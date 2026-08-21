<?php

namespace App\Services;

use App\Models\Career;
use App\Models\Exam;
use App\Models\ExamAnswerKey;
use App\Models\StudentResult;
use Illuminate\Support\Collection;

class ScoringService
{
    /**
     * Ponderaciones por defecto por Asignatura / Bloque y Grupo Académico
     */
    public const WEIGHT_CONFIG = [
        'A' => [ // Biomédicas
            'VERBAL_MATE' => 20.0000,
            'MATE_BASIC'  => 14.3014,
            'LETRAS'      => 14.2770,
            'FISICA_QUIM' => 25.0000,
            'BIOLOGIA'    => 25.0000,
        ],
        'BCD' => [ // Letras / Humanidades / Económicas / Sociales
            'VERBAL_MATE' => 20.0000,
            'MATE_BASIC'  => 16.0012,
            'LETRAS'      => 23.5290,
            'FISICA_QUIM' => 14.5450,
            'BIOLOGIA'    => 14.5450,
        ],
        'EF' => [ // Ingenierías / Agropecuarias
            'VERBAL_MATE' => 20.0000,
            'MATE_BASIC'  => 22.2220,
            'LETRAS'      => 17.6310,
            'FISICA_QUIM' => 22.2220,
            'BIOLOGIA'    => 13.0038,
        ],
    ];

