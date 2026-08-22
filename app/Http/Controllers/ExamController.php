<?php

namespace App\Http\Controllers;

use App\Models\Career;
use App\Models\Exam;
use App\Models\ExamAnswerKey;
use App\Models\ExamScoringRule;
use App\Models\StudentResult;
use App\Services\ExcelExportService;
use App\Services\ExcelImportService;
use App\Services\PdfExportService;
use App\Services\ScoringService;
use Illuminate\Http\Request;
use Throwable;

class ExamController extends Controller
{
    protected ExcelImportService $importService;

    protected ExcelExportService $exportService;

    protected PdfExportService $pdfService;

    protected ScoringService $scoringService;

    private const EXCEL_FILE_RULE = 'nullable|file|mimes:xlsx,xls,csv|max:20480';

    public function __construct(
        ExcelImportService $importService,
        ExcelExportService $exportService,
        PdfExportService $pdfService,
        ScoringService $scoringService
    ) {
        $this->importService = $importService;
        $this->exportService = $exportService;
        $this->pdfService = $pdfService;
        $this->scoringService = $scoringService;
    }

    /**
     * Pantalla Principal / Listado de Simulacros
     */
    public function index()
    {
        $exams = Exam::withCount(['answerKeys', 'studentResults'])
            ->latest()
            ->get();

        $keyGroupCounts = ExamAnswerKey::query()
            ->select('exam_id')
            ->selectRaw('COUNT(DISTINCT academic_group) as groups_count')
            ->groupBy('exam_id')
            ->pluck('groups_count', 'exam_id');

        $exams->each(function (Exam $exam) use ($keyGroupCounts): void {
            $keyGroupsCount = max(1, (int) ($keyGroupCounts[$exam->id] ?? 0));
            $exam->setAttribute('answer_key_groups_count', $keyGroupsCount);
            $exam->setAttribute('expected_answer_keys_count', $exam->total_questions * $keyGroupsCount);
        });

        $careers = Career::orderBy('name')->get();

        return view('exams.index', compact('exams', 'careers'));
    }

