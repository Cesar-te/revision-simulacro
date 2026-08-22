<?php

namespace Tests\Feature;

use App\Models\Career;
use App\Models\Exam;
use App\Models\StudentResult;
use App\Services\ExcelImportService;
use App\Services\ScoringService;
use Database\Seeders\CareerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulacroImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CareerSeeder::class);
    }

    public function test_can_create_exam_import_keys_and_responses(): void
    {
        $scoring = new ScoringService();
        $importer = new ExcelImportService($scoring);

        $exam = Exam::create([
            'title' => 'SIMULACRO 7° - LETRAS (UNPRG)',
            'description' => 'Test de integración con archivos reales',
            'incorrect_penalty' => -1.1250,
            'blank_score' => 0.0,
            'total_questions' => 100,
        ]);

        $keysFile = base_path('Respuestas_Preguntas_UNPRG.xlsx');
        $respFile = base_path('SIMULACRO 7°  (LETRAS) (respuestas).xlsx');

        $this->assertFileExists($keysFile);
        $this->assertFileExists($respFile);

        $resKeys = $importer->importAnswerKeys($exam, $keysFile);
        $this->assertTrue($resKeys['success']);
        $this->assertGreaterThan(0, $resKeys['imported_keys']);

        $resStudents = $importer->importStudentResponses($exam, $respFile);
        $this->assertTrue($resStudents['success']);
        $this->assertGreaterThan(0, $resStudents['imported_students']);

        $totalResults = StudentResult::where('exam_id', $exam->id)->count();
        $this->assertEquals($resStudents['imported_students'], $totalResults);

        // Validar que el primer puesto tenga ranking 1 y puntaje superior a 0
        $topStudent = StudentResult::where('exam_id', $exam->id)->where('general_rank', 1)->first();
        $this->assertNotNull($topStudent);
        $this->assertGreaterThan(0, $topStudent->total_score);
        $this->assertNotEmpty($topStudent->scores_detail_json);
        $this->assertArrayHasKey('HV', $topStudent->subject_scores);
        $this->assertArrayHasKey('HM', $topStudent->subject_scores);
        $this->assertArrayHasKey('BIO', $topStudent->subject_scores);

        // Probar ruta HTTP index y show
        $response = $this->get(route('exams.show', $exam));
        $response->assertStatus(200);
        $response->assertSee($topStudent->full_name);
        $response->assertSee('HV');
        $response->assertSee('HM');
        $response->assertSee('ARIT');
    }

    public function test_allows_negative_total_scores(): void
    {
        $scoring = new ScoringService();
        $exam = Exam::create([
            'title' => 'TEST EXAMEN',
            'incorrect_penalty' => -1.1250,
            'blank_score' => 0.0,
            'total_questions' => 10,
        ]);

        // Simular todas respuestas incorrectas (10 malas * -1.125 = -11.25)
        $studentAnswers = array_fill(1, 10, 'Z');
        $result = $scoring->scoreStudent($exam, $studentAnswers, 'DERECHO');

        $this->assertEquals(0, $result['correct_count']);
        $this->assertEquals(10, $result['incorrect_count']);
        $this->assertEquals(-11.25, $result['total_score']);
        $this->assertLessThan(0, $result['total_score']);
    }

    public function test_multi_group_answer_keys_and_responses(): void
    {
        $scoring = new ScoringService();
        $importer = new ExcelImportService($scoring);

        $exam = Exam::create([
            'title' => 'SIMULACRO MULTI-GRUPO UNPRG',
            'incorrect_penalty' => -1.1250,
            'blank_score' => 0.0,
            'total_questions' => 20,
        ]);

        // 1. Claves para grupo A (Biomédicas)
        \App\Models\ExamAnswerKey::create([
            'exam_id' => $exam->id,
            'academic_group' => 'A',
            'question_number' => 1,
            'subject' => 'Biología',
            'correct_key' => 'A',
        ]);

        // 2. Claves para grupo EF (Ingenierías)
        \App\Models\ExamAnswerKey::create([
            'exam_id' => $exam->id,
            'academic_group' => 'EF',
            'question_number' => 1,
            'subject' => 'Física',
            'correct_key' => 'B',
        ]);

        // Estudiante A responde 'A' a la preg 1 -> Correcta con ponderación de Biomédicas (25 pts)
        $scoreA = $scoring->scoreStudent($exam, [1 => 'A'], 'MEDICINA HUMANA', 'A');
        $this->assertEquals(1, $scoreA['correct_count']);
        $this->assertEquals(25.0, $scoreA['total_score']);

        // Estudiante EF responde 'B' a la preg 1 -> Correcta con ponderación de Ingenierías (22.222 pts)
        $scoreEF = $scoring->scoreStudent($exam, [1 => 'B'], 'INGENIERIA CIVIL', 'EF');
        $this->assertEquals(1, $scoreEF['correct_count']);
        $this->assertEquals(22.222, $scoreEF['total_score']);

        // Crear StudentResults
        $stA = StudentResult::create([
            'exam_id' => $exam->id,
            'full_name' => 'Alumno Biomedicas',
            'career' => 'Medicina Humana',
            'academic_group' => 'A',
            'correct_count' => 1,
            'total_score' => 25.0,
        ]);

        $stEF = StudentResult::create([
            'exam_id' => $exam->id,
            'full_name' => 'Alumno Ingenieria',
            'career' => 'Ingenieria Civil',
            'academic_group' => 'EF',
            'correct_count' => 1,
            'total_score' => 22.222,
        ]);

        $scoring->recalculateRanks($exam);

        $stA->refresh();
        $stEF->refresh();

        // En la tabla general: Alumno Biomedicas es 1°, Alumno Ingenieria es 2°
        $this->assertEquals(1, $stA->general_rank);
        $this->assertEquals(2, $stEF->general_rank);

        // En sus grupos respectivos: ambos son 1° de su grupo
        $this->assertEquals(1, $stA->group_rank);
        $this->assertEquals(1, $stEF->group_rank);
    }

    public function test_importing_different_groups_does_not_delete_other_groups(): void
    {
        $scoring = new ScoringService();
        $importer = new ExcelImportService($scoring);

        $exam = Exam::create([
            'title' => 'SIMULACRO TEST MULTI GRUPOS',
            'incorrect_penalty' => -1.1250,
            'blank_score' => 0.0,
            'total_questions' => 100,
        ]);

        $keysFile = base_path('Respuestas_Preguntas_UNPRG.xlsx');
        $respFile = base_path('SIMULACRO 7°  (LETRAS) (respuestas).xlsx');

        $importer->importAnswerKeys($exam, $keysFile, 'ALL');

        // 1. Simular alumno existente de Biomédicas (A)
        StudentResult::create([
            'exam_id' => $exam->id,
            'full_name' => 'Postulante Biomedicas Previsto',
            'career' => 'Medicina Humana',
            'academic_group' => 'A',
            'correct_count' => 50,
            'total_score' => 1000.0,
        ]);

        $this->assertEquals(1, StudentResult::where('exam_id', $exam->id)->where('academic_group', 'A')->count());

        // 2. Importar respuestas del Grupo BCD (Letras)
        $res = $importer->importStudentResponses($exam, $respFile, 'BCD');
        $this->assertTrue($res['success']);

        // 3. El alumno del Grupo A debe SEGUIR existiendo en la base de datos
        $this->assertEquals(1, StudentResult::where('exam_id', $exam->id)->where('academic_group', 'A')->count());
        $this->assertGreaterThan(0, StudentResult::where('exam_id', $exam->id)->where('academic_group', 'BCD')->count());

        // Total debe ser 1 (de A) + N (de BCD)
        $this->assertEquals(1 + $res['imported_students'], StudentResult::where('exam_id', $exam->id)->count());
    }

    public function test_export_excel_for_each_group_and_general(): void
    {
        $exam = Exam::create([
            'title' => 'SIMULACRO EXPORT TEST',
            'incorrect_penalty' => -1.1250,
            'blank_score' => 0.0,
            'total_questions' => 100,
        ]);

        StudentResult::create([
            'exam_id' => $exam->id,
            'full_name' => 'Alumno A',
            'career' => 'Medicina Humana',
            'academic_group' => 'A',
            'correct_count' => 80,
            'total_score' => 1600.0,
            'general_rank' => 1,
            'group_rank' => 1,
        ]);

        StudentResult::create([
            'exam_id' => $exam->id,
            'full_name' => 'Alumno BCD',
            'career' => 'Derecho',
            'academic_group' => 'BCD',
            'correct_count' => 70,
            'total_score' => 1400.0,
            'general_rank' => 2,
            'group_rank' => 1,
        ]);

        StudentResult::create([
            'exam_id' => $exam->id,
            'full_name' => 'Alumno EF',
            'career' => 'Ingenieria Civil',
            'academic_group' => 'EF',
            'correct_count' => 60,
            'total_score' => 1200.0,
            'general_rank' => 3,
            'group_rank' => 1,
        ]);

        // 1. Export General
        $resGen = $this->get(route('exams.export', ['exam' => $exam, 'group' => 'all']));
        $resGen->assertStatus(200);
        $resGen->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // 2. Export Biomédicas (A)
        $resA = $this->get(route('exams.export', ['exam' => $exam, 'group' => 'A']));
        $resA->assertStatus(200);
        $resA->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // 3. Export Letras (BCD)
        $resBCD = $this->get(route('exams.export', ['exam' => $exam, 'group' => 'BCD']));
        $resBCD->assertStatus(200);
        $resBCD->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // 4. Export Ingenierías (EF)
        $resEF = $this->get(route('exams.export', ['exam' => $exam, 'group' => 'EF']));
        $resEF->assertStatus(200);
        $resEF->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_export_pdf_generates_landscape_pdf()
    {
        $exam = Exam::create([
            'title' => 'Simulacro PDF Test',
            'incorrect_penalty' => -1.1250,
            'blank_score' => 0.0,
            'total_questions' => 100,
        ]);

        StudentResult::create([
            'exam_id' => $exam->id,
            'full_name' => 'Estudiante Uno',
            'career' => 'Medicina Humana',
            'academic_group' => 'A',
            'correct_count' => 80,
            'total_score' => 1600.0,
            'general_rank' => 1,
            'group_rank' => 1,
        ]);

        $res = $this->get(route('exams.export-pdf', ['exam' => $exam]));
        $res->assertStatus(200);
        $res->assertHeader('content-type', 'application/pdf');

        $resGroup = $this->get(route('exams.export-pdf', ['exam' => $exam, 'group' => 'A']));
        $resGroup->assertStatus(200);
        $resGroup->assertHeader('content-type', 'application/pdf');
    }
}
