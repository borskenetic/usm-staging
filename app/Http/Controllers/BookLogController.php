<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookLog;
use App\Models\FineSetting;
use App\Models\Student;
use App\Services\CirculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookLogController extends Controller
{
    protected function calculateOverdueDays(Carbon $dueDate, Carbon $returnedDate, int $gracePeriod = 0)
    {
        if ($returnedDate->lessThanOrEqualTo($dueDate)) {
            return 0;
        }

        $holidays = \App\Models\Holiday::pluck('holiday_date')->map(function ($d) {
            return Carbon::parse($d)->startOfDay()->toDateString();
        });

        $overdueDays = 0;
        $current = $dueDate->copy()->addDay()->startOfDay();

        while ($current->lessThanOrEqualTo($returnedDate)) {
            $isWeekend = $current->isWeekend();
            $isHoliday = $holidays->contains($current->toDateString());

            if (! $isWeekend && ! $isHoliday) {
                $overdueDays++;
            }

            $current->addDay();
        }

        return max(0, $overdueDays - $gracePeriod);
    }

    protected function addBusinessDays(Carbon $start, int $days)
    {
        $holidays = \App\Models\Holiday::pluck('holiday_date')->map(function ($d) {
            return Carbon::parse($d)->startOfDay()->toDateString();
        });

        $date = $start->copy()->startOfDay();
        $added = 0;

        while ($added < $days) {
            $date->addDay();

            $isWeekend = $date->isWeekend();
            $isHoliday = $holidays->contains($date->toDateString());

            if (! $isWeekend && ! $isHoliday) {
                $added++;
            }
        }

        return $date;
    }

    /**
     * Cooldown: after returning a book, the same student must wait before borrowing the same book again.
     */
    protected function enforceReborrowCooldownOrNull(int $studentId, int $bookId): ?string
    {
        $latestReturn = BookLog::query()
            ->where('student_id', $studentId)
            ->where('book_id', $bookId)
            ->where('status', 'Checked In')
            ->whereNotNull('returned_date')
            ->orderByDesc('returned_date')
            ->value('returned_date');

        if (! $latestReturn) {
            return null;
        }

        $returnedAt = Carbon::parse($latestReturn)->timezone('Asia/Manila');
        $allowedAt = $returnedAt->copy()->addDays(BookController::reborrowCooldownDays());
        $now = Carbon::now('Asia/Manila');

        if ($now->lt($allowedAt)) {
            return 'This patron must wait '.BookController::reborrowCooldownDays().' days after returning this book before borrowing it again. (Available again on '.$allowedAt->format('M j, Y').')';
        }

        return null;
    }

    public function index(Request $request)
    {
        $logs = BookLog::with(['book', 'student']);

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

        $logs = $logs->latest()->paginate(10);

        $prefillPatronLabel = '';
        if ($request->filled('student_id')) {
            $ps = Student::find($request->student_id);
            if ($ps) {
                $prefillPatronLabel = $this->patronDisplayLabel($ps);
            }
        } elseif ($request->filled('filter_patron')) {
            $prefillPatronLabel = trim((string) $request->filter_patron);
        }

        $filterBookTitle = trim((string) $request->input('book_title', ''));

        $prefillCopyIdentifier = trim((string) $request->input(
            'copy_identifier',
            $request->input('rfid', '')
        ));

        return view('books.logs', compact(
            'logs',
            'prefillPatronLabel',
            'filterBookTitle',
            'prefillCopyIdentifier',
        ));
    }

    public function store(Request $request)
    {
        $copyCode = trim((string) ($request->input('copy_identifier') ?: $request->input('rfid')));

        $request->validate([
            'copy_identifier' => 'nullable|string|max:255',
            'rfid' => 'nullable|string|max:255',
            'status' => 'required|string|in:checked_out,room_use,checked_in',
            'student_id' => 'required|integer|exists:library_students,id',
        ]);

        if ($copyCode === '') {
            return back()->withInput()->with('error', 'Enter the copy accession number, barcode, or RFID.');
        }

        $book = Book::findByCopyIdentifier($copyCode);

        if (! $book) {
            return back()->withInput()->with(
                'error',
                'No copy found for that code. Use accession number (recommended), barcode, or RFID.'
            );
        }

        $student = Student::findOrFail($request->student_id);
        $patronName = "{$student->lastname}, {$student->firstname}";

        $action = $request->status;
        $isOutbound = in_array($action, ['checked_out', 'room_use'], true);

        $lastLog = BookLog::where('book_id', $book->id)
            ->latest('timestamp')
            ->first();

        if ($isOutbound && $lastLog && $lastLog->status === 'Checked Out') {
            return back()->with('error', 'This book is already on loan (check in first).');
        }

        if ($action === 'checked_in' && (! $lastLog || $lastLog->status !== 'Checked Out')) {
            return back()->with('error', 'This book is already checked in.');
        }

        if ($action === 'checked_in') {
            try {
                $result = app(CirculationService::class)->checkInBook($book, $student);
            } catch (\RuntimeException $e) {
                return back()->with('error', $e->getMessage());
            }

            if ($result['overdue_modal']) {
                session()->flash('overdue_modal', $result['overdue_modal']);
            }

            return back()->with('success', 'Book has been Checked In successfully!');
        }

        if ($isOutbound) {
            $cooldownError = $this->enforceReborrowCooldownOrNull((int) $student->id, (int) $book->id);
            if ($cooldownError) {
                return back()->with('error', $cooldownError);
            }

            $active = BookLog::countActiveLoansForStudent((int) $student->id);
            if ($active >= BookController::maxConcurrentLoansPerStudent()) {
                return back()->with(
                    'error',
                    'This patron already has the maximum of '.BookController::maxConcurrentLoansPerStudent().' books on loan (including room use). Check one in first, or use check out only for books taken outside the library.'
                );
            }
        }

        $newStatus = $isOutbound ? 'Checked Out' : 'Checked In';
        $book->availability = $isOutbound ? 'Borrowed' : 'Available';

        $circulationType = BookLog::CIRCULATION_CHECKOUT;
        if ($isOutbound && $action === 'room_use') {
            $circulationType = BookLog::CIRCULATION_ROOM_USE;
        }

        $settings = FineSetting::currentOrDefault();

        $dueDate = null;
        $returnedDate = null;
        $fineIncurred = null;

        if ($isOutbound && $action === 'checked_out') {
            $loanDays = $settings->studentLoanDurationDays();
            $dueDate = $this->addBusinessDays(Carbon::now('Asia/Manila'), $loanDays);
        }

        BookLog::create([
            'book_id' => $book->id,
            'student_id' => $student->id,
            'patron_name' => $patronName,
            'status' => $newStatus,
            'circulation_type' => $circulationType,
            'renew_count' => 0,
            'timestamp' => Carbon::now('Asia/Manila'),
            'due_date' => $dueDate,
            'returned_date' => $returnedDate,
            'fine_incurred' => $fineIncurred,
        ]);

        $book->save();

        if ($action === 'room_use') {
            return back()->with('success', 'Room use recorded (in library only). Remind the patron to check in when finished.');
        }

        return back()->with('success', "Book has been {$newStatus} successfully!");
    }

    public function renew(Request $request, Book $book)
    {
        $request->validate([
            'student_id' => 'required|integer|exists:library_students,id',
        ]);

        $studentId = (int) $request->student_id;

        $lastLog = BookLog::query()
            ->where('book_id', $book->id)
            ->latest('timestamp')
            ->first();

        if (! $lastLog || $lastLog->status !== 'Checked Out') {
            return back()->with('error', 'This book is not currently checked out.');
        }

        if ((int) $lastLog->student_id !== $studentId) {
            return back()->with('error', 'Only the patron who borrowed this book can renew it.');
        }

        if (($lastLog->circulation_type ?? BookLog::CIRCULATION_CHECKOUT) !== BookLog::CIRCULATION_CHECKOUT) {
            return back()->with('error', 'Room-use loans cannot be renewed.');
        }

        if (! $lastLog->due_date) {
            return back()->with('error', 'This loan has no due date to renew.');
        }

        $renewCount = (int) ($lastLog->renew_count ?? 0);
        if ($renewCount >= BookController::maxRenewalsPerLoan()) {
            return back()->with('error', 'Renewal limit reached (max '.BookController::maxRenewalsPerLoan().' renewals).');
        }

        $settings = FineSetting::currentOrDefault();
        $loanDays = (int) $settings->studentLoanDurationDays();

        $base = Carbon::parse($lastLog->due_date, 'Asia/Manila');
        $newDue = $this->addBusinessDays($base, $loanDays);

        $lastLog->due_date = $newDue;
        $lastLog->renew_count = $renewCount + 1;
        $lastLog->last_renewed_at = Carbon::now('Asia/Manila');
        $lastLog->save();

        return back()->with('success', 'Loan renewed. New due date: '.$newDue->format('Y-m-d').'. ('.$lastLog->renew_count.'/'.BookController::maxRenewalsPerLoan().' renewals used)');
    }

    protected function patronDisplayLabel(Student $s): string
    {
        $label = "{$s->lastname}, {$s->firstname}";
        if ($s->id_number) {
            $label .= " ({$s->id_number})";
        }

        return $label;
    }

    public function bookTitleLogSuggestions(Request $request)
    {
        $search = trim((string) $request->get('query', ''));
        if ($search === '') {
            return response()->json([]);
        }

        $titles = Book::query()
            ->whereHas('logs')
            ->where('title_statement', 'like', '%'.$search.'%')
            ->whereNotNull('title_statement')
            ->orderBy('title_statement')
            ->pluck('title_statement')
            ->unique()
            ->take(12)
            ->values();

        return response()->json($titles->map(fn ($title) => ['title' => $title]));
    }

    public function patronSuggestions(Request $request)
    {
        $search = $request->get('query', '');

        $suggestions = Student::where(function ($q) use ($search) {
            $q->where('firstname', 'LIKE', "%{$search}%")
                ->orWhere('lastname', 'LIKE', "%{$search}%")
                ->orWhere('id_number', 'LIKE', "%{$search}%")
                ->orWhereRaw(
                    'LOWER(CONCAT(firstname, \' \', lastname)) LIKE ?',
                    ['%'.strtolower($search).'%']
                );
        })
            ->limit(10)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $this->patronDisplayLabel($s),
            ]);

        return response()->json($suggestions);
    }

    public function bookSuggestions(Request $request)
    {
        $search = $request->get('query', '');

        $books = Book::whereNull('archived_at')->where(function ($q) use ($search) {
            $q->where('title_statement', 'LIKE', "%{$search}%")
                ->orWhere('main_author', 'LIKE', "%{$search}%")
                ->orWhere('accession_no', 'LIKE', "%{$search}%")
                ->orWhere('rfid', 'LIKE', "%{$search}%")
                ->orWhere('barcode', 'LIKE', "%{$search}%");
        })
            ->limit(10)
            ->get();

        return response()->json(
            $books->map(function ($b) {
                $lastCheckout = BookLog::with('student')
                    ->where('book_id', $b->id)
                    ->where('status', 'Checked Out')
                    ->latest('timestamp')
                    ->first();

                return [
                    'id' => $b->id,
                    'title' => $b->title_statement,
                    'author' => $b->main_author,
                    'accession_no' => $b->accession_no,
                    'barcode' => $b->barcode,
                    'rfid' => $b->rfid,
                    'copy_identifier' => $b->copyIdentifierForCirculation(),
                    'copy_identifier_summary' => $b->copyIdentifierSummary(),
                    'availability' => $b->availability,
                    'last_student_id' => $lastCheckout?->student_id,
                    'last_patron' => $lastCheckout ? $lastCheckout->patronLabel() : null,
                    'last_circulation_type' => $lastCheckout?->circulation_type,
                ];
            })
        );
    }
}