    /**
     * Crea un nuevo simulacro
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'incorrect_penalty' => 'nullable|numeric',
            'blank_score' => 'nullable|numeric',
            'total_questions' => 'nullable|integer|min:1|max:200',
            'academic_group_keys' => 'nullable|in:A,BCD,EF,ALL',
            'academic_group_responses' => 'nullable|in:A,BCD,EF',
            'keys_file_a' => self::EXCEL_FILE_RULE,
            'keys_file_bcd' => self::EXCEL_FILE_RULE,
            'keys_file_ef' => self::EXCEL_FILE_RULE,
            'keys_file' => self::EXCEL_FILE_RULE,
            'responses_file_a' => self::EXCEL_FILE_RULE,
            'responses_file_bcd' => self::EXCEL_FILE_RULE,
            'responses_file_ef' => self::EXCEL_FILE_RULE,
            'responses_file' => self::EXCEL_FILE_RULE,
        ]);

        $penalty = $request->input('incorrect_penalty', -1.1250);
        if ($penalty > 0) {
            $penalty = -$penalty;
        }

        $exam = Exam::create([
            'title' => $request->title,
            'description' => $request->description,
            'incorrect_penalty' => $penalty,
            'blank_score' => $request->input('blank_score', 0.0000),
            'total_questions' => $request->input('total_questions', 100),
        ]);
        $this->scoringService->ensureScoringRules($exam);

        $errors = [];

        // 1. Claves en lote (A, BCD, EF) o individuales
        if ($request->hasFile('keys_file_a')) {
            $this->runImport(fn () => $this->importService->importAnswerKeys($exam, $request->file('keys_file_a')->getRealPath(), 'A'), 'imported_keys', $errors);
        }
        if ($request->hasFile('keys_file_bcd')) {
            $this->runImport(fn () => $this->importService->importAnswerKeys($exam, $request->file('keys_file_bcd')->getRealPath(), 'BCD'), 'imported_keys', $errors);
        }
        if ($request->hasFile('keys_file_ef')) {
            $this->runImport(fn () => $this->importService->importAnswerKeys($exam, $request->file('keys_file_ef')->getRealPath(), 'EF'), 'imported_keys', $errors);
        }
        if ($request->hasFile('keys_file')) {
            $groupKeys = $request->input('academic_group_keys', 'ALL');
            $this->runImport(fn () => $this->importService->importAnswerKeys($exam, $request->file('keys_file')->getRealPath(), $groupKeys), 'imported_keys', $errors);
        }

        // 2. Respuestas en lote (A, BCD, EF) o individuales
        if ($request->hasFile('responses_file_a')) {
            $this->runImport(fn () => $this->importService->importStudentResponses($exam, $request->file('responses_file_a')->getRealPath(), 'A'), 'imported_students', $errors);
        }
        if ($request->hasFile('responses_file_bcd')) {
            $this->runImport(fn () => $this->importService->importStudentResponses($exam, $request->file('responses_file_bcd')->getRealPath(), 'BCD'), 'imported_students', $errors);
        }
        if ($request->hasFile('responses_file_ef')) {
            $this->runImport(fn () => $this->importService->importStudentResponses($exam, $request->file('responses_file_ef')->getRealPath(), 'EF'), 'imported_students', $errors);
        }
        if ($request->hasFile('responses_file')) {
            $groupResp = $request->input('academic_group_responses', null);
            $this->runImport(fn () => $this->importService->importStudentResponses($exam, $request->file('responses_file')->getRealPath(), $groupResp), 'imported_students', $errors);
        }

        if (! empty($errors)) {
            return redirect()->route('exams.show', $exam)->with('error', implode(' ', $errors));
        }

        return redirect()->route('exams.show', $exam)->with('success', 'Simulacro creado y procesado exitosamente.');
    }

    /**
     * Ver resultados, estadísticas y ranking de un simulacro
     */
    public function show(Request $request, Exam $exam)
    {
        $query = StudentResult::where('exam_id', $exam->id);

        $selectedGroup = $request->input('group');
        $hasGroupFilter = $selectedGroup && $selectedGroup !== 'all';

        // Filtro por grupo académico
        if ($hasGroupFilter) {
            $query->where('academic_group', $selectedGroup);
        }

        // Filtro por carrera
        if ($request->filled('career') && $request->career !== 'all') {
            $query->where('career', $request->career);
        }

        // Búsqueda por texto (nombre o DNI)
        if ($request->filled('search')) {
            $search = '%'.$request->search.'%';
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', $search)
                    ->orWhere('dni', 'like', $search);
            });
        }

        // Si se filtra por grupo, el orden de mérito principal es por group_rank
        if ($hasGroupFilter) {
            $results = $query->orderBy('group_rank')->paginate(15)->withQueryString();
        } else {
            $results = $query->orderBy('general_rank')->paginate(15)->withQueryString();
        }

        // Estadísticas generales y por grupo
        $allResults = StudentResult::where('exam_id', $exam->id)->get();
        $totalStudents = $allResults->count();

        $statsGroup = $hasGroupFilter ? $allResults->where('academic_group', $selectedGroup) : $allResults;
        $evalCount = $statsGroup->count();
        $avgScore = $evalCount > 0 ? $statsGroup->avg('total_score') : 0;
        $maxScore = $evalCount > 0 ? $statsGroup->max('total_score') : 0;
        $minScore = $evalCount > 0 ? $statsGroup->min('total_score') : 0;

        $groupsCount = [
            'A' => $allResults->where('academic_group', 'A')->count(),
            'BCD' => $allResults->where('academic_group', 'BCD')->count(),
            'EF' => $allResults->where('academic_group', 'EF')->count(),
        ];

        $careersList = StudentResult::where('exam_id', $exam->id)
            ->select('career')
            ->distinct()
            ->orderBy('career')
            ->pluck('career');

        $answerKeys = $exam->answerKeys()->get();
        $keysByGroup = [
            'A' => $exam->answerKeys()->where('academic_group', 'A')->count(),
            'BCD' => $exam->answerKeys()->where('academic_group', 'BCD')->count(),
            'EF' => $exam->answerKeys()->where('academic_group', 'EF')->count(),
            'ALL' => $exam->answerKeys()->where('academic_group', 'ALL')->count(),
        ];
        $subjectColumns = ScoringService::SUBJECT_COLUMNS;
        $scoringRules = $this->scoringService->getScoringRulesMatrix($exam);
        $scoringCategories = collect(ScoringService::SUBJECT_COLUMNS)
            ->mapWithKeys(fn (array $subject, string $code): array => [$code => $subject['name']])
            ->all();
        $scoringGroups = ScoringService::GROUP_LABELS;
        $scoringMaxScores = [];
        foreach (array_keys($scoringGroups) as $groupCode) {
            $scoringMaxScores[$groupCode] = $this->scoringService->getMaximumCorrectScoreForGroup($exam, $groupCode);
        }

        return view('exams.show', compact(
            'exam',
            'results',
            'totalStudents',
            'avgScore',
            'maxScore',
            'minScore',
            'groupsCount',
            'careersList',
            'answerKeys',
            'keysByGroup',
            'subjectColumns',
            'scoringRules',
            'scoringCategories',
            'scoringGroups',
            'scoringMaxScores'
        ));
    }

    /**
     * Subir / Actualizar archivo de claves oficiales (individual o los 3 a la vez)
     */
    public function uploadKeys(Request $request, Exam $exam)
    {
        $request->validate([
            'academic_group' => 'nullable|in:A,BCD,EF,ALL',
            'keys_file_a' => self::EXCEL_FILE_RULE,
            'keys_file_bcd' => self::EXCEL_FILE_RULE,
            'keys_file_ef' => self::EXCEL_FILE_RULE,
            'keys_file' => self::EXCEL_FILE_RULE,
        ]);

        if (! $this->hasAnyFile($request, ['keys_file_a', 'keys_file_bcd', 'keys_file_ef', 'keys_file'])) {
            return back()->with('error', 'Selecciona al menos un archivo de claves para procesar.');
        }

        $importedCount = 0;
        $errors = [];

        // Subida en lote de los 3 archivos de claves
        if ($request->hasFile('keys_file_a')) {
            $importedCount += $this->runImport(fn () => $this->importService->importAnswerKeys($exam, $request->file('keys_file_a')->getRealPath(), 'A'), 'imported_keys', $errors);
        }
        if ($request->hasFile('keys_file_bcd')) {
            $importedCount += $this->runImport(fn () => $this->importService->importAnswerKeys($exam, $request->file('keys_file_bcd')->getRealPath(), 'BCD'), 'imported_keys', $errors);
        }
        if ($request->hasFile('keys_file_ef')) {
            $importedCount += $this->runImport(fn () => $this->importService->importAnswerKeys($exam, $request->file('keys_file_ef')->getRealPath(), 'EF'), 'imported_keys', $errors);
        }

        // Subida de un solo archivo con selector de grupo
        if ($request->hasFile('keys_file')) {
            $group = $request->input('academic_group', 'ALL');
            $importedCount += $this->runImport(fn () => $this->importService->importAnswerKeys($exam, $request->file('keys_file')->getRealPath(), $group), 'imported_keys', $errors);
        }

        if (! empty($errors)) {
            return back()->with('error', implode(' ', $errors));
        }

        // Si ya hay estudiantes importados, recalcular puntajes automáticamente
        if ($exam->studentResults()->count() > 0) {
            $this->recalculateAll($exam);
        }

        return redirect()->route('exams.show', $exam)->with('success', "Se registraron {$importedCount} claves oficiales correctamente.");
    }

    /**
     * Subir / Reemplazar archivo de respuestas de estudiantes (individual o los 3 a la vez)
     */
    public function uploadResponses(Request $request, Exam $exam)
    {
        $request->validate([
            'academic_group' => 'nullable|in:A,BCD,EF',
            'responses_file_a' => self::EXCEL_FILE_RULE,
            'responses_file_bcd' => self::EXCEL_FILE_RULE,
            'responses_file_ef' => self::EXCEL_FILE_RULE,
            'responses_file' => self::EXCEL_FILE_RULE,
        ]);

        if (! $this->hasAnyFile($request, ['responses_file_a', 'responses_file_bcd', 'responses_file_ef', 'responses_file'])) {
            return back()->with('error', 'Selecciona al menos un archivo de respuestas para procesar.');
        }

        $importedCount = 0;
        $errors = [];

        // Subida en lote de los 3 archivos de respuestas
        if ($request->hasFile('responses_file_a')) {
            $importedCount += $this->runImport(fn () => $this->importService->importStudentResponses($exam, $request->file('responses_file_a')->getRealPath(), 'A'), 'imported_students', $errors);
        }
        if ($request->hasFile('responses_file_bcd')) {
            $importedCount += $this->runImport(fn () => $this->importService->importStudentResponses($exam, $request->file('responses_file_bcd')->getRealPath(), 'BCD'), 'imported_students', $errors);
        }
        if ($request->hasFile('responses_file_ef')) {
            $importedCount += $this->runImport(fn () => $this->importService->importStudentResponses($exam, $request->file('responses_file_ef')->getRealPath(), 'EF'), 'imported_students', $errors);
        }

        // Subida de un solo archivo con selector de grupo
        if ($request->hasFile('responses_file')) {
            $group = $request->input('academic_group');
            $importedCount += $this->runImport(fn () => $this->importService->importStudentResponses($exam, $request->file('responses_file')->getRealPath(), $group), 'imported_students', $errors);
        }

        if (! empty($errors)) {
            return back()->with('error', implode(' ', $errors));
        }

        return redirect()->route('exams.show', $exam)->with('success', "Se procesaron {$importedCount} estudiantes y se calculó el orden de mérito consolidado.");
    }

    /**
     * Recalcula todos los puntajes del examen
     */
    public function recalculateAll(Exam $exam)
    {
        $this->recalculateExamResults($exam);

        return redirect()->route('exams.show', $exam)->with('success', 'Puntajes y rankings recalculados exitosamente.');
    }

    /**
     * Actualiza los puntajes por grupo/bloque y recalcula resultados.
     */
    public function updateScoringRules(Request $request, Exam $exam)
    {
        $request->validate([
            'rules' => ['required', 'array'],
            'rules.*' => ['required', 'array'],
            'rules.*.*' => ['required', 'numeric', 'min:0', 'max:1000'],
        ]);

        $this->scoringService->ensureScoringRules($exam);

        foreach (ScoringService::SUBJECT_WEIGHT_CONFIG as $group => $subjects) {
            foreach (array_keys($subjects) as $subjectCode) {
                $points = (float) data_get($request->input('rules'), "{$group}.{$subjectCode}", $subjects[$subjectCode]);

                ExamScoringRule::updateOrCreate(
                    [
                        'exam_id' => $exam->id,
                        'academic_group' => $group,
                        'category' => $subjectCode,
                    ],
                    [
                        'points_correct' => round($points, 4),
                    ]
                );
            }
        }

        $this->recalculateExamResults($exam);

        return redirect()->route('exams.show', $exam)->with('success', 'Puntajes por pregunta actualizados y resultados recalculados.');
    }

    /**
     * Corrige una clave oficial puntual y recalcula resultados.
     */
    public function updateAnswerKey(Request $request, Exam $exam, ExamAnswerKey $answerKey)
    {
        abort_unless($answerKey->exam_id === $exam->id, 404);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:120'],
            'correct_key' => ['required', 'string', 'max:5'],
            'explanation' => ['nullable', 'string', 'max:2000'],
            'is_annulled' => ['nullable', 'boolean'],
        ]);

        $correctKey = strtoupper(trim($validated['correct_key']));
        if (strlen($correctKey) > 1 && preg_match('/^[A-E]/', $correctKey, $matches)) {
            $correctKey = $matches[0];
        }

        $isAnnulled = $request->boolean('is_annulled') || $correctKey === '*';
        if (! $isAnnulled && ! in_array($correctKey, ['A', 'B', 'C', 'D', 'E'], true)) {
            return back()->withErrors([
                'correct_key' => 'La clave debe ser A, B, C, D, E o * para anular.',
            ]);
        }

        $answerKey->update([
            'subject' => trim($validated['subject']),
            'correct_key' => $isAnnulled ? '*' : $correctKey,
            'explanation' => filled($validated['explanation'] ?? null) ? trim($validated['explanation']) : null,
            'is_annulled' => $isAnnulled,
        ]);

        $this->recalculateExamResults($exam);

        return redirect()->route('exams.show', $exam)->with('success', "Clave {$answerKey->academic_group} #{$answerKey->question_number} actualizada y resultados recalculados.");
    }

    /**
     * Exportar resultados en Excel
     */
    public function export(Request $request, Exam $exam)
    {
        return $this->exportService->exportExamResults(
            $exam,
            $request->query('group'),
            $request->query('career')
        );
    }

    /**
     * Exportar resultados en PDF en formato Horizontal (Landscape)
     */
    public function exportPdf(Request $request, Exam $exam)
    {
        return $this->pdfService->exportExamPdf(
            $exam,
            $request->query('group')
        );
    }

    /**
     * Retorna el detalle de respuestas y puntajes de un estudiante en formato JSON para el modal
     */
    public function studentDetail(Exam $exam, StudentResult $student)
    {
        abort_unless($student->exam_id === $exam->id, 404);

        return response()->json([
            'student' => $student,
            'details' => $student->scores_detail_json,
        ]);
    }

    /**
     * Eliminar simulacro
     */
    public function destroy(Exam $exam)
    {
        $exam->delete();

        return redirect()->route('exams.index')->with('success', 'Simulacro eliminado.');
    }

    private function recalculateExamResults(Exam $exam): void
    {
        $results = StudentResult::where('exam_id', $exam->id)->get();
        foreach ($results as $res) {
            $scoreData = $this->scoringService->scoreStudent($exam, $res->answers_json ?? [], $res->career, $res->academic_group);
            $res->update([
                'academic_group' => $scoreData['academic_group'],
                'group_label' => $scoreData['group_label'],
                'correct_count' => $scoreData['correct_count'],
                'incorrect_count' => $scoreData['incorrect_count'],
                'blank_count' => $scoreData['blank_count'],
                'total_score' => $scoreData['total_score'],
                'scores_detail_json' => $scoreData['scores_detail_json'],
            ]);
        }

        $this->scoringService->recalculateRanks($exam);
    }

    private function hasAnyFile(Request $request, array $fields): bool
    {
        foreach ($fields as $field) {
            if ($request->hasFile($field)) {
                return true;
            }
        }

        return false;
    }

    private function runImport(callable $callback, string $countKey, array &$errors): int
    {
        try {
            $result = $callback();
        } catch (Throwable $e) {
            $errors[] = 'No se pudo procesar uno de los archivos. Revisa el formato e inténtalo nuevamente.';

            return 0;
        }

        if (! ($result['success'] ?? false)) {
            $errors[] = $result['message'] ?? 'No se pudo procesar uno de los archivos.';

            return 0;
        }

        return (int) ($result[$countKey] ?? 0);
    }
}
