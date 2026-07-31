<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\ResolvesMobileStudent;
use App\Http\Controllers\BookController;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookLog;
use App\Models\FineSetting;
use App\Models\Holiday;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BorrowingController extends Controller
{
    use ResolvesMobileStudent;

    public function active(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if ($student instanceof JsonResponse) {
            return $student;
        }

        $logs = $this->activeLoanQuery($student)
            ->with('book:id,title_statement,main_author,call_number,accession_no,barcode')
            ->orderBy('due_date')
            ->get();

        return response()->json([
            'message' => 'Borrowed books retrieved.',
            'data' => $logs->map(fn (BookLog $log) => $this->formatLoan($log))->values(),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if ($student instanceof JsonResponse) {
            return $student;
        }

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $history = BookLog::query()
            ->with('book:id,title_statement,main_author,call_number,accession_no,barcode')
            ->where('student_id', $student->id)
            ->latest('timestamp')
            ->paginate((int) ($validated['per_page'] ?? 10))
            ->withQueryString();

        return response()->json([
            'message' => 'Borrow history retrieved.',
            'data' => $history->getCollection()->map(fn (BookLog $log) => $this->formatLoan($log))->values(),
            'meta' => [
                'current_page' => $history->currentPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
                'last_page' => $history->lastPage(),
            ],
        ]);
    }

    public function limits(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if ($student instanceof JsonResponse) {
            return $student;
        }

        $maxLoans = BookController::MAX_CONCURRENT_BOOK_LOANS_PER_STUDENT;
        $currentLoans = BookLog::countActiveLoansForStudent((int) $student->id);
        $hasOverdue = $this->hasOverdueLoans($student);
        $fineSetting = FineSetting::currentOrDefault();

        return response()->json([
            'message' => 'Borrow limits retrieved.',
            'data' => [
                'max_active_loans' => $maxLoans,
                'current_active_loans' => $currentLoans,
                'remaining_loans' => max(0, $maxLoans - $currentLoans),
                'has_overdue' => $hasOverdue,
                'can_borrow' => $currentLoans < $maxLoans && ! $hasOverdue,
                'reborrow_cooldown_days' => BookController::REBORROW_COOLDOWN_DAYS,
                'fine_settings_configured' => FineSetting::current() !== null,
                'loan_duration_days' => $fineSetting->loan_duration_days,
                'grace_period_days' => $fineSetting->grace_period_days,
            ],
        ]);
    }

    public function submitCart(Request $request): JsonResponse
    {
        return app(BorrowRequestController::class)->submitCart($request);
    }

    private function activeLoanQuery(Student $student)
    {
        $latestIds = DB::table('library_book_logs')
            ->select(DB::raw('MAX(id) as id'))
            ->where('student_id', $student->id)
            ->groupBy('book_id');

        return BookLog::query()
            ->whereIn('id', $latestIds)
            ->where('status', 'Checked Out');
    }

    private function hasOverdueLoans(Student $student): bool
    {
        return $this->activeLoanQuery($student)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', Carbon::now('Asia/Manila')->toDateString())
            ->exists();
    }

    private function reborrowCooldownMessage(int $studentId, int $bookId): ?string
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
        $allowedAt = $returnedAt->copy()->addDays(BookController::REBORROW_COOLDOWN_DAYS);

        if (Carbon::now('Asia/Manila')->lt($allowedAt)) {
            return 'Re-borrow cooldown active until '.$allowedAt->format('Y-m-d').'.';
        }

        return null;
    }

    private function addBusinessDays(Carbon $start, int $days): Carbon
    {
        $holidays = Holiday::query()
            ->pluck('holiday_date')
            ->map(fn ($date) => Carbon::parse($date)->startOfDay()->toDateString());

        $date = $start->copy()->startOfDay();
        $added = 0;

        while ($added < $days) {
            $date->addDay();

            if (! $date->isWeekend() && ! $holidays->contains($date->toDateString())) {
                $added++;
            }
        }

        return $date;
    }

    private function formatLoan(BookLog $log): array
    {
        $book = $log->book;

        return [
            'id' => $log->id,
            'book_id' => $log->book_id,
            'title' => $book?->title_statement,
            'author' => $book?->main_author,
            'call_number' => $book?->call_number,
            'accession_no' => $book?->accession_no,
            'barcode' => $book?->barcode,
            'borrowed_at' => $log->timestamp?->timezone('Asia/Manila')->toDateTimeString(),
            'due_at' => $log->due_date?->format('Y-m-d'),
            'returned_at' => $log->returned_date?->timezone('Asia/Manila')->toDateTimeString(),
            'status' => $log->status,
            'circulation_type' => $log->circulation_type,
            'is_overdue' => (bool) $log->is_overdue,
            'days_overdue' => (int) $log->days_overdue,
            'fine' => (float) $log->total_fine,
            'renew_count' => (int) $log->renew_count,
        ];
    }
}
