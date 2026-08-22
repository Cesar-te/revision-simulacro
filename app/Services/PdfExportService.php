<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\StudentResult;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class PdfExportService
{
    /**
     * Generar y descargar PDF de resultados en formato horizontal (Landscape)
     *
     * @param Exam $exam
     * @param string|null $group 'A', 'BCD', 'EF' o null para General
     * @return \Illuminate\Http\Response
     */
    public function exportExamPdf(Exam $exam, ?string $group = null)
    {
        $query = StudentResult::where('exam_id', $exam->id);

        if ($group && in_array($group, ['A', 'BCD', 'EF'])) {
            $query->where('academic_group', $group)->orderBy('group_rank');
            $groupTitle = match($group) {
                'A'   => 'CIENCIAS MÉDICAS (BIOMÉDICAS A)',
                'BCD' => 'LETRAS Y HUMANIDADES (BCD)',
                'EF'  => 'CIENCIAS E INGENIERÍAS (EF)',
                default => 'GRUPO ' . $group,
            };
            $fileSlug = 'RESULTADOS_' . $group . '_' . Str::slug($exam->title);
        } else {
            $query->orderBy('general_rank');
            $groupTitle = 'TABLA GENERAL CONSOLIDADA';
            $fileSlug = 'RESULTADOS_GENERAL_' . Str::slug($exam->title);
            $group = null;
        }

        $results = $query->get();
        $subjectCols = ScoringService::SUBJECT_COLUMNS;
        $title = "Resultados - {$groupTitle} - {$exam->title}";

        // Cargar logo en base64 para renderizado seguro en DomPDF
        $logoPath = public_path('images/logo.png');
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }

        $pdf = Pdf::loadView('pdf.exam-results', compact(
            'exam',
            'results',
            'group',
            'groupTitle',
            'subjectCols',
            'title',
            'logoBase64'
        ));

        // Configuración de hoja horizontal A4 y renderizado rápido
        $pdf->setPaper('a4', 'landscape');
        $pdf->setOption([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => true,
            'defaultFont'          => 'sans-serif',
        ]);

        $fileName = $fileSlug . '.pdf';

        return $pdf->download($fileName);
    }
}
