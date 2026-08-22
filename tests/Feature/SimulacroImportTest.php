<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAnswerKey;
use App\Models\StudentResult;
use App\Models\User;
use App\Services\ExcelImportService;
use App\Services\ScoringService;
use Database\Seeders\CareerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class SimulacroImportTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CareerSeeder::class);
        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    public function test_can_create_exam_import_keys_and_responses(): void
    {
        $scoring = new ScoringService;
        $importer = new ExcelImportService($scoring);

        $exam = Exam::create([
            'title' => 'SIMULACRO 7 - LETRAS (UNPRG)',
            'description' => 'Test de integracion con archivos generados',
            'incorrect_penalty' => -1.1250,
            'blank_score' => 0.0,
            'total_questions' => 100,
        ]);

        $resKeys = $importer->importAnswerKeys($exam, $this->makeAnswerKeysFile(), 'ALL');
        $this->assertTrue($resKeys['success']);
        $this->assertEquals(100, $resKeys['imported_keys']);

        $resStudents = $importer->importStudentResponses($exam, $this->makeResponsesFile(), 'BCD');
        $this->assertTrue($resStudents['success']);
        $this->assertEquals(2, $resStudents['imported_students']);

        $topStudent = StudentResult::where('exam_id', $exam->id)->where('general_rank', 1)->first();
        $this->assertNotNull($topStudent);
        $this->assertGreaterThan(0, $topStudent->total_score);
        $this->assertNotEmpty($topStudent->scores_detail_json);
        $this->assertArrayHasKey('HV', $topStudent->subject_scores);
        $this->assertArrayHasKey('HM', $topStudent->subject_scores);
        $this->assertArrayHasKey('BIO', $topStudent->subject_scores);

        $response = $this->get(route('exams.show', $exam));
        $response->assertStatus(200);
        $response->assertSee($topStudent->full_name);
        $response->assertSee('HV');
        $response->assertSee('HM');
        $response->assertSee('ARIT');
    }

    public function test_allows_negative_total_scores(): void
    {
        $scoring = new ScoringService;
        $exam = Exam::create([
            'title' => 'TEST EXAMEN',
            'incorrect_penalty' => -1.1250,
            'blank_score' => 0.0,
            'total_questions' => 10,
        ]);

        $studentAnswers = array_fill(1, 10, 'Z');
        $result = $scoring->scoreStudent($exam, $studentAnswers, 'DERECHO');

        $this->assertEquals(0, $result['correct_count']);
        $this->assertEquals(10, $result['incorrect_count']);
        $this->assertEquals(-11.25, $result['total_score']);
        $this->assertLessThan(0, $result['total_score']);
    }

    public function test_multi_group_answer_keys_and_responses(): void
    {
        $scoring = new ScoringService;

        $exam = Exam::create([
            'title' => 'SIMULACRO MULTI-GRUPO UNPRG',
            'incorrect_penalty' => -1.1250,
            'blank_score' => 0.0,
            'total_questions' => 20,
        ]);

        ExamAnswerKey::create([
            'exam_id' => $exam->id,
            'academic_group' => 'A',
            'question_number' => 1,
            'subject' => 'Biologia',
            'correct_key' => 'A',
        ]);

        ExamAnswerKey::create([
            'exam_id' => $exam->id,
            'academic_group' => 'EF',
            'question_number' => 1,
            'subject' => 'Fisica',
            'correct_key' => 'B',
        ]);

        $scoreA = $scoring->scoreStudent($exam, [1 => 'A'], 'MEDICINA HUMANA', 'A');
        $this->assertEquals(1, $scoreA['correct_count']);
        $this->assertEquals(25.0, $scoreA['total_score']);
        $this->assertEquals('A', $scoreA['academic_group']);

        $scoreEF = $scoring->scoreStudent($exam, [1 => 'B'], 'DERECHO', 'EF');
        $this->assertEquals(1, $scoreEF['correct_count']);
        $this->assertEquals(22.222, $scoreEF['total_score']);
        $this->assertEquals('EF', $scoreEF['academic_group']);

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

        $this->assertEquals(1, $stA->fresh()->general_rank);
        $this->assertEquals(2, $stEF->fresh()->general_rank);
        $this->assertEquals(1, $stA->fresh()->group_rank);
        $this->assertEquals(1, $stEF->fresh()->group_rank);
    }

    public function test_importing_different_groups_does_not_delete_other_groups(): void
    {
        $importer = new ExcelImportService(new ScoringService);

        $exam = Exam::create([
            'title' => 'SIMULACRO TEST MULTI GRUPOS',
            'incorrect_penalty' => -1.1250,
            'blank_score' => 0.0,
            'total_questions' => 100,
        ]);

        $importer->importAnswerKeys($exam, $this->makeAnswerKeysFile(), 'ALL');

        StudentResult::create([
            'exam_id' => $exam->id,
            'full_name' => 'Postulante Biomedicas Previsto',
            'career' => 'Medicina Humana',
            'academic_group' => 'A',
            'correct_count' => 50,
            'total_score' => 1000.0,
        ]);

        $res = $importer->importStudentResponses($exam, $this->makeResponsesFile(), 'BCD');

        $this->assertTrue($res['success']);
        $this->assertEquals(1, StudentResult::where('exam_id', $exam->id)->where('academic_group', 'A')->count());
        $this->assertGreaterThan(0, StudentResult::where('exam_id', $exam->id)->where('academic_group', 'BCD')->count());
        $this->assertEquals(1 + $res['imported_students'], StudentResult::where('exam_id', $exam->id)->count());
    }

    public function test_same_dni_with_different_names_is_imported_as_separate_rows(): void
    {
        $importer = new ExcelImportService(new ScoringService);

        $exam = Exam::create([
            'title' => 'SIMULACRO DUPLICADOS',
            'incorrect_penalty' => -1.1250,
            'blank_score' => 0.0,
            'total_questions' => 3,
        ]);

        $importer->importAnswerKeys($exam, $this->makeAnswerKeysFile(3), 'BCD');
        $res = $importer->importStudentResponses($exam, $this->makeResponsesFile(3, [
            ['Uno Test', '70000000', 'Derecho', ['A', 'A', 'A']],
            ['Otro Test', '70000000', 'Derecho', ['A', 'B', 'A']],
        ]), 'BCD');

        $this->assertTrue($res['success']);
        $this->assertEquals(2, StudentResult::where('exam_id', $exam->id)->where('dni', '70000000')->count());
    }

    public function test_student_detail_cannot_cross_exam_boundaries(): void
    {
        $exam = Exam::create(['title' => 'Exam A']);
        $otherExam = Exam::create(['title' => 'Exam B']);
        $student = StudentResult::create([
            'exam_id' => $otherExam->id,
            'full_name' => 'Alumno Externo',
            'career' => 'Derecho',
            'academic_group' => 'BCD',
        ]);

        $this->get(route('exams.student-detail', [$exam, $student]))->assertNotFound();
    }

    public function test_export_excel_for_each_group_and_general(): void
    {
        $exam = $this->makeExamWithResults();

        $this->get(route('exams.export', ['exam' => $exam, 'group' => 'all']))
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->get(route('exams.export', ['exam' => $exam, 'group' => 'A']))
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->get(route('exams.export', ['exam' => $exam, 'group' => 'BCD']))
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->get(route('exams.export', ['exam' => $exam, 'group' => 'EF']))
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_export_pdf_generates_landscape_pdf(): void
    {
        $exam = $this->makeExamWithResults();

        $this->get(route('exams.export-pdf', ['exam' => $exam]))
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');

        $this->get(route('exams.export-pdf', ['exam' => $exam, 'group' => 'A']))
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');
    }

    private function makeExamWithResults(): Exam
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

        return $exam;
    }

    private function makeAnswerKeysFile(int $questions = 100): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Respuestas');
        $sheet->fromArray(['N.', 'Area', 'Tema', 'Clave'], null, 'A1');

        $subjects = [
            'Habilidad Verbal',
            'Habilidad Matematica',
            'Aritmetica',
            'Geometria',
            'Algebra',
            'Trigonometria',
            'Lenguaje',
            'Literatura',
            'Psicologia',
            'Educacion Civica',
            'Historia',
            'Geografia',
            'Economia',
            'Filosofia',
            'Fisica',
            'Quimica',
            'Biologia',
        ];

        for ($i = 1; $i <= $questions; $i++) {
            $sheet->setCellValue('A'.($i + 1), $i);
            $sheet->setCellValue('B'.($i + 1), $subjects[($i - 1) % count($subjects)]);
            $sheet->setCellValue('D'.($i + 1), 'A');
        }

        return $this->writeSpreadsheet($spreadsheet, 'keys');
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: string, 3: array<int, string>}>|null  $students
     */
    private function makeResponsesFile(int $questions = 100, ?array $students = null): string
    {
        $students ??= [
            ['Alumno Correcto', '70000001', 'Derecho', array_fill(0, $questions, 'A')],
            ['Alumno Regular', '70000002', 'Derecho', array_fill(0, $questions, 'B')],
        ];

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Marca temporal');
        $sheet->setCellValue('B1', 'Correo');
        $sheet->setCellValue('D1', 'Nombre y apellidos');
        $sheet->setCellValue('E1', 'DNI');
        $sheet->setCellValue('F1', 'Carrera');

        for ($i = 1; $i <= $questions; $i++) {
            $column = Coordinate::stringFromColumnIndex(6 + $i);
            $sheet->setCellValue("{$column}1", "Habilidad Verbal [PREGUNTA {$i}]");
        }

        foreach ($students as $index => [$name, $dni, $career, $answers]) {
            $row = $index + 2;
            $sheet->setCellValue("A{$row}", now()->toDateTimeString());
            $sheet->setCellValue("B{$row}", strtolower(str_replace(' ', '.', $name)).'@example.test');
            $sheet->setCellValue("D{$row}", $name);
            $sheet->setCellValue("E{$row}", $dni);
            $sheet->setCellValue("F{$row}", $career);

            for ($i = 1; $i <= $questions; $i++) {
                $column = Coordinate::stringFromColumnIndex(6 + $i);
                $sheet->setCellValue("{$column}{$row}", $answers[$i - 1] ?? '');
            }
        }

        return $this->writeSpreadsheet($spreadsheet, 'responses');
    }

    private function writeSpreadsheet(Spreadsheet $spreadsheet, string $prefix): string
    {
        $dir = storage_path('framework/testing/excel');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $path = $dir.'/'.uniqid($prefix.'_', true).'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $this->tempFiles[] = $path;

        return $path;
    }
}
