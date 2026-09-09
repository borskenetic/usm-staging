<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LibraryHoldingsReport1ExcelWriter
{
    protected Spreadsheet $spreadsheet;

    public function __construct(
        protected string $heiName,
        protected string $programName,
        protected ?string $dateAccomplished,
        protected array $detail,
        protected array $summary,
    ) {
        $this->spreadsheet = new Spreadsheet;
    }

    public function saveTo(string $path): void
    {
        $this->build();
        (new Xlsx($this->spreadsheet))->save($path);
    }

    public function build(): Spreadsheet
    {
        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->setTitle('Report 1');

        $this->applyColumnWidths($sheet);

        $sheet->setCellValue('A1', 'HEI Name: '.$this->heiName);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(10);

        $dateLine = $this->dateAccomplished
            ? 'Date Accomplished: '.$this->dateAccomplished
            : 'Date Accomplished:________________________________________________';
        $sheet->setCellValue('A2', $dateLine);
        $sheet->getStyle('A2')->getFont()->setSize(10);

        $sheet->setCellValue('A3', 'Program Name: '.mb_strtoupper($this->programName));
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(10);

        $sheet->setCellValue('A5', 'Library Collection');
        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(12);
        $sheet->setCellValue('J5', 'SUMMARY OF REPORT');
        $sheet->getStyle('J5')->getFont()->setBold(true)->setSize(12);

        $sheet->setCellValue(
            'A6',
            'List of 5 non- duplicated book titles per course in the curriculum published within the last 5 years'
        );
        $sheet->getStyle('A6')->getFont()->setSize(10);

        $summaryHeaders = [
            'J7' => 'Gen.Ed./Prof.Ed.',
            'K7' => 'Course Name',
            'L7' => 'No. of Titles/Subject',
            'M7' => 'No. of Titles Published within the last 5 years',
            'N7' => 'No. of Copies',
        ];
        foreach ($summaryHeaders as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }

        $detailHeaders = [
            'A8' => 'No.',
            'B8' => 'Collection Type',
            'C8' => 'Gen.Ed./Prof.Ed.',
            'D8' => 'Course Name',
            'E8' => 'Book Title',
            'F8' => 'Author',
            'G8' => 'Publication Year',
            'H8' => 'No. of Book Copies',
        ];
        foreach ($detailHeaders as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }

        foreach (['J7:N7', 'A8:H8'] as $range) {
            $sheet->getStyle($range)->applyFromArray([
                'font' => ['bold' => true, 'size' => 10],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'CFE2F3'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
            ]);
        }

        foreach (['J7:J8', 'K7:K8', 'L7:L8', 'M7:M8', 'N7:N8'] as $merge) {
            $sheet->mergeCells($merge);
        }

        $startRow = 9;
        $detailCount = count($this->detail);
        $summaryCount = count($this->summary);
        $rowCount = max($detailCount, $summaryCount);

        for ($i = 0; $i < $rowCount; $i++) {
            $row = $startRow + $i;

            if (isset($this->detail[$i])) {
                $line = $this->detail[$i];
                $sheet->setCellValue("A{$row}", $i + 1);
                $sheet->setCellValue("B{$row}", $line['collection_type']);
                $sheet->setCellValue("C{$row}", $line['curriculum_label']);
                $sheet->setCellValue("D{$row}", $line['course']);
                $sheet->setCellValue("E{$row}", $line['title']);
                $sheet->setCellValue("F{$row}", $line['author']);
                $sheet->setCellValue("G{$row}", $line['pub_year'] ?? '');
                $sheet->setCellValue("H{$row}", $line['copy_count']);
            }

            if (isset($this->summary[$i])) {
                $summary = $this->summary[$i];
                $sheet->setCellValue("J{$row}", $summary['curriculum_label']);
                $sheet->setCellValue("K{$row}", $summary['course']);
                $sheet->setCellValue("L{$row}", $summary['title_count']);
                $sheet->setCellValue("M{$row}", $summary['recent_title_count']);
                $sheet->setCellValue("N{$row}", $summary['copy_count']);
            }

            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($this->dataStyle());
            $sheet->getStyle("J{$row}:N{$row}")->applyFromArray($this->dataStyle());
        }

        if ($rowCount > 0) {
            $lastDataRow = $startRow + $rowCount - 1;
            $sheet->getStyle("A{$startRow}:H{$lastDataRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle("J{$startRow}:N{$lastDataRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle("A{$startRow}:A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$startRow}:H{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("L{$startRow}:N{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $noteRow = $startRow + $rowCount + 2;
        $sheet->setCellValue(
            "A{$noteRow}",
            'Note: Start by presenting the book holdings according to the sequence of the CHED-contents noted curriculum'
        );

        $sigRow = $noteRow + 2;
        $nameRow = $sigRow + 1;
        $labelRow = $sigRow + 2;
        $titleRow = $sigRow + 3;

        $preparedName = config('reports.signatories.prepared_by_name', '');
        $approvedName = config('reports.signatories.approved_by_name', '');
        $preparedTitle = config('reports.signatories.prepared_by_title', 'College Librarian');
        $approvedTitle = config('reports.signatories.approved_by_title', 'HEI Head');

        $sheet->setCellValue("A{$sigRow}", 'Prepared by:');
        $sheet->setCellValue("E{$sigRow}", 'Approved by:');
        $sheet->setCellValue("J{$sigRow}", 'Prepared by:');
        $sheet->setCellValue("M{$sigRow}", 'Approved by:');

        $sheet->setCellValue("A{$nameRow}", $preparedName);
        $sheet->setCellValue("E{$nameRow}", $approvedName);
        $sheet->setCellValue("J{$nameRow}", $preparedName);
        $sheet->setCellValue("M{$nameRow}", $approvedName);

        $sheet->setCellValue("A{$labelRow}", 'Name');
        $sheet->setCellValue("E{$labelRow}", 'Name');
        $sheet->setCellValue("J{$labelRow}", 'Name');
        $sheet->setCellValue("M{$labelRow}", 'Name');

        $sheet->setCellValue("A{$titleRow}", $preparedTitle);
        $sheet->setCellValue("E{$titleRow}", $approvedTitle);
        $sheet->setCellValue("J{$titleRow}", $preparedTitle);
        $sheet->setCellValue("M{$titleRow}", $approvedTitle);

        return $this->spreadsheet;
    }

    protected function dataStyle(): array
    {
        return [
            'font' => ['size' => 10],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
            'alignment' => [
                'wrapText' => true,
            ],
        ];
    }

    protected function applyColumnWidths(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        $widths = [
            'A' => 5.71,
            'B' => 19.29,
            'C' => 23.43,
            'D' => 18.86,
            'E' => 32.71,
            'F' => 24.57,
            'G' => 14.0,
            'H' => 16.43,
            'I' => 13.0,
            'J' => 14.14,
            'K' => 11.71,
            'L' => 17.0,
            'M' => 20.71,
            'N' => 13.0,
        ];

        foreach ($widths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }
}
