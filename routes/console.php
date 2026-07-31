<?php

use App\Http\Controllers\Api\Mobile\BookReservationController;
use App\Models\Book;
use App\Models\BookLog;
use App\Services\AttendanceSessionService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('books:reset-all-borrowing {--force : Run without confirmation}', function () {
    if (! $this->option('force') && ! $this->confirm('Delete ALL book_logs and set every book copy to Available? This cannot be undone.')) {
        $this->warn('Aborted.');

        return 1;
    }

    $deleted = 0;
    $booksUpdated = 0;

    DB::transaction(function () use (&$deleted, &$booksUpdated) {
        $deleted = BookLog::query()->count();
        BookLog::query()->delete();
        $booksUpdated = Book::query()->update(['availability' => 'Available']);
    });

    $this->info("Removed {$deleted} book log row(s).");
    $this->info("Set availability=Available on {$booksUpdated} book row(s).");

    return 0;
})->purpose('Wipe circulation history (book_logs) and mark all books Available for a clean slate.');

Artisan::command('attendance:close-stale-ins', function (AttendanceSessionService $sessions) {
    $n = $sessions->closeAllStaleOpenIns();
    $this->info("Inserted {$n} automatic end-of-day OUT row(s) (patrons who were still IN from a prior day).");

    return 0;
})->purpose('Auto OUT at end of IN-day for patrons who never scanned OUT (Asia/Manila calendar days).');

Artisan::command('reservations:expire-holds', function () {
    BookReservationController::expireStaleHolds();
    $this->info('Expired stale book reservation holds.');

    return 0;
})->purpose('Expire ready book reservation holds and notify the next patron in queue.');

Schedule::command('reservations:expire-holds')->everyFifteenMinutes();
