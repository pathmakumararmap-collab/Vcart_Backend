<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\PhpWord;

class MovementReportExportService
{
    private const BUCKET_LABELS = [
        'fast' => 'Fast moving',
        'slow' => 'Slow moving',
        'non_moving' => 'Non-moving',
    ];

    /**
     * @param  array<string, mixed>  $report
     * @return array{0: string, 1: string} [absolute file path, download filename]
     */
    public function export(array $report, string $format, string $title): array
    {
        $filenameBase = str_replace(' ', '-', strtolower($title)).'-'.now()->format('Ymd-His');

        return match ($format) {
            'excel' => $this->toExcel($report, $title, $filenameBase),
            'pdf' => $this->toPdf($report, $title, $filenameBase),
            'word' => $this->toWord($report, $title, $filenameBase),
            default => throw new \InvalidArgumentException("Unsupported export format: {$format}"),
        };
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{0: string, 1: string}
     */
    private function toExcel(array $report, string $title, string $filenameBase): array
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Report');

        $sheet->setCellValue('A1', $title);
        $sheet->setCellValue('A2', "Period: {$report['range']['from']} to {$report['range']['to']}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = $this->columnHeaders($report);
        $headerRow = 4;
        foreach ($headers as $index => $label) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue("{$column}{$headerRow}", $label);
        }
        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->getFont()->setBold(true);

        $row = $headerRow + 1;
        foreach ($this->rowsAsArrays($report) as $values) {
            foreach (array_values($values) as $index => $value) {
                $column = Coordinate::stringFromColumnIndex($index + 1);
                $sheet->setCellValue("{$column}{$row}", $value);
            }
            $row++;
        }

        foreach (range(1, count($headers)) as $index) {
            $column = Coordinate::stringFromColumnIndex($index);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $path = storage_path("app/private/reports/{$filenameBase}.xlsx");
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        (new Xlsx($spreadsheet))->save($path);

        return [$path, "{$filenameBase}.xlsx"];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{0: string, 1: string}
     */
    private function toPdf(array $report, string $title, string $filenameBase): array
    {
        $pdf = Pdf::loadView('reports.movement-pdf', [
            'title' => $title,
            'report' => $report,
            'headers' => $this->columnHeaders($report),
            'rows' => $this->rowsAsArrays($report),
            'bucketLabels' => self::BUCKET_LABELS,
        ])->setPaper('a4', 'landscape');

        $path = storage_path("app/private/reports/{$filenameBase}.pdf");
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        file_put_contents($path, $pdf->output());

        return [$path, "{$filenameBase}.pdf"];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{0: string, 1: string}
     */
    private function toWord(array $report, string $title, string $filenameBase): array
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection(['orientation' => 'landscape']);

        $section->addText($title, ['bold' => true, 'size' => 16]);
        $section->addText("Period: {$report['range']['from']} to {$report['range']['to']}", ['size' => 10]);
        $section->addTextBreak();

        $headers = $this->columnHeaders($report);
        $rows = $this->rowsAsArrays($report);

        $tableStyle = ['borderSize' => 6, 'borderColor' => 'CCCCCC', 'cellMargin' => 80];
        $phpWord->addTableStyle('ReportTable', $tableStyle);
        $table = $section->addTable('ReportTable');

        $table->addRow();
        foreach ($headers as $label) {
            $table->addCell(2500)->addText($label, ['bold' => true]);
        }

        foreach ($rows as $values) {
            $table->addRow();
            foreach ($values as $value) {
                $table->addCell(2500)->addText((string) $value);
            }
        }

        $path = storage_path("app/private/reports/{$filenameBase}.docx");
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        $phpWord->save($path, 'Word2007');

        return [$path, "{$filenameBase}.docx"];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<int, string>
     */
    private function columnHeaders(array $report): array
    {
        if ($report['group_by']) {
            return ['Group', 'Products', 'Qty sold', 'Revenue', 'Fast', 'Slow', 'Non-moving'];
        }

        return ['Product', 'SKU', 'Category', 'Brand', 'Qty sold', 'Revenue', 'Status'];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<int, array<int, string|int|float>>
     */
    private function rowsAsArrays(array $report): array
    {
        $rows = $report['rows'] instanceof Collection ? $report['rows'] : collect($report['rows']);

        if ($report['group_by']) {
            return $rows->map(fn ($row) => [
                $row->group_name,
                $row->products_count,
                $row->qty_sold,
                round((float) $row->revenue, 2),
                $row->fast_count,
                $row->slow_count,
                $row->non_moving_count,
            ])->all();
        }

        return $rows->map(fn ($row) => [
            $row->product_name,
            $row->sku,
            $row->category_name ?? '-',
            $row->brand_name ?? '-',
            $row->qty_sold,
            round((float) $row->revenue, 2),
            $row->bucket ? self::BUCKET_LABELS[$row->bucket] ?? $row->bucket : '-',
        ])->all();
    }
}
