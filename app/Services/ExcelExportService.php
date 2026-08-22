<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\StudentResult;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExportService
{
    /**
     * Genera y descarga un único archivo Excel consolidado con 4 pestañas/hojas:
     * - Hoja 1: GENERAL (Todos los alumnos de todos los grupos)
     * - Hoja 2: BIOMÉDICAS (Grupo A)
     * - Hoja 3: LETRAS (Grupo BCD)
     * - Hoja 4: INGENIERÍAS (Grupo EF)
     */
    public function exportExamResults(Exam $exam, ?string $groupFilter = null, ?string $careerFilter = null): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;

        // 1. Hoja General
        $sheetGeneral = $spreadsheet->getActiveSheet();
        $sheetGeneral->setTitle('GENERAL');
        $this->buildSheetContent($sheetGeneral, $exam, null, $careerFilter, 'GENERAL (TODOS LOS GRUPOS)');

        // 2. Hoja Biomédicas (A)
        $sheetA = $spreadsheet->createSheet();
        $sheetA->setTitle('BIOMEDICAS (A)');
        $this->buildSheetContent($sheetA, $exam, 'A', $careerFilter, 'BIOMÉDICAS (GRUPO A)');

        // 3. Hoja Letras (BCD)
        $sheetBCD = $spreadsheet->createSheet();
        $sheetBCD->setTitle('LETRAS (BCD)');
        $this->buildSheetContent($sheetBCD, $exam, 'BCD', $careerFilter, 'LETRAS Y HUMANIDADES (GRUPO BCD)');

        // 4. Hoja Ingenierías (EF)
        $sheetEF = $spreadsheet->createSheet();
        $sheetEF->setTitle('INGENIERIAS (EF)');
        $this->buildSheetContent($sheetEF, $exam, 'EF', $careerFilter, 'CIENCIAS E INGENIERÍAS (GRUPO EF)');

        // Establecer como activa la hoja del grupo filtrado o la General
        if ($groupFilter === 'A') {
            $spreadsheet->setActiveSheetIndex(1);
        } elseif ($groupFilter === 'BCD') {
            $spreadsheet->setActiveSheetIndex(2);
        } elseif ($groupFilter === 'EF') {
            $spreadsheet->setActiveSheetIndex(3);
        } else {
            $spreadsheet->setActiveSheetIndex(0);
        }

        $fileName = 'Resultados_'.preg_replace('/[^A-Za-z0-9_\-]/', '_', $exam->title).'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Construye el contenido, encabezados y estilos de una hoja específica
     */
    private function buildSheetContent(Worksheet $sheet, Exam $exam, ?string $group, ?string $careerFilter, string $groupTitle): void
    {
        $query = StudentResult::where('exam_id', $exam->id);

        if ($group) {
            $query->where('academic_group', $group)->orderBy('group_rank');
        } else {
            $query->orderBy('general_rank');
        }

        if ($careerFilter && $careerFilter !== 'all') {
            $query->where('career', $careerFilter);
        }

        $results = $query->get();

        // Título del reporte
        $sheet->mergeCells('A1:Y1');
        $sheet->setCellValue('A1', 'CACHIMBOS UNPRG - '.$groupTitle);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color('000000'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // Subtítulo
        $sheet->mergeCells('A2:Y2');
        $sheet->setCellValue('A2', mb_strtoupper($exam->title, 'UTF-8').' | Evaluados: '.$results->count().' alumnos | Penalidad: '.$exam->incorrect_penalty.' pts | Blanco: '.$exam->blank_score.' pts');
        $sheet->getStyle('A2')->getFont()->setSize(10)->setItalic(true)->setColor(new Color('475569'));
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Definir columnas base y de asignaturas
        $subjectCols = ScoringService::SUBJECT_COLUMNS;

        $headerTitles = [
            'N°',
            'NOMBRE Y APELLIDO',
            'DNI',
            'CARRERA',
        ];

        foreach (array_keys($subjectCols) as $code) {
            $headerTitles[] = $code;
        }

        $headerTitles[] = 'BUENAS';
        $headerTitles[] = 'MALAS';
        $headerTitles[] = 'BLANCO';
        $headerTitles[] = 'PUNTS';

        if (! $group) {
            $headerTitles[] = 'GRUPO';
        }

        $lastColLetter = Coordinate::stringFromColumnIndex(count($headerTitles));

        // Escribir encabezados en fila 4
        $colIdx = 1;
        foreach ($headerTitles as $title) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue("{$colLetter}4", $title);
            $colIdx++;
        }

        // Estilos de encabezado verde claro (estilo UNPRG Cachimbo)
        $sheet->getStyle("A4:{$lastColLetter}4")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FF1F2937'], 'size' => 9],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD1E7DD']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF6B7280']]],
        ]);

        // Columna Carrera en azul pastel suave
        $sheet->getStyle('D4')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFCFE2FF']],
        ]);

        $sheet->getRowDimension(4)->setRowHeight(26);

        // Llenar datos
        $rowNum = 5;
        $counter = 1;
        foreach ($results as $r) {
            $subScores = $r->subject_scores;

            $colIdx = 1;
            // A: N° (Si es grupo, muestra group_rank; si es general, muestra general_rank)
            $rankNum = $group ? ($r->group_rank ?: $counter) : $r->general_rank;
            $sheet->setCellValue([$colIdx++, $rowNum], $rankNum);

            // B: Nombre y Apellido
            $sheet->setCellValue([$colIdx++, $rowNum], $r->full_name);

            // C: DNI
            $sheet->setCellValueExplicit([$colIdx++, $rowNum], $r->dni ?: '-', DataType::TYPE_STRING);

            // D: Carrera
            $sheet->setCellValue([$colIdx++, $rowNum], $r->career);

            // E a U: Asignaturas (17 materias)
            foreach (array_keys($subjectCols) as $code) {
                $val = (float) ($subScores[$code] ?? 0.0);
                $sheet->setCellValue([$colIdx++, $rowNum], round($val, 4));
            }

            // Buenas, Malas, Blanco (enteros), PUNTS (float)
            $sheet->setCellValue([$colIdx++, $rowNum], (int) $r->correct_count);
            $sheet->setCellValue([$colIdx++, $rowNum], (int) $r->incorrect_count);
            $sheet->setCellValue([$colIdx++, $rowNum], (int) $r->blank_count);
            $sheet->setCellValue([$colIdx++, $rowNum], round((float) $r->total_score, 4));

            if (! $group) {
                $sheet->setCellValue([$colIdx++, $rowNum], $r->academic_group);
            }

            // Alineaciones por fila
            $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Colorear filas alternadas
            if ($rowNum % 2 == 0) {
                $sheet->getStyle("A{$rowNum}:{$lastColLetter}{$rowNum}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
            }

            $rowNum++;
            $counter++;
        }

        $lastRow = max(5, $rowNum - 1);

        // 1. Formato numérico de 4 decimales exactos (0.0000) y alineación derecha para las 17 asignaturas (E a U)
        $sheet->getStyle("E5:U{$lastRow}")->getNumberFormat()->setFormatCode('0.0000');
        $sheet->getStyle("E5:U{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // 2. Formato para Buenas, Malas y Blanco (V, W, X) centradas
        $sheet->getStyle("V5:X{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // 3. Formato para PUNTS (Columna Y) con 4 decimales y negrita
        $sheet->getStyle("Y5:Y{$lastRow}")->getNumberFormat()->setFormatCode('0.0000');
        $sheet->getStyle("Y5:Y{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("Y5:Y{$lastRow}")->getFont()->setBold(true);

        if (! $group) {
            $sheet->getStyle("Z5:Z{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Bordes de la tabla de datos
        $sheet->getStyle("A4:{$lastColLetter}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('FF94A3B8'));

        // Anchos de columna uniformes y proporcionales
        $sheet->getColumnDimension('A')->setWidth(7);   // N°
        $sheet->getColumnDimension('B')->setWidth(34);  // NOMBRE Y APELLIDO
        $sheet->getColumnDimension('C')->setWidth(13);  // DNI
        $sheet->getColumnDimension('D')->setWidth(30);  // CARRERA

        // Las 17 columnas de asignaturas (E a U) tienen exactamente el MISMO tamaño uniforme
        for ($i = 5; $i <= 21; $i++) {
            $colLetter = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colLetter)->setWidth(11.5);
        }

        // Columnas de totales
        $sheet->getColumnDimension('V')->setWidth(9);   // BUENAS
        $sheet->getColumnDimension('W')->setWidth(9);   // MALAS
        $sheet->getColumnDimension('X')->setWidth(9);   // BLANCO
        $sheet->getColumnDimension('Y')->setWidth(14);  // PUNTS

        if (! $group) {
            $sheet->getColumnDimension('Z')->setWidth(10); // GRUPO
        }
    }
}
