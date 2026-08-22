<?php

namespace App\Http\Controllers;

use App\Models\Career;
use App\Models\Exam;
use App\Models\ExamAnswerKey;
use App\Models\StudentResult;
use App\Services\ExcelExportService;
use App\Services\ExcelImportService;
use App\Services\PdfExportService;
use App\Services\ScoringService;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    protected ExcelImportService $importService;
    protected ExcelExportService $exportService;
    protected PdfExportService $pdfService;
    protected ScoringService $scoringService;

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
            'incorrect_penalty' => 'nullable|numeric',
            'blank_score' => 'nullable|numeric',
            'total_questions' => 'nullable|integer|min:1|max:200',
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

        // 1. Claves en lote (A, BCD, EF) o individuales
        if ($request->hasFile('keys_file_a')) {
            $this->importService->importAnswerKeys($exam, $request->file('keys_file_a')->getRealPath(), 'A');
        }
        if ($request->hasFile('keys_file_bcd')) {
            $this->importService->importAnswerKeys($exam, $request->file('keys_file_bcd')->getRealPath(), 'BCD');
        }
        if ($request->hasFile('keys_file_ef')) {
            $this->importService->importAnswerKeys($exam, $request->file('keys_file_ef')->getRealPath(), 'EF');
        }
        if ($request->hasFile('keys_file')) {
            $groupKeys = $request->input('academic_group_keys', 'ALL');
            $this->importService->importAnswerKeys($exam, $request->file('keys_file')->getRealPath(), $groupKeys);
        }

        // 2. Respuestas en lote (A, BCD, EF) o individuales
        if ($request->hasFile('responses_file_a')) {
            $this->importService->importStudentResponses($exam, $request->file('responses_file_a')->getRealPath(), 'A');
        }
        if ($request->hasFile('responses_file_bcd')) {
            $this->importService->importStudentResponses($exam, $request->file('responses_file_bcd')->getRealPath(), 'BCD');
        }
        if ($request->hasFile('responses_file_ef')) {
            $this->importService->importStudentResponses($exam, $request->file('responses_file_ef')->getRealPath(), 'EF');
        }
        if ($request->hasFile('responses_file')) {
            $groupResp = $request->input('academic_group_responses', null);
            $this->importService->importStudentResponses($exam, $request->file('responses_file')->getRealPath(), $groupResp);
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
            $search = '%' . $request->search . '%';
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
            'A'   => $allResults->where('academic_group', 'A')->count(),
            'BCD' => $allResults->where('academic_group', 'BCD')->count(),
            'EF'  => $allResults->where('academic_group', 'EF')->count(),
        ];

        $careersList = StudentResult::where('exam_id', $exam->id)
            ->select('career')
            ->distinct()
            ->orderBy('career')
            ->pluck('career');

        $answerKeys = $exam->answerKeys()->get();
        $keysByGroup = [
            'A'   => $exam->answerKeys()->where('academic_group', 'A')->count(),
            'BCD' => $exam->answerKeys()->where('academic_group', 'BCD')->count(),
            'EF'  => $exam->answerKeys()->where('academic_group', 'EF')->count(),
            'ALL' => $exam->answerKeys()->where('academic_group', 'ALL')->count(),
        ];
        $subjectColumns = \App\Services\ScoringService::SUBJECT_COLUMNS;

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
            'subjectColumns'
        ));
    }

    /**
     * Subir / Actualizar archivo de claves oficiales (individual o los 3 a la vez)
     */
    public function uploadKeys(Request $request, Exam $exam)
    {
        $importedCount = 0;

        // Subida en lote de los 3 archivos de claves
        if ($request->hasFile('keys_file_a')) {
            $resA = $this->importService->importAnswerKeys($exam, $request->file('keys_file_a')->getRealPath(), 'A');
            $importedCount += $resA['imported_keys'];
        }
        if ($request->hasFile('keys_file_bcd')) {
            $resBCD = $this->importService->importAnswerKeys($exam, $request->file('keys_file_bcd')->getRealPath(), 'BCD');
            $importedCount += $resBCD['imported_keys'];
        }
        if ($request->hasFile('keys_file_ef')) {
            $resEF = $this->importService->importAnswerKeys($exam, $request->file('keys_file_ef')->getRealPath(), 'EF');
            $importedCount += $resEF['imported_keys'];
        }

        // Subida de un solo archivo con selector de grupo
        if ($request->hasFile('keys_file')) {
            $group = $request->input('academic_group', 'ALL');
            $res = $this->importService->importAnswerKeys($exam, $request->file('keys_file')->getRealPath(), $group);
            $importedCount += $res['imported_keys'];
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
        $importedCount = 0;

        // Subida en lote de los 3 archivos de respuestas
        if ($request->hasFile('responses_file_a')) {
            $resA = $this->importService->importStudentResponses($exam, $request->file('responses_file_a')->getRealPath(), 'A');
            $importedCount += $resA['imported_students'];
        }
        if ($request->hasFile('responses_file_bcd')) {
            $resBCD = $this->importService->importStudentResponses($exam, $request->file('responses_file_bcd')->getRealPath(), 'BCD');
            $importedCount += $resBCD['imported_students'];
        }
        if ($request->hasFile('responses_file_ef')) {
            $resEF = $this->importService->importStudentResponses($exam, $request->file('responses_file_ef')->getRealPath(), 'EF');
            $importedCount += $resEF['imported_students'];
        }

        // Subida de un solo archivo con selector de grupo
        if ($request->hasFile('responses_file')) {
            $group = $request->input('academic_group');
            $res = $this->importService->importStudentResponses($exam, $request->file('responses_file')->getRealPath(), $group);
            $importedCount += $res['imported_students'];
        }

        return redirect()->route('exams.show', $exam)->with('success', "Se procesaron {$importedCount} estudiantes y se calculó el orden de mérito consolidado.");
    }

    /**
     * Recalcula todos los puntajes del examen
     */
    public function recalculateAll(Exam $exam)
    {
        $results = StudentResult::where('exam_id', $exam->id)->get();
        foreach ($results as $res) {
            $scoreData = $this->scoringService->scoreStudent($exam, $res->answers_json ?? [], $res->career, $res->academic_group);
            $res->update([
                'academic_group'     => $scoreData['academic_group'],
                'group_label'        => $scoreData['group_label'],
                'correct_count'      => $scoreData['correct_count'],
                'incorrect_count'    => $scoreData['incorrect_count'],
                'blank_count'        => $scoreData['blank_count'],
                'total_score'        => $scoreData['total_score'],
                'scores_detail_json' => $scoreData['scores_detail_json'],
            ]);
        }

        $this->scoringService->recalculateRanks($exam);

        return redirect()->route('exams.show', $exam)->with('success', 'Puntajes y rankings recalculados exitosamente.');
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
}
