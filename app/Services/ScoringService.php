<?php

namespace App\Services;

use App\Models\Career;
use App\Models\Exam;
use App\Models\ExamAnswerKey;
use App\Models\ExamScoringRule;
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
            'MATE_BASIC' => 14.3014,
            'LETRAS' => 14.2770,
            'FISICA_QUIM' => 25.0000,
            'BIOLOGIA' => 25.0000,
        ],
        'BCD' => [ // Letras / Humanidades / Económicas / Sociales
            'VERBAL_MATE' => 20.0000,
            'MATE_BASIC' => 16.0012,
            'LETRAS' => 23.5290,
            'FISICA_QUIM' => 14.5450,
            'BIOLOGIA' => 14.5450,
        ],
        'EF' => [ // Ingenierías / Agropecuarias
            'VERBAL_MATE' => 20.0000,
            'MATE_BASIC' => 22.2220,
            'LETRAS' => 17.6310,
            'FISICA_QUIM' => 22.2220,
            'BIOLOGIA' => 13.0038,
        ],
    ];

    public const SUBJECT_WEIGHT_CONFIG = [
        'A' => [ // Biomedicas
            'HV' => 20.0000,
            'HM' => 20.0000,
            'ARIT' => 14.3014,
            'GEOM' => 14.3014,
            'ALG' => 14.3014,
            'TRIG' => 14.3014,
            'LENG' => 14.2770,
            'LIT' => 14.2770,
            'PSIC' => 14.2770,
            'CIV' => 14.2770,
            'HIST' => 14.2770,
            'GEOG' => 14.2770,
            'ECON' => 14.2770,
            'FILO' => 14.2770,
            'FIS' => 25.0000,
            'QUI' => 25.0000,
            'BIO' => 25.0000,
        ],
        'BCD' => [ // Letras / Humanidades / Economicas / Sociales
            'HV' => 20.0000,
            'HM' => 20.0000,
            'ARIT' => 16.0012,
            'GEOM' => 16.0012,
            'ALG' => 16.0012,
            'TRIG' => 16.0012,
            'LENG' => 23.5290,
            'LIT' => 23.5290,
            'PSIC' => 23.5290,
            'CIV' => 23.5290,
            'HIST' => 23.5290,
            'GEOG' => 23.5290,
            'ECON' => 23.5290,
            'FILO' => 23.5290,
            'FIS' => 14.5450,
            'QUI' => 14.5450,
            'BIO' => 14.5450,
        ],
        'EF' => [ // Ingenierias / Agropecuarias
            'HV' => 20.0000,
            'HM' => 20.0000,
            'ARIT' => 22.2220,
            'GEOM' => 22.2220,
            'ALG' => 22.2220,
            'TRIG' => 22.2220,
            'LENG' => 17.6310,
            'LIT' => 17.6310,
            'PSIC' => 17.6310,
            'CIV' => 17.6310,
            'HIST' => 17.6310,
            'GEOG' => 17.6310,
            'ECON' => 17.6310,
            'FILO' => 17.6310,
            'FIS' => 22.2220,
            'QUI' => 22.2220,
            'BIO' => 13.0038,
        ],
    ];

    public const CATEGORY_LABELS = [
        'VERBAL_MATE' => 'Habilidad verbal y matematica',
        'MATE_BASIC' => 'Ciencias basicas',
        'LETRAS' => 'Letras y humanidades',
        'FISICA_QUIM' => 'Fisica y quimica',
        'BIOLOGIA' => 'Biologia',
    ];

    public const GROUP_LABELS = [
        'A' => 'Ciencias Medicas (Biomedicas)',
        'BCD' => 'Letras / Humanidades',
        'EF' => 'Ciencias e Ingenierias',
    ];

    /**
     * Lista y mapeo de columnas de asignaturas UNPRG
     */
    public const SUBJECT_COLUMNS = [
        'HV' => ['name' => 'Habilidad Verbal', 'short' => 'HV'],
        'HM' => ['name' => 'Habilidad Matemática', 'short' => 'HM'],
        'ARIT' => ['name' => 'Aritmética', 'short' => 'ARIT'],
        'GEOM' => ['name' => 'Geometría', 'short' => 'GEOM'],
        'ALG' => ['name' => 'Álgebra', 'short' => 'ALG'],
        'TRIG' => ['name' => 'Trigonometría', 'short' => 'TRIG'],
        'LENG' => ['name' => 'Lenguaje', 'short' => 'LENG'],
        'LIT' => ['name' => 'Literatura', 'short' => 'LIT'],
        'PSIC' => ['name' => 'Psicología', 'short' => 'PSIC'],
        'CIV' => ['name' => 'Educación Cívica', 'short' => 'CIV'],
        'HIST' => ['name' => 'Historia', 'short' => 'HIST'],
        'GEOG' => ['name' => 'Geografía', 'short' => 'GEOG'],
        'ECON' => ['name' => 'Economía', 'short' => 'ECON'],
        'FILO' => ['name' => 'Filosofía', 'short' => 'FILO'],
        'FIS' => ['name' => 'Física', 'short' => 'FIS'],
        'QUI' => ['name' => 'Química', 'short' => 'QUI'],
        'BIO' => ['name' => 'Biología', 'short' => 'BIO'],
    ];

    /**
     * Mapea el nombre de una materia/asignatura a su código de columna (HV, HM, ARIT, etc.)
     */
    public function getSubjectCode(string $subject): string
    {
        $sub = mb_strtoupper(trim($subject), 'UTF-8');
        $clean = strtr($sub, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N']);

        if (str_contains($clean, 'VERBAL') || str_contains($clean, 'R.V.') || str_ends_with($clean, ' RV') || $clean === 'RV' || $clean === 'HV') {
            return 'HV';
        }
        if (str_contains($clean, 'HABILIDAD MATEMATICA') || str_contains($clean, 'RAZONAMIENTO MATEMATICO') || str_contains($clean, 'R.M.') || str_ends_with($clean, ' RM') || $clean === 'RM' || $clean === 'HM') {
            return 'HM';
        }
        if (str_contains($clean, 'ARITMETICA') || $clean === 'ARIT') {
            return 'ARIT';
        }
        if (str_contains($clean, 'GEOMETRIA') || $clean === 'GEOM') {
            return 'GEOM';
        }
        if (str_contains($clean, 'ALGEBRA') || $clean === 'ALG') {
            return 'ALG';
        }
        if (str_contains($clean, 'TRIGONOMETRIA') || $clean === 'TRIG') {
            return 'TRIG';
        }
        if (str_contains($clean, 'LENGUAJE') || str_contains($clean, 'COMUNICACION') || str_contains($clean, 'LENGUA') || $clean === 'LENG') {
            return 'LENG';
        }
        if (str_contains($clean, 'LITERATURA') || $clean === 'LIT') {
            return 'LIT';
        }
        if (str_contains($clean, 'PSICOLOGIA') || $clean === 'PSIC') {
            return 'PSIC';
        }
        if (str_contains($clean, 'CIVICA') || str_contains($clean, 'CIVICO') || $clean === 'CIV') {
            return 'CIV';
        }
        if (str_contains($clean, 'HISTORIA') || $clean === 'HIST') {
            return 'HIST';
        }
        if (str_contains($clean, 'GEOGRAFIA') || $clean === 'GEOG') {
            return 'GEOG';
        }
        if (str_contains($clean, 'ECONOMIA') || $clean === 'ECON') {
            return 'ECON';
        }
        if (str_contains($clean, 'FILOSOFIA') || $clean === 'FILO') {
            return 'FILO';
        }
        if (str_contains($clean, 'FISICA') || $clean === 'FIS') {
            return 'FIS';
        }
        if (str_contains($clean, 'QUIMICA') || $clean === 'QUI') {
            return 'QUI';
        }
        if (str_contains($clean, 'BIOLOGIA') || str_contains($clean, 'ANATOMIA') || str_contains($clean, 'BOTANICA') || $clean === 'BIO') {
            return 'BIO';
        }

        return 'HV';
    }

    /**
     * Mapea el nombre de una materia/asignatura a su categoría/bloque
     */
    public function getSubjectCategory(string $subject): string
    {
        $code = $this->getSubjectCode($subject);
        if (in_array($code, ['HV', 'HM'], true)) {
            return 'VERBAL_MATE';
        }
        if (in_array($code, ['ARIT', 'GEOM', 'ALG', 'TRIG'], true)) {
            return 'MATE_BASIC';
        }
        if (in_array($code, ['LENG', 'LIT', 'PSIC', 'CIV', 'HIST', 'GEOG', 'ECON', 'FILO'], true)) {
            return 'LETRAS';
        }
        if (in_array($code, ['FIS', 'QUI'], true)) {
            return 'FISICA_QUIM';
        }
        if ($code === 'BIO') {
            return 'BIOLOGIA';
        }

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
    public function ensureScoringRules(Exam $exam): void
    {
        $existingRules = ExamScoringRule::where('exam_id', $exam->id)
            ->get()
            ->groupBy(fn (ExamScoringRule $rule) => $this->normalizeGroup($rule->academic_group));

        foreach (self::SUBJECT_WEIGHT_CONFIG as $group => $subjects) {
            $existingGroupRules = $existingRules->get($group, collect())->keyBy('category');

            foreach ($subjects as $subjectCode => $points) {
                $category = $this->getSubjectCategory($subjectCode);
                $pointsFromExistingCategory = $existingGroupRules->get($category)?->points_correct;

                ExamScoringRule::firstOrCreate(
                    [
                        'exam_id' => $exam->id,
                        'academic_group' => $group,
                        'category' => $subjectCode,
                    ],
                    [
                        'points_correct' => $pointsFromExistingCategory ?? $points,
                    ]
                );
            }
        }
    }

    /**
     * @return array<string, array<string, float>>
     */
    public function getScoringRulesMatrix(Exam $exam): array
    {
        $this->ensureScoringRules($exam);

        $matrix = self::SUBJECT_WEIGHT_CONFIG;
        $rules = ExamScoringRule::where('exam_id', $exam->id)->get();

        foreach ($rules as $rule) {
            $group = $this->normalizeGroup($rule->academic_group);
            $subjectCode = strtoupper(trim($rule->category));
            if (! isset($matrix[$group][$subjectCode])) {
                continue;
            }

            $matrix[$group][$subjectCode] = (float) $rule->points_correct;
        }

        return $matrix;
    }

    /**
     * @param  array<string, array<string, float>>|null  $rulesMatrix
     */
    public function getPointsForCorrect(Exam $exam, string $subject, string $group, ?array $rulesMatrix = null): float
    {
        $groupKey = $this->normalizeGroup($group);
        $subjectCode = $this->getSubjectCode($subject);
        $category = $this->getSubjectCategory($subject);
        $rulesMatrix ??= $this->getScoringRulesMatrix($exam);

        return $rulesMatrix[$groupKey][$subjectCode]
            ?? self::SUBJECT_WEIGHT_CONFIG[$groupKey][$subjectCode]
            ?? self::WEIGHT_CONFIG[$groupKey][$category]
            ?? 20.0000;
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
                'group_label' => $career->group_label,
            ];
        }

        // Búsqueda aproximada / heurística
        $upper = mb_strtoupper($cleanName, 'UTF-8');
        $clean = strtr($upper, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U']);

        if (str_contains($clean, 'MEDICINA') || str_contains($clean, 'ENFERMERIA') || (str_contains($clean, 'BIOLOGIA') && ! str_contains($clean, 'EDUCACION')) || str_contains($clean, 'VETERINARIA')) {
            return ['academic_group' => 'A', 'group_label' => 'Ciencias Médicas (Biomédicas)'];
        }

        if (str_contains($clean, 'INGENIERIA') || str_contains($clean, 'ARQUITECTURA') || str_contains($clean, 'AGRONOMIA') || str_contains($clean, 'ZOOTECNIA') || str_contains($clean, 'FISICA') || str_contains($clean, 'ESTADISTICA')) {
            return ['academic_group' => 'EF', 'group_label' => 'Ciencias e Ingenierías'];
        }

        // Default Letras
        return ['academic_group' => 'BCD', 'group_label' => 'Letras / Sociales y Económicas'];
    }

    /**
     * Obtiene las claves del examen correspondientes al grupo académico del estudiante
     */
    public function getAnswerKeysForGroup(Exam $exam, string $groupCode): Collection
    {
        $keys = $exam->answerKeys()->where('academic_group', $groupCode)->get();
        if ($keys->isEmpty()) {
            $keys = $exam->answerKeys()->where('academic_group', 'ALL')->get();
        }
        if ($keys->isEmpty()) {
            $keys = $exam->answerKeys()->get();
        }

        return $keys->keyBy('question_number');
    }

    /**
     * Suma el maximo posible con las claves cargadas y los puntajes vigentes.
     */
    public function getMaximumCorrectScoreForGroup(Exam $exam, string $groupCode): float
    {
        $answerKeys = $this->getAnswerKeysForGroup($exam, $this->normalizeGroup($groupCode));
        $rulesMatrix = $this->getScoringRulesMatrix($exam);
        $total = 0.0;

        foreach ($answerKeys as $key) {
            $total += $this->getPointsForCorrect($exam, $key->subject ?? 'General', $groupCode, $rulesMatrix);
        }

        return round($total, 4);
    }

    /**
     * Califica las respuestas de un estudiante contra las claves del examen
     */
    public function scoreStudent(Exam $exam, array $studentAnswers, string $careerName, ?string $forcedGroup = null): array
    {
        $groupInfo = $this->getGroupForCareer($careerName);
        $groupCode = $forcedGroup ? $this->normalizeGroup($forcedGroup) : $this->normalizeGroup($groupInfo['academic_group']);

        $groupLabel = match ($groupCode) {
            'A' => 'Ciencias Médicas (Biomédicas)',
            'BCD' => 'Letras / Sociales y Económicas',
            'EF' => 'Ciencias e Ingenierías',
            default => $groupInfo['group_label'] ?? 'General',
        };

        $answerKeys = $this->getAnswerKeysForGroup($exam, $groupCode);
        $rulesMatrix = $this->getScoringRulesMatrix($exam);
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
            $givenAnswer = isset($studentAnswers[$i]) ? strtoupper(trim((string) $studentAnswers[$i])) : '';
            /** @var ExamAnswerKey|null $keyObj */
            $keyObj = $answerKeys->get($i);

            $subject = $keyObj ? ($keyObj->subject ?? 'General') : 'General';
            $correctKey = $keyObj ? strtoupper(trim($keyObj->correct_key)) : '';
            $isAnnulled = $keyObj ? $keyObj->is_annulled : false;

            $ptsCorrect = $this->getPointsForCorrect($exam, $subject, $groupCode, $rulesMatrix);

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
                'subject' => $subject,
                'given_answer' => $givenAnswer,
                'correct_key' => $correctKey,
                'status' => $status,
                'points' => round($questionScore, 4),
            ];
        }

        return [
            'academic_group' => $groupCode,
            'group_label' => $groupLabel,
            'correct_count' => $correctCount,
            'incorrect_count' => $incorrectCount,
            'blank_count' => $blankCount,
            'total_score' => round($totalScore, 4), // Permite puntajes negativos
            'raw_total_score' => round($totalScore, 4),
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
