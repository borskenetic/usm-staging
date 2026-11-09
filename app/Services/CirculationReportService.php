<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BookLog;
use App\Models\FineSetting;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CirculationReportService
{
    /**
     * Same filters as the circulation log screen.
     *
     * @return Builder<BookLog>
     */
    public function transactionQuery(Request $request): Builder
    {
        $logs = BookLog::query()->with(['book', 'student']);

        if ($request->filled('student_id')) {
            $student = Student::find($request->student_id);
            if ($student) {
                $legacyComma = "{$student->lastname}, {$student->firstname}";
                $legacySpace = trim("{$student->firstname} {$student->lastname}");
                $logs->where(function ($q) use ($student, $legacyComma, $legacySpace) {
                    $q->where('student_id', $student->id)
                        ->orWhere('patron_name', $legacyComma)
                        ->orWhere('patron_name', $legacySpace);
                });
            }
        } elseif ($request->filled('filter_patron')) {
            $term = trim((string) $request->filter_patron);
            if ($term !== '') {
                $logs->where(function ($q) use ($term) {
                    $q->where('patron_name', 'like', '%'.$term.'%')
                        ->orWhereHas('student', function ($s) use ($term) {
                            $s->where('firstname', 'like', '%'.$term.'%')
                                ->orWhere('lastname', 'like', '%'.$term.'%')
                                ->orWhere('id_number', 'like', '%'.$term.'%')
                                ->orWhereRaw(
                                    'LOWER(CONCAT(firstname, \' \', lastname)) LIKE ?',
                                    ['%'.strtolower($term).'%']
                                );
                        });
                });
            }
        }

        if ($request->filled('book_title')) {
            $titleTerm = trim((string) $request->book_title);
            $logs->whereHas('book', function ($query) use ($titleTerm) {
                $query->where('title_statement', 'like', '%'.$titleTerm.'%');
            });
        }

        if ($request->filled('start_date')) {
            $logs->whereDate('timestamp', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $logs->whereDate('timestamp', '<=', $request->end_date);
        }

        if ($request->filled('circulation_type') && in_array($request->circulation_type, ['checkout', 'room_use'], true)) {
            if ($request->circulation_type === 'room_use') {
                $logs->where('circulation_type', BookLog::CIRCULATION_ROOM_USE);
            } else {
                $logs->where(function ($q) {
                    $q->where('circulation_type', BookLog::CIRCULATION_CHECKOUT)
                        ->orWhereNull('circulation_type');
                });
            }
        }

        return $logs;
    }

    public function downloadTransactions(Request $request): BinaryFileResponse
    {
        $logs = $this->transactionQuery($request)->latest('id')->get();
        $generated = Carbon::now('Asia/Manila');

        $headers = [
            'Book', 'Copy ID', 'Identifier', 'Call number', 'Patron', 'Patron ID',
            'Status', 'Loan type', 'Checked out', 'Due', 'Renewals', 'Returned',
            'Overdue', 'Days late', 'Fine', 'Sublocation',
        ];

        $rows = $logs->map(fn (BookLog $log) => $this->transactionRow($log))->all();
        $overdue = $logs->filter(fn (BookLog $log) => $this->isPastDue($log))->count();
        $fineTotal = array_sum(array_map(fn (array $row) => (float) ($row[14] ?? 0), $rows));

        $sheet = $this->workbook(
            'Transactions',
            'Circulation transaction report',
            [
                'Generated' => $generated->format('Y-m-d h:i A'),
                'Filters' => $this->filterSummary($request),
                'Records' => (string) $logs->count().'  ·  '.$overdue.' overdue  ·  fine ₱'.number_format($fineTotal, 2),
            ],
            $headers,
            $rows,
        );

        return $this->download($sheet, 'circulation_transactions_'.$generated->format('Y-m-d_His'));
    }

    /**
     * @param  Collection<int, BookLog>  $loans
     */
    public function downloadActiveLoans(Collection $loans): BinaryFileResponse
    {
        $generated = Carbon::now('Asia/Manila');
        $today = $generated->copy()->startOfDay();
        $overdue = $loans->filter(fn (BookLog $loan) => $loan->due_date && $loan->due_date->lt($today))->count();

        $headers = [
            'Patron', 'Patron ID', 'Book', 'Call number', 'Accession', 'Sublocation',
            'Checked out', 'Due', 'Status', 'Days overdue',
        ];

        $rows = $loans->map(function (BookLog $loan) use ($today) {
            $isOverdue = $loan->due_date && $loan->due_date->lt($today);

            return [
                $loan->patronLabel(),
                $loan->student?->id_number ?? '',
                $loan->book?->title_statement ?? 'Unknown',
                $loan->book?->call_number ?? '',
                $loan->book?->accession_no ?? '',
                $loan->book?->section ?? '',
                $loan->timestamp?->timezone('Asia/Manila')->format('Y-m-d h:i A') ?? '',
                $loan->due_date?->format('Y-m-d') ?? '',
                $isOverdue ? 'Overdue' : 'Borrowed',
                $isOverdue ? (int) $loan->due_date->diffInDays($today) : 0,
            ];
        })->all();

        $sheet = $this->workbook(
            'In circulation',
            'Books in circulation',
            [
                'Generated' => $generated->format('Y-m-d h:i A'),
                'Scope' => 'Books currently checked out for use outside the library',
                'Records' => (string) $loans->count().' borrowed  ·  '.$overdue.' overdue',
            ],
            $headers,
            $rows,
        );

        return $this->download($sheet, 'books_in_circulation_'.$generated->format('Y-m-d_His'));
    }

    /**
     * @return list<mixed>
     */
    private function transactionRow(BookLog $log): array
    {
        $isCheckIn = $log->status === 'Checked In';
        $settings = FineSetting::forSection($log->book->section ?? null);
        $isCheckout = ($log->circulation_type ?? BookLog::CIRCULATION_CHECKOUT) === BookLog::CIRCULATION_CHECKOUT;
        $daysLate = 0;
        $fine = null;
        $overdueLabel = '';

        if ($isCheckIn && $log->due_date && $log->returned_date) {
            $whole = (int) floor(max(0, $log->due_date->diffInDays($log->returned_date, false)));
            $daysLate = max(0, $whole - (int) $settings->grace_period_days);
            if ($daysLate > 0) {
                $overdueLabel = 'Overdue';
                $fine = (float) $log->fine_incurred;
            } else {
                $overdueLabel = 'On time';
            }
        } elseif (! $isCheckIn && $log->due_date && $log->due_date->lt(Carbon::now('Asia/Manila')->startOfDay())) {
            $overdueLabel = 'Overdue';
            $daysLate = (int) $log->days_overdue;
            if ($daysLate > 0) {
                $fine = (float) $log->total_fine;
            }
        }

        $renewals = $isCheckout
            ? ((int) ($log->renew_count ?? 0)).'/'.$settings->maxRenewals()
            : '';

        return [
            $log->book?->title_statement ?? 'Unknown',
            $log->book?->copyIdentifierForCirculation() ?? '',
            $log->book?->copyIdentifierTypeLabel() ?? '',
            $log->book?->call_number ?? '',
            $log->patronLabel(),
            $log->student?->id_number ?? '',
            $log->status === 'Checked Out' ? 'Out' : 'In',
            $log->circulationLabel(),
            $log->timestamp?->timezone('Asia/Manila')->format('Y-m-d h:i A') ?? '',
            $log->due_date?->format('Y-m-d') ?? '',
            $renewals,
            $log->returned_date?->timezone('Asia/Manila')->format('Y-m-d h:i A') ?? '',
            $overdueLabel,
            $daysLate > 0 ? $daysLate : '',
            $fine,
            $log->book?->section ?? '',
        ];
    }

    private function isPastDue(BookLog $log): bool
    {
        if (! $log->due_date) {
            return false;
        }

        $compare = $log->returned_date
            ? Carbon::parse($log->returned_date)
            : Carbon::now('Asia/Manila')->startOfDay();

        return $compare->gt($log->due_date);
    }

    private function filterSummary(Request $request): string
    {
        $parts = [];

        if ($request->filled('student_id')) {
            $student = Student::find($request->student_id);
            $parts[] = 'Patron: '.($student ? "{$student->lastname}, {$student->firstname}" : $request->student_id);
        } elseif ($request->filled('filter_patron')) {
            $parts[] = 'Patron: '.trim((string) $request->filter_patron);
        }

        if ($request->filled('book_title')) {
            $parts[] = 'Book: '.trim((string) $request->book_title);
        }

        if ($request->filled('start_date') || $request->filled('end_date')) {
            $parts[] = 'Dates: '.($request->input('start_date') ?: '…').' to '.($request->input('end_date') ?: '…');
        }

        if ($request->input('circulation_type') === 'room_use') {
            $parts[] = 'Loan type: Room use';
        } elseif ($request->input('circulation_type') === 'checkout') {
            $parts[] = 'Loan type: Check out';
        }

        return $parts === [] ? 'None (all transactions)' : implode('  ·  ', $parts);
    }

    /**
     * @param  array<string, string>  $meta
     * @param  list<string>  $headers
     * @param  list<list<mixed>>  $rows
     */
    private function workbook(string $title, string $heading, array $meta, array $headers, array $rows): Spreadsheet
    {
        $sheet = new Spreadsheet;
        $worksheet = $sheet->getActiveSheet();
        $worksheet->setTitle($title);

        $lastColumn = chr(ord('A') + count($headers) - 1);
        $worksheet->setCellValue('A1', $heading);
        $worksheet->mergeCells("A1:{$lastColumn}1");
        $worksheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $line = 2;
        foreach ($meta as $label => $value) {
            $worksheet->setCellValue("A{$line}", $label);
            $worksheet->setCellValue("B{$line}", $value);
            $worksheet->getStyle("A{$line}")->getFont()->setBold(true);
            $line++;
        }

        $headerRow = $line + 1;
        $worksheet->fromArray($headers, null, "A{$headerRow}");
        $worksheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1B5E20']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        if ($rows !== []) {
            $worksheet->fromArray($rows, null, 'A'.($headerRow + 1));
        }

        $worksheet->freezePane('A'.($headerRow + 1));
        $worksheet->setAutoFilter("A{$headerRow}:{$lastColumn}".max($headerRow, $headerRow + count($rows)));

        foreach (range('A', $lastColumn) as $column) {
            $worksheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $sheet;
    }

    private function download(Spreadsheet $spreadsheet, string $basename): BinaryFileResponse
    {
        $fileName = $basename.'.xlsx';
        $filePath = storage_path('app/'.$fileName);
        (new Xlsx($spreadsheet))->save($filePath);
        $spreadsheet->disconnectWorksheets();

        return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
    }
}
