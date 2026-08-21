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

        // Probar ruta HTTP index y show
        $response = $this->get(route('exams.show', $exam));
        $response->assertStatus(200);
        $response->assertSee($topStudent->full_name);
    }
}
