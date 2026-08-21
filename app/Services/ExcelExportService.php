<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\StudentResult;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExportService
{
    /**
     * Genera y descarga el archivo Excel con el orden de mérito de los postulantes
     */
    public function exportExamResults(Exam $exam, ?string $groupFilter = null, ?string $careerFilter = null): StreamedResponse
    {
        $query = StudentResult::where('exam_id', $exam->id);

        if ($groupFilter && $groupFilter !== 'all') {
            $query->where('academic_group', $groupFilter);
        }

        if ($careerFilter && $careerFilter !== 'all') {
            $query->where('career', $careerFilter);
        }

        $results = $query->orderBy('general_rank')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Orden de Mérito');

        // Título del reporte
        $sheet->mergeCells('A1:L1');
        $sheet->setCellValue('A1', 'RESULTADOS GENERALES - ' . mb_strtoupper($exam->title, 'UTF-8'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E293B');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Subtítulo
        $sheet->mergeCells('A2:L2');
        $sheet->setCellValue('A2', 'Total Evaluados: ' . $results->count() . ' | Puntaje Máximo: 2000.0000 | Penalidad: ' . $exam->incorrect_penalty . ' pts');
        $sheet->getStyle('A2')->getFont()->setSize(10)->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('475569'));
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Encabezados de tabla
        $headers = [
            'A3' => 'Pto. Gen.',
            'B3' => 'Pto. Carr.',
            'C3' => 'DNI',
            'D3' => 'Apellidos y Nombres',
            'E3' => 'Carrera del Postulante',
            'F3' => 'Grupo Académico',
            'G3' => 'Buenas',
            'H3' => 'Malas',
            'I3' => 'Blanco',
            'J3' => 'Puntaje Total',
            'K3' => 'Correo Electrónico',
            'L3' => 'Fecha / Hora Envío',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF334155']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
        ];
        $sheet->getStyle('A3:L3')->applyFromArray($headerStyle);
        $sheet->getRowDimension(3)->setRowHeight(25);

        // Llenar datos
        $rowNum = 4;
        foreach ($results as $r) {
            $sheet->setCellValue("A{$rowNum}", $r->general_rank);
            $sheet->setCellValue("B{$rowNum}", $r->career_rank);
            $sheet->setCellValue("C{$rowNum}", $r->dni ?: '-');
            $sheet->setCellValue("D{$rowNum}", $r->full_name);
            $sheet->setCellValue("E{$rowNum}", $r->career);
            $sheet->setCellValue("F{$rowNum}", $r->group_label ?? $r->academic_group);
            $sheet->setCellValue("G{$rowNum}", $r->correct_count);
            $sheet->setCellValue("H{$rowNum}", $r->incorrect_count);
            $sheet->setCellValue("I{$rowNum}", $r->blank_count);
            $sheet->setCellValue("J{$rowNum}", number_format($r->total_score, 4, '.', ''));
            $sheet->setCellValue("K{$rowNum}", $r->email ?: '-');
            $sheet->setCellValue("L{$rowNum}", $r->submitted_at ? $r->submitted_at->format('d/m/Y H:i:s') : '-');

            // Formato de celdas numéricas y alineación
            $sheet->getStyle("A{$rowNum}:C{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F{$rowNum}:I{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("J{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("J{$rowNum}")->getFont()->setBold(true);

            // Colorear filas alternadas
            if ($rowNum % 2 == 0) {
                $sheet->getStyle("A{$rowNum}:L{$rowNum}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
            }

            $rowNum++;
        }

        // Bordes de la tabla de datos
        $lastRow = max(4, $rowNum - 1);
        $sheet->getStyle("A4:L{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFE2E8F0'));

        // Autoajustar ancho de columnas
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'Resultados_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $exam->title) . '_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
