<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Services\ExcelImportService;
use App\Services\ScoringService;
use Illuminate\Database\Seeder;

class SampleExamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $keysFile = base_path('Respuestas_Preguntas_UNPRG.xlsx');
        $respFile = base_path('SIMULACRO 7°  (LETRAS) (respuestas).xlsx');

        if (!file_exists($keysFile) || !file_exists($respFile)) {
            return;
        }

        $exam = Exam::updateOrCreate(
            ['title' => 'SIMULACRO 7° - LETRAS (UNPRG)'],
            [
                'description' => 'Simulacro oficial de admisión UNPRG con calificación ponderada por grupo académico.',
                'incorrect_penalty' => -1.1250,
                'blank_score' => 0.0000,
                'total_questions' => 100,
            ]
        );

        $scoring = new ScoringService();
        $importer = new ExcelImportService($scoring);

        $importer->importAnswerKeys($exam, $keysFile);
        $importer->importStudentResponses($exam, $respFile);
    }
}
