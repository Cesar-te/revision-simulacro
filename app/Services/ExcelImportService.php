<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAnswerKey;
use App\Models\StudentResult;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExcelImportService
{
    protected ScoringService $scoringService;

    public function __construct(ScoringService $scoringService)
    {
        $this->scoringService = $scoringService;
    }

    /**
     * Importa las claves oficiales desde un archivo Excel
     */
    public function importAnswerKeys(Exam $exam, string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName('Respuestas') 
            ?? $spreadsheet->getSheetByName('Clave rápida') 
            ?? $spreadsheet->getActiveSheet();

        $rows = $sheet->toArray(null, true, true, true);

        // Detectar fila de cabecera
        $headerRowIndex = 1;
        $colMap = [
            'num' => null,
            'subject' => null,
            'key' => null,
            'explanation' => null,
        ];

        foreach ($rows as $rIdx => $row) {
            foreach ($row as $colLetter => $val) {
                if (!$val) continue;
                $v = mb_strtolower(trim((string)$val), 'UTF-8');
                if (in_array($v, ['n.º', 'n°', 'nº', 'número', 'numero', 'n.', 'item', 'pregunta', 'preg'])) {
                    $colMap['num'] = $colLetter;
                    $headerRowIndex = $rIdx;
                }
                if (in_array($v, ['área', 'area', 'asignatura', 'materia', 'curso'])) {
                    $colMap['subject'] = $colLetter;
                }
                if (in_array($v, ['clave', 'clave correcta', 'respuesta', 'resp'])) {
                    $colMap['key'] = $colLetter;
                }
                if (in_array($v, ['justificación breve', 'justificacion breve', 'justificación', 'justificacion', 'explicación', 'explicacion'])) {
                    $colMap['explanation'] = $colLetter;
                }
            }
            if ($colMap['num'] && $colMap['key']) {
                break;
            }
        }

        // Si no se encontró mapeo automático por cabecera, asumir columnas comunes (A=Num, B=Area, D=Clave)
        if (!$colMap['num']) $colMap['num'] = 'A';
        if (!$colMap['subject']) $colMap['subject'] = 'B';
        if (!$colMap['key']) $colMap['key'] = 'D';

        $importedCount = 0;
        $maxQuestionNum = 0;

        foreach ($rows as $rIdx => $row) {
            if ($rIdx <= $headerRowIndex) continue;

            $qNumRaw = $row[$colMap['num']] ?? null;
            if (!$qNumRaw || !is_numeric($qNumRaw)) continue;

            $qNum = (int) $qNumRaw;
            $subject = trim((string)($row[$colMap['subject']] ?? 'General'));
            $key = strtoupper(trim((string)($row[$colMap['key']] ?? '')));
            $explanation = trim((string)($row[$colMap['explanation'] ?? '']));

            if (empty($key)) continue;

            // Extraer solo la letra de clave si viene como "A) Opción" o similar
            if (strlen($key) > 1 && preg_match('/^[A-E]/', $key, $m)) {
                $key = $m[0];
            }

            ExamAnswerKey::updateOrCreate(
                [
                    'exam_id' => $exam->id,
                    'question_number' => $qNum,
                ],
                [
                    'subject'     => $subject ?: 'General',
                    'correct_key' => substr($key, 0, 5),
                    'explanation' => $explanation ?: null,
                    'is_annulled' => ($key === '*'),
                ]
            );

            $importedCount++;
            if ($qNum > $maxQuestionNum) {
                $maxQuestionNum = $qNum;
            }
        }

        if ($maxQuestionNum > 0 && $exam->total_questions < $maxQuestionNum) {
            $exam->total_questions = $maxQuestionNum;
            $exam->save();
        }

        return [
            'success' => true,
            'imported_keys' => $importedCount,
            'max_question' => $maxQuestionNum,
        ];
    }

    /**
     * Importa las respuestas de los estudiantes desde el Excel (Google Forms / Formato simulacro)
     */
    public function importStudentResponses(Exam $exam, string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (empty($rows)) {
            return ['success' => false, 'message' => 'El archivo Excel está vacío.'];
        }

        // Analizar la cabecera (Fila 1)
        $headerRow = $rows[1] ?? [];
        $colDni = null;
        $colName = null;
        $colEmail = null;
        $colCareer = null;
        $colTimestamp = null;
        $questionCols = []; // [q_num => ['col' => 'G', 'subject' => 'Habilidad Verbal']]

        foreach ($headerRow as $colLetter => $headerText) {
            if (!$headerText) continue;
            $h = mb_strtolower(trim((string)$headerText), 'UTF-8');

            if (str_contains($h, 'dni') || str_contains($h, 'documento') || str_contains($h, 'identidad')) {
                $colDni = $colLetter;
                continue;
            }
            if (str_contains($h, 'nombre') || str_contains($h, 'apellidos') || str_contains($h, 'postulante') || str_contains($h, 'alumno')) {
                if (!$colName) $colName = $colLetter;
                continue;
            }
            if (str_contains($h, 'correo') || str_contains($h, 'email')) {
                $colEmail = $colLetter;
                continue;
            }
            if (str_contains($h, 'carrera') || str_contains($h, 'especialidad') || str_contains($h, 'opcion')) {
                $colCareer = $colLetter;
                continue;
            }
            if (str_contains($h, 'marca temporal') || str_contains($h, 'fecha') || str_contains($h, 'timestamp')) {
                $colTimestamp = $colLetter;
                continue;
            }

            // Buscar patrón de pregunta: ej. "HABILIDAD VERBAL [PREGUNTA 1]" o "[PREGUNTA 1]" o "P1" o "1"
            if (preg_match('/\[PREGUNTA\s*(\d+)\]/i', $headerText, $matches) ||
                preg_match('/PREGUNTA\s*(\d+)/i', $headerText, $matches) ||
                preg_match('/^P\s*(\d+)$/i', $headerText, $matches) ||
                preg_match('/^(\d+)$/i', $headerText, $matches)) {
                
                $qNum = (int)$matches[1];
                $subject = 'General';
                // Extraer el nombre de la asignatura antes del corchete
                if (preg_match('/^(.*?)\s*\[/i', $headerText, $subMatch)) {
                    $subject = trim($subMatch[1]);
                }
                $questionCols[$qNum] = [
                    'col' => $colLetter,
                    'subject' => $subject,
                ];
            }
        }

        // Si no se detectaron columnas por nombre, buscar por posición clásica
        if (!$colName) $colName = 'D';
        if (!$colDni) $colDni = 'E';
        if (!$colCareer) $colCareer = 'F';
        if (!$colEmail) $colEmail = 'B';
        if (!$colTimestamp) $colTimestamp = 'A';

        // Si no se detectaron preguntas con corchetes, asumir columnas consecutivas a partir de la columna 7 (G)
        if (empty($questionCols)) {
            $colLetters = array_keys($headerRow);
            $startIdx = 6; // índice 0-based para columna 7 (G)
            $qCount = 1;
            for ($i = $startIdx; $i < count($colLetters); $i++) {
                $cLetter = $colLetters[$i];
                $questionCols[$qCount] = [
                    'col' => $cLetter,
                    'subject' => 'General',
                ];
                $qCount++;
            }
        }

        // Si el examen aún no tiene claves o tiene materias genéricas, registrar materias detectadas en cabeceras
        foreach ($questionCols as $qNum => $qData) {
            $existingKey = ExamAnswerKey::where('exam_id', $exam->id)->where('question_number', $qNum)->first();
            if ($existingKey && ($existingKey->subject === 'General' || empty($existingKey->subject)) && $qData['subject'] !== 'General') {
                $existingKey->subject = $qData['subject'];
                $existingKey->save();
            }
        }

        $importedStudents = 0;
        $totalRows = count($rows);

        // Limpiar resultados anteriores para evitar duplicados en re-importaciones
        StudentResult::where('exam_id', $exam->id)->delete();

        for ($rIdx = 2; $rIdx <= $totalRows; $rIdx++) {
            $row = $rows[$rIdx] ?? null;
            if (!$row) continue;

            $fullName = trim((string)($row[$colName] ?? ''));
            $dni = trim((string)($row[$colDni] ?? ''));
            $career = trim((string)($row[$colCareer] ?? ''));
            $email = trim((string)($row[$colEmail] ?? ''));
            $timestampRaw = $row[$colTimestamp] ?? null;

            // Si la fila no tiene nombre ni DNI, se salta
            if (empty($fullName) && empty($dni)) {
                continue;
            }

            // Parsear respuestas dadas por el estudiante
            $studentAnswers = [];
            foreach ($questionCols as $qNum => $qData) {
                $rawAns = $row[$qData['col']] ?? '';
                $cleanAns = strtoupper(trim((string)$rawAns));
                // Extraer solo la primera letra si viene "A) Respuesta..."
                if (strlen($cleanAns) > 1 && preg_match('/^[A-E]/', $cleanAns, $m)) {
                    $cleanAns = $m[0];
                }
                $studentAnswers[$qNum] = $cleanAns;
            }

            // Calificar al estudiante usando ScoringService
            $scoreData = $this->scoringService->scoreStudent($exam, $studentAnswers, $career ?: 'Sin Carrera');

            $submittedAt = null;
            if ($timestampRaw) {
                try {
                    $submittedAt = Carbon::parse($timestampRaw);
                } catch (\Exception $e) {
                    $submittedAt = null;
                }
            }

            StudentResult::create([
                'exam_id'            => $exam->id,
                'dni'                => $dni ?: null,
                'full_name'          => $fullName ?: 'Estudiante sin nombre',
                'email'              => $email ?: null,
                'career'             => $career ?: 'Sin Carrera',
                'academic_group'     => $scoreData['academic_group'],
                'group_label'        => $scoreData['group_label'],
                'correct_count'      => $scoreData['correct_count'],
                'incorrect_count'    => $scoreData['incorrect_count'],
                'blank_count'        => $scoreData['blank_count'],
                'total_score'        => $scoreData['total_score'],
                'answers_json'       => $studentAnswers,
                'scores_detail_json' => $scoreData['scores_detail_json'],
                'submitted_at'       => $submittedAt,
            ]);

            $importedStudents++;
        }

        // Recalcular puestos / ranking de mérito
        $this->scoringService->recalculateRanks($exam);

        return [
            'success'           => true,
            'imported_students' => $importedStudents,
            'total_questions'   => count($questionCols),
        ];
    }
}