    /**
     * Mapea el nombre de una materia/asignatura a su categoría/bloque
     */
    public function getSubjectCategory(string $subject): string
    {
        $sub = mb_strtoupper(trim($subject), 'UTF-8');
        $clean = strtr($sub, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N']);

        if (str_contains($clean, 'VERBAL') || str_contains($clean, 'HABILIDAD MATEMATICA') || str_contains($clean, 'RAZONAMIENTO')) {
            return 'VERBAL_MATE';
        }

        if (str_contains($clean, 'ARITMETICA') || str_contains($clean, 'GEOMETRIA') || str_contains($clean, 'ALGEBRA') || str_contains($clean, 'TRIGONOMETRIA') || str_contains($clean, 'MATEMATICA')) {
            return 'MATE_BASIC';
        }

        if (
            str_contains($clean, 'LENGUAJE') || str_contains($clean, 'LITERATURA') ||
            str_contains($clean, 'PSICOLOGIA') || str_contains($clean, 'CIVICA') ||
            str_contains($clean, 'HISTORIA') || str_contains($clean, 'GEOGRAFIA') ||
            str_contains($clean, 'ECONOMIA') || str_contains($clean, 'FILOSOFIA')
        ) {
            return 'LETRAS';
        }

        if (str_contains($clean, 'FISICA') || str_contains($clean, 'QUIMICA')) {
            return 'FISICA_QUIM';
        }

        if (str_contains($clean, 'BIOLOGIA')) {
            return 'BIOLOGIA';
        }

        return 'VERBAL_MATE';
    }

    /**
     * Retorna el puntaje de respuesta correcta según la materia y el grupo académico
     */
    public function getPointsForCorrect(string $subject, string $group): float
    {
        $groupKey = $this->normalizeGroup($group);
        $category = $this->getSubjectCategory($subject);

        return self::WEIGHT_CONFIG[$groupKey][$category] ?? 20.0000;
    }

    /**
     * Normaliza el código de grupo ('A', 'B', 'C', 'D', 'E', 'F', 'BCD', 'EF')
     */
    public function normalizeGroup(string $group): string
    {
        $g = strtoupper(trim($group));
        if (in_array($g, ['B', 'C', 'D', 'BCD', 'LETRAS'])) {
            return 'BCD';
        }
        if (in_array($g, ['E', 'F', 'EF', 'INGENIERIA', 'INGENIERIAS'])) {
            return 'EF';
        }
        return 'A'; // Default Biomédicas
    }

    /**
     * Obtiene el grupo académico a partir del nombre de la carrera
     */
    public function getGroupForCareer(string $careerName): array
    {
        $cleanName = trim($careerName);
        $career = Career::where('name', $cleanName)->first();

        if ($career) {
            return [
                'academic_group' => $career->academic_group,
                'group_label'    => $career->group_label,
            ];
        }

        // Búsqueda aproximada / heurística
        $upper = mb_strtoupper($cleanName, 'UTF-8');
        $clean = strtr($upper, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U']);

        if (str_contains($clean, 'MEDICINA') || str_contains($clean, 'ENFERMERIA') || (str_contains($clean, 'BIOLOGIA') && !str_contains($clean, 'EDUCACION')) || str_contains($clean, 'VETERINARIA')) {
            return ['academic_group' => 'A', 'group_label' => 'Ciencias Médicas (Biomédicas)'];
        }

        if (str_contains($clean, 'INGENIERIA') || str_contains($clean, 'ARQUITECTURA') || str_contains($clean, 'AGRONOMIA') || str_contains($clean, 'ZOOTECNIA') || str_contains($clean, 'FISICA') || str_contains($clean, 'ESTADISTICA')) {
            return ['academic_group' => 'EF', 'group_label' => 'Ciencias e Ingenierías'];
        }

        // Default Letras
        return ['academic_group' => 'BCD', 'group_label' => 'Letras / Sociales y Económicas'];
    }

    /**
     * Califica las respuestas de un estudiante contra las claves del examen
     */
    public function scoreStudent(Exam $exam, array $studentAnswers, string $careerName): array
    {
        $groupInfo = $this->getGroupForCareer($careerName);
        $groupCode = $this->normalizeGroup($groupInfo['academic_group']);

        $answerKeys = $exam->answerKeys->keyBy('question_number');
        $penalty = (float) $exam->incorrect_penalty; // e.g. -1.1250
        if ($penalty > 0) {
            $penalty = -$penalty; // aseguramos que sea negativa
        }

        $correctCount = 0;
        $incorrectCount = 0;
        $blankCount = 0;
        $totalScore = 0.0;
        $scoresDetail = [];

        $totalQuestions = $exam->total_questions > 0 ? $exam->total_questions : count($answerKeys);

        for ($i = 1; $i <= $totalQuestions; $i++) {
            $givenAnswer = isset($studentAnswers[$i]) ? strtoupper(trim((string)$studentAnswers[$i])) : '';
            /** @var ExamAnswerKey|null $keyObj */
            $keyObj = $answerKeys->get($i);

            $subject = $keyObj ? ($keyObj->subject ?? 'General') : 'General';
            $correctKey = $keyObj ? strtoupper(trim($keyObj->correct_key)) : '';
            $isAnnulled = $keyObj ? $keyObj->is_annulled : false;

            $ptsCorrect = $this->getPointsForCorrect($subject, $groupCode);

            $status = 'blank';
            $questionScore = 0.0;

            if ($isAnnulled || $correctKey === '*') {
                // Pregunta anulada: se otorga el puntaje completo a todos
                $status = 'annulled';
                $questionScore = $ptsCorrect;
                $correctCount++;
            } elseif ($givenAnswer === '' || $givenAnswer === '-' || $givenAnswer === 'BLANCO' || $givenAnswer === 'NONE') {
                $status = 'blank';
                $questionScore = (float) $exam->blank_score;
                $blankCount++;
            } elseif ($givenAnswer === $correctKey) {
                $status = 'correct';
                $questionScore = $ptsCorrect;
                $correctCount++;
            } else {
                $status = 'incorrect';
                $questionScore = $penalty;
                $incorrectCount++;
            }

            $totalScore += $questionScore;

            $scoresDetail[$i] = [
                'question_number' => $i,
                'subject'         => $subject,
                'given_answer'    => $givenAnswer,
                'correct_key'     => $correctKey,
                'status'          => $status,
                'points'          => round($questionScore, 4),
            ];
        }

        return [
            'academic_group'     => $groupInfo['academic_group'],
            'group_label'        => $groupInfo['group_label'],
            'correct_count'      => $correctCount,
            'incorrect_count'    => $incorrectCount,
            'blank_count'        => $blankCount,
            'total_score'        => round(max(0, $totalScore), 4), // el puntaje total mínimo es 0
            'raw_total_score'    => round($totalScore, 4),
            'scores_detail_json' => $scoresDetail,
        ];
    }

    /**
     * Recalcula los puestos/rankings (general, por carrera y por grupo) de un simulacro
     */
    public function recalculateRanks(Exam $exam): void
    {
        $results = StudentResult::where('exam_id', $exam->id)
            ->orderByDesc('total_score')
            ->orderByDesc('correct_count')
            ->orderBy('incorrect_count')
            ->orderBy('id')
            ->get();

        // 1. Ranking General
        $genRank = 1;
        foreach ($results as $res) {
            $res->general_rank = $genRank++;
            $res->save();
        }

        // 2. Ranking por Carrera
        $byCareer = $results->groupBy('career');
        foreach ($byCareer as $careerResults) {
            $cRank = 1;
            foreach ($careerResults as $res) {
                $res->career_rank = $cRank++;
                $res->save();
            }
        }

        // 3. Ranking por Grupo Académico
        $byGroup = $results->groupBy('academic_group');
        foreach ($byGroup as $groupResults) {
            $gRank = 1;
            foreach ($groupResults as $res) {
                $res->group_rank = $gRank++;
                $res->save();
            }
        }
    }
}
