<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\SimpleExcel\SimpleExcelWriter;

class ExportController extends Controller
{
    public function exportBooks(Request $request)
    {
        $templatePath = resource_path('templates/library_holdings_export_template.xlsx');
        if (! is_file($templatePath)) {
            abort(500, 'Library holdings export template is missing.');
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        $query = Book::query()->whereNull('archived_at');

        if (in_array($request->input('status'), ['Available', 'Borrowed'], true)) {
            $query->where('availability', $request->input('status'));
        }

        if ($request->filled('program')) {
            $query->whereHas('programs', function ($q) use ($request) {
                $q->where('library_programs.id', $request->program);
            });
        }

        if ($request->filled('year_filter') && $request->filled('year1')) {
            $year1 = (int) $request->year1;
            $year2 = (int) $request->year2;

            switch ($request->year_filter) {
                case 'exact':
                    $query->where('pub_year', $year1);
                    break;
                case 'before':
                    $query->where('pub_year', '<=', $year1);
                    break;
                case 'after':
                    $query->where('pub_year', '>=', $year1);
                    break;
                case 'between':
                    if ($request->filled('year2')) {
                        $query->whereBetween('pub_year', [min($year1, $year2), max($year1, $year2)]);
                    }
                    break;
            }
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('title_statement', 'like', "%{$search}%")
                        ->orWhere('rfid', 'like', "%{$search}%")
                        ->orWhere('main_author', 'like', "%{$search}%")
                        ->orWhere('course', 'like', "%{$search}%")
                        ->orWhere('call_number', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhere('accession_no', 'like', "%{$search}%")
                        ->orWhere('year', 'like', "%{$search}%");
                });
            }
        }

        $row = 2;
        $query->orderBy('call_number')
            ->orderBy('title_statement')
            ->chunk(500, function ($books) use ($sheet, &$row) {
                foreach ($books as $book) {
                    $sheet->setCellValue("A{$row}", $book->call_number);
                    $sheet->setCellValue("B{$row}", $book->barcode);
                    $sheet->setCellValue("C{$row}", $book->title_statement);
                    $sheet->setCellValue("D{$row}", $book->main_author);
                    $sheet->setCellValue("E{$row}", $book->edition);
                    $sheet->setCellValue("F{$row}", $book->pub_year);
                    $sheet->setCellValue("G{$row}", $book->publisher);
                    $sheet->setCellValue("H{$row}", $book->section);
                    $sheet->setCellValue("I{$row}", $book->library_name);
                    $sheet->setCellValue("J{$row}", $book->availability);
                    $row++;
                }
            });

        $fileName = 'library_holdings_'.Carbon::now()->format('Y-m-d_H-i-s').'.xlsx';
        $filePath = storage_path('app/'.$fileName);
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($filePath);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
    }

    public function exportTransactions()
    {
        $fileName = 'transactions_'.Carbon::now()->format('Y-m-d_H-i-s').'.xlsx';
        $filePath = storage_path('app/'.$fileName);

        $writer = SimpleExcelWriter::create($filePath);
        $writer->addRow(['Downloaded At' => Carbon::now()->format('Y-m-d H:i:s')]);
        $writer->addHeader(['Patron Name', 'Barcode', 'Book Title', 'Author', 'RFID', 'Status', 'Loan type', 'Timestamp']);

        BookLog::with(['book', 'student'])->chunk(500, fn ($logs) => collect($logs)->each(fn ($log) => $writer->addRow([
            'Patron Name' => $log->patronLabel(),
            'Barcode' => $log->book->barcode ?? 'N/A',
            'Book Title' => $log->book->title_statement ?? 'Unknown',
            'Author' => $log->book->main_author ?? 'Unknown',
            'RFID' => $log->book->rfid ?? 'N/A',
            'Status' => $log->status,
            'Loan type' => $log->circulationLabel(),
            'Timestamp' => $log->timestamp
                ? Carbon::parse($log->timestamp)->format('Y-m-d H:i:s')
                : '',
        ])
        )
        );

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
