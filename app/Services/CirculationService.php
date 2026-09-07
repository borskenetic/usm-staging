<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Controllers\Api\Mobile\BookReservationController;
use App\Http\Controllers\BookController;
use App\Models\Book;
use App\Models\BookLog;
use App\Models\BookReservation;
use App\Models\FineSetting;
use App\Models\Holiday;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CirculationService
{
    public function isAvailableForBorrow(Book $book): bool
    {
        return $book->archived_at === null
            && $book->availability === Book::AVAILABILITY_AVAILABLE;
    }

    public function canPatronBorrow(Student $student, Book $book): ?string
    {
        if ($book->archived_at !== null) {
            return 'Book is not available for checkout.';
        }

        if ($book->availability === Book::AVAILABILITY_ON_HOLD) {
            return 'This copy is on hold for another patron.';
        }

        if ($book->availability !== Book::AVAILABILITY_AVAILABLE) {
            return 'Book is not available.';
        }

        if ($this->hasOverdueLoans($student)) {
            return 'Checkout blocked: student has overdue book(s).';
        }

        $cooldown = $this->reborrowCooldownMessage((int) $student->id, (int) $book->id);
        if ($cooldown !== null) {
            return $cooldown;
        }

        $currentLoans = BookLog::countActiveLoansForStudent((int) $student->id);
        if ($currentLoans >= BookController::maxConcurrentLoansPerStudent()) {
            return 'Checkout blocked: patron may have at most '
                .BookController::maxConcurrentLoansPerStudent().' books on loan at a time.';
        }

        return null;
    }

    public function checkoutBook(Student $student, Book $book, ?User $staff = null): BookLog
    {
        $reason = $this->canPatronBorrow($student, $book);
        if ($reason !== null) {
            throw new \RuntimeException($reason);
        }

        $fineSetting = FineSetting::currentOrDefault();
        $borrowedAt = Carbon::now('Asia/Manila');
        $patronName = "{$student->lastname}, {$student->firstname}";
        $dueDate = $this->addBusinessDays($borrowedAt, (int) $fineSetting->studentLoanDurationDays());

        return DB::transaction(function () use ($student, $book, $borrowedAt, $patronName, $dueDate) {
            $lockedBook = Book::query()->whereKey($book->id)->lockForUpdate()->firstOrFail();

            if (! $this->isAvailableForBorrow($lockedBook)) {
                throw new \RuntimeException('Book is no longer available for checkout.');
            }

            $log = BookLog::query()->create([
                'book_id' => $lockedBook->id,
                'student_id' => $student->id,
                'patron_name' => $patronName,
                'status' => 'Checked Out',
                'circulation_type' => BookLog::CIRCULATION_CHECKOUT,
                'renew_count' => 0,
                'timestamp' => $borrowedAt,
                'due_date' => $dueDate,
                'fine_incurred' => 0,
            ]);

            $lockedBook->update(['availability' => Book::AVAILABILITY_BORROWED]);

            return $log->load('book');
        });
    }

    public function checkoutHeldBook(Student $student, Book $book, BookReservation $reservation): BookLog
    {
        if ($reservation->status !== BookReservation::STATUS_READY) {
            throw new \RuntimeException('Reservation is not ready for desk claim.');
        }

        if ((int) $reservation->student_id !== (int) $student->id) {
            throw new \RuntimeException('This hold belongs to another patron.');
        }

        if ((int) $reservation->held_book_id !== (int) $book->id) {
            throw new \RuntimeException('This copy is not held for this reservation.');
        }

        if ($reservation->hold_expires_at && Carbon::now('Asia/Manila')->gt($reservation->hold_expires_at)) {
            throw new \RuntimeException('This reservation hold has expired.');
        }

        if ($this->hasOverdueLoans($student)) {
            throw new \RuntimeException('Checkout blocked: student has overdue book(s).');
        }

        $fineSetting = FineSetting::currentOrDefault();
        $borrowedAt = Carbon::now('Asia/Manila');
        $patronName = "{$student->lastname}, {$student->firstname}";
        $dueDate = $this->addBusinessDays($borrowedAt, (int) $fineSetting->studentLoanDurationDays());

        return DB::transaction(function () use ($student, $book, $borrowedAt, $patronName, $dueDate) {
            $lockedBook = Book::query()->whereKey($book->id)->lockForUpdate()->firstOrFail();

            if ($lockedBook->availability !== Book::AVAILABILITY_ON_HOLD) {
                throw new \RuntimeException('This copy is no longer on hold.');
            }

            $log = BookLog::query()->create([
                'book_id' => $lockedBook->id,
                'student_id' => $student->id,
                'patron_name' => $patronName,
                'status' => 'Checked Out',
                'circulation_type' => BookLog::CIRCULATION_CHECKOUT,
                'renew_count' => 0,
                'timestamp' => $borrowedAt,
                'due_date' => $dueDate,
                'fine_incurred' => 0,
            ]);

            $lockedBook->update(['availability' => Book::AVAILABILITY_BORROWED]);

            return $log->load('book');
        });
    }

    public function releaseHold(Book $book): void
    {
        if ($book->availability === Book::AVAILABILITY_ON_HOLD) {
            $book->update(['availability' => Book::AVAILABILITY_AVAILABLE]);
        }
    }

    public function placeHold(Book $book): void
    {
        if ($book->availability === Book::AVAILABILITY_AVAILABLE) {
            $book->update(['availability' => Book::AVAILABILITY_ON_HOLD]);
        }
    }

    /**
     * @return array{log: BookLog, overdue_modal: ?array<string, mixed>}
     */
    public function checkInBook(Book $book, Student $student): array
    {
        $lastLog = BookLog::query()
            ->where('book_id', $book->id)
            ->latest('timestamp')
            ->first();

        if (! $lastLog || $lastLog->status !== 'Checked Out') {
            throw new \RuntimeException('This book is already checked in.');
        }

        if ((int) $lastLog->student_id !== (int) $student->id) {
            throw new \RuntimeException('Patron must match the student who has this book.');
        }

        $settings = FineSetting::currentOrDefault();
        $returnedDate = Carbon::now('Asia/Manila');
        $dueDate = $lastLog->due_date;
        $fineIncurred = null;
        $overdueModal = null;
        $patronName = "{$student->lastname}, {$student->firstname}";

        if ($dueDate) {
            $gracePeriod = (int) $settings->grace_period_days;
            $finePerDay = (float) $settings->fine_per_day;
            $maxFine = $settings->max_fine;

            $overdueDays = $this->calculateOverdueDays(
                Carbon::parse($dueDate)->startOfDay(),
                $returnedDate->copy()->startOfDay(),
                $gracePeriod
            );

            $fineIncurred = $overdueDays * $finePerDay;

            if ($overdueDays > 0) {
                $overdueModal = [
                    'book_title' => $book->title_statement,
                    'patron_name' => $patronName,
                    'days_late' => $overdueDays,
                    'fine' => $fineIncurred,
                    'breakdown' => "{$overdueDays} day(s) × ₱".number_format($finePerDay, 2).' = ₱'.number_format($fineIncurred, 2),
                ];
            }

            if (! is_null($maxFine) && $fineIncurred !== null) {
                $fineIncurred = min($fineIncurred, $maxFine);
            }
        }

        $log = BookLog::query()->create([
            'book_id' => $book->id,
            'student_id' => $student->id,
            'patron_name' => $patronName,
            'status' => 'Checked In',
            'circulation_type' => $lastLog->circulation_type ?? BookLog::CIRCULATION_CHECKOUT,
            'renew_count' => 0,
            'timestamp' => $returnedDate,
            'due_date' => $dueDate,
            'returned_date' => $returnedDate,
            'fine_incurred' => $fineIncurred,
        ]);

        $book->update(['availability' => Book::AVAILABILITY_AVAILABLE]);

        BookReservationController::fulfilNextInQueue($book->fresh());

        return [
            'log' => $log->load(['book', 'student']),
            'overdue_modal' => $overdueModal,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, BookLog>
     */
    public function activeCheckoutLoans()
    {
        $latestIds = DB::table('library_book_logs')
            ->select(DB::raw('MAX(id) as id'))
            ->groupBy('book_id');

        return BookLog::query()
            ->whereIn('id', $latestIds)
            ->where('status', 'Checked Out')
            ->where('circulation_type', BookLog::CIRCULATION_CHECKOUT)
            ->with(['book', 'student'])
            ->orderBy('due_date')
            ->get();
    }

    protected function calculateOverdueDays(Carbon $dueDate, Carbon $returnedDate, int $gracePeriod = 0): int
    {
        if ($returnedDate->lessThanOrEqualTo($dueDate)) {
            return 0;
        }

        $holidays = Holiday::query()
            ->pluck('holiday_date')
            ->map(fn ($date) => Carbon::parse($date)->startOfDay()->toDateString());

        $overdueDays = 0;
        $current = $dueDate->copy()->addDay()->startOfDay();

        while ($current->lessThanOrEqualTo($returnedDate)) {
            if (! $current->isWeekend() && ! $holidays->contains($current->toDateString())) {
                $overdueDays++;
            }

            $current->addDay();
        }

        return max(0, $overdueDays - $gracePeriod);
    }

    public function hasOverdueLoans(Student $student): bool
    {
        $latestIds = DB::table('library_book_logs')
            ->select(DB::raw('MAX(id) as id'))
            ->where('student_id', $student->id)
            ->groupBy('book_id');

        return BookLog::query()
            ->whereIn('id', $latestIds)
            ->where('status', 'Checked Out')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', Carbon::now('Asia/Manila')->toDateString())
            ->exists();
    }

    public function reborrowCooldownMessage(int $studentId, int $bookId): ?string
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

        if (Carbon::now('Asia/Manila')->lt($allowedAt)) {
            return 'Re-borrow cooldown active until '.$allowedAt->format('Y-m-d').'.';
        }

        return null;
    }

    public function addBusinessDays(Carbon $start, int $days): Carbon
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
}
