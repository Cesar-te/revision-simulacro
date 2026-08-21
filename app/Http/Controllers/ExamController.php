<?php

namespace App\Http\Controllers;

use App\Models\Career;
use App\Models\Exam;
use App\Models\ExamAnswerKey;
use App\Models\StudentResult;
use App\Services\ExcelExportService;
use App\Services\ExcelImportService;
use App\Services\ScoringService;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    protected ExcelImportService $importService;
    protected ExcelExportService $exportService;
    protected ScoringService $scoringService;

    public function __construct(
        ExcelImportService $importService,
        ExcelExportService $exportService,
        ScoringService $scoringService
    ) {
        $this->importService = $importService;
        $this->exportService = $exportService;
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

        // Si se adjuntó archivo de claves de una vez
        if ($request->hasFile('keys_file')) {
            $path = $request->file('keys_file')->getRealPath();
            $this->importService->importAnswerKeys($exam, $path);
        }

        // Si se adjuntó archivo de respuestas de una vez
        if ($request->hasFile('responses_file')) {
            $path = $request->file('responses_file')->getRealPath();
            $this->importService->importStudentResponses($exam, $path);
        }

        return redirect()->route('exams.show', $exam)->with('success', 'Simulacro creado exitosamente.');
    }

    /**
     * Ver resultados, estadísticas y ranking de un simulacro
     */
    public function show(Request $request, Exam $exam)
    {
        $query = StudentResult::where('exam_id', $exam->id);

        // Filtro por grupo académico
        if ($request->filled('group') && $request->group !== 'all') {
            $query->where('academic_group', $request->group);
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

        $results = $query->orderBy('general_rank')->paginate(50)->withQueryString();

        // Estadísticas generales
        $allResults = StudentResult::where('exam_id', $exam->id)->get();
        $totalStudents = $allResults->count();
        $avgScore = $totalStudents > 0 ? $allResults->avg('total_score') : 0;
        $maxScore = $totalStudents > 0 ? $allResults->max('total_score') : 0;
        $minScore = $totalStudents > 0 ? $allResults->min('total_score') : 0;

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

        return view('exams.show', compact(
            'exam',
            'results',
            'totalStudents',
            'avgScore',
            'maxScore',
            'minScore',
            'groupsCount',
            'careersList',
            'answerKeys'
        ));
    }

    /**
     * Subir / Actualizar archivo de claves oficiales
     */
    public function uploadKeys(Request $request, Exam $exam)
    {
        $request->validate([
            'keys_file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        $res = $this->importService->importAnswerKeys($exam, $request->file('keys_file')->getRealPath());

        // Si ya hay estudiantes importados, recalcular puntajes automáticamente
        if ($exam->studentResults()->count() > 0) {
            $this->recalculateAll($exam);
        }

        return redirect()->route('exams.show', $exam)->with('success', "Se importaron {$res['imported_keys']} claves correctamente.");
    }

    /**
     * Subir / Reemplazar archivo de respuestas de estudiantes
     */
    public function uploadResponses(Request $request, Exam $exam)
    {
        $request->validate([
            'responses_file' => 'required|file|mimes:xlsx,xls,csv|max:30720',
        ]);

        $res = $this->importService->importStudentResponses($exam, $request->file('responses_file')->getRealPath());

        return redirect()->route('exams.show', $exam)->with('success', "Se procesaron {$res['imported_students']} estudiantes y se calcularon sus puntajes.");
    }

    /**
     * Recalcula todos los puntajes del examen
     */
    public function recalculateAll(Exam $exam)
    {
        $results = StudentResult::where('exam_id', $exam->id)->get();
        foreach ($results as $res) {
            $scoreData = $this->scoringService->scoreStudent($exam, $res->answers_json ?? [], $res->career);
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
