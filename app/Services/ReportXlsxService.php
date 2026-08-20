<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class ReportXlsxService
{
    public function create(string $title, string $period, array $headers, iterable $rows, array $moneyColumns = [], array $summary = []): string
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Report')->setShowGridlines(false);
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->mergeCells("A1:{$lastColumn}1")->setCellValue('A1', 'CGM - CAMY GLOBAL MARCKET');
        $sheet->mergeCells("A2:{$lastColumn}2")->setCellValue('A2', $title);
        $sheet->mergeCells("A3:{$lastColumn}3")->setCellValue('A3', $period);
        $sheet->fromArray($headers, null, 'A5');
        $rowNumber = 6;
        foreach ($rows as $row) {
            $column = 1;
            foreach (array_values((array) $row) as $value) {
                $cell = $sheet->getCellByColumnAndRow($column, $rowNumber);
                if (is_int($value) || is_float($value)) {
                    $cell->setValue($value);
                } else {
                    $cell->setValueExplicit((string) ($value ?? ''), DataType::TYPE_STRING);
                }
                $column++;
            }
            $rowNumber++;
        }
        $lastData = $rowNumber - 1;
        if ($summary) {
            $rowNumber++;
            foreach ($summary as $label => $value) {
                $sheet->mergeCells('A'.$rowNumber.':'.\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(max(1, count($headers) - 1)).$rowNumber)->setCellValue('A'.$rowNumber, $label);
                $sheet->setCellValue($lastColumn.$rowNumber, $value);
                $sheet->getStyle("A{$rowNumber}:{$lastColumn}{$rowNumber}")->getFont()->setBold(true);
                $rowNumber++;
            }
        }
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray(['font' => ['bold' => true, 'size' => 19, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17396B']]]);
        $sheet->getStyle("A2:{$lastColumn}2")->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('17396B');
        $sheet->getStyle("A5:{$lastColumn}5")->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17396B']], 'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]]);
        if ($lastData >= 6) {
            $sheet->setAutoFilter("A5:{$lastColumn}{$lastData}");
            $sheet->getStyle("A6:{$lastColumn}{$lastData}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('DDE5EE');
        }
        foreach ($moneyColumns as $column) {
            $sheet->getStyle("{$column}6:{$column}{$rowNumber}")->getNumberFormat()->setFormatCode('"Rs. "#,##0.00;[Red]-"Rs. "#,##0.00');
        }
        foreach (range(1, count($headers)) as $column) {
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        }
        $sheet->freezePane('A6');
        $sheet->getSheetView()->setZoomScale(90);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4)->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1)->setFitToHeight(0)->setPrintArea("A1:{$lastColumn}{$rowNumber}");
        $sheet->getHeaderFooter()->setOddFooter('&LCGM - Camy Global Marcket&CConfidential report&RPage &P of &N');
        $base = tempnam(storage_path('app'), 'report-');
        if ($base === false) throw new RuntimeException('Could not create Excel report.');
        $path = $base.'.xlsx';
        @unlink($base);
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();

        return $path;
    }
}
