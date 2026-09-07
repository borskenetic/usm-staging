<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\ResolvesMobileStudent;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookReservation;
use App\Models\Setting;
use App\Models\StudentNotification;
use App\Models\User;
use App\Services\AdminActivityLogger;
use App\Services\CirculationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookReservationController extends Controller
{
    use ResolvesMobileStudent;

    public function store(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if ($student instanceof JsonResponse) {
            return $student;
        }

        $validated = $request->validate([
            'book_id' => ['required', 'integer', 'exists:library_books,id'],
        ]);

        $book = Book::query()
            ->whereNull('archived_at')
            ->findOrFail($validated['book_id']);

        $copies = $this->bookGroupCopies($book);

        if ($copies->contains(fn (Book $copy) => $copy->availability === Book::AVAILABILITY_AVAILABLE)) {
            return response()->json([
                'message' => 'This book has available copies — no reservation needed.',
                'data' => null,
            ], 409);
        }

        $existing = BookReservation::query()
            ->where('student_id', $student->id)
            ->whereIn('book_id', $copies->pluck('id'))
            ->whereIn('status', [BookReservation::STATUS_PENDING, BookReservation::STATUS_READY])
            ->exists();

        if ($existing) {
            return response()->json([
                'message' => 'You already have an active reservation for this book.',
                'data' => null,
            ], 409);
        }

        $queuePosition = BookReservation::query()
            ->whereIn('book_id', $copies->pluck('id'))
            ->where('status', BookReservation::STATUS_PENDING)
            ->count() + 1;

        $reservation = BookReservation::query()->create([
            'student_id' => $student->id,
            'book_id' => $book->id,
            'status' => BookReservation::STATUS_PENDING,
            'queue_position' => $queuePosition,
            'reserved_at' => Carbon::now('Asia/Manila'),
        ]);

        $title = $book->title_statement ?? 'Reserved book';
        StudentNotification::query()->create([
            'student_id' => $student->id,
            'type' => 'book_reservation_pending',
            'title' => 'Reservation queued',
            'message' => "You are #{$queuePosition} in the queue for \"{$title}\".",
        ]);

        app(AdminActivityLogger::class)->bookReservationPending(
            $reservation,
            "{$student->lastname}, {$student->firstname}",
            (string) $title,
        );

        return response()->json([
            'message' => "Book reserved. You are #{$queuePosition} in the queue.",
            'data' => $this->formatReservation($reservation->load(['book', 'heldBook'])),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if ($student instanceof JsonResponse) {
            return $student;
        }

        $reservations = BookReservation::query()
            ->where('student_id', $student->id)
            ->latest('reserved_at')
            ->get()
            ->map(fn (BookReservation $reservation) => $this->formatReservation(
                $reservation->load(['book', 'heldBook'])
            ));

        return response()->json([
            'message' => 'Book reservations retrieved.',
            'data' => $reservations,
        ]);
    }

    public function show(Request $request, BookReservation $reservation): JsonResponse
    {
        $owned = $this->ownedReservation($request, $reservation);

        if ($owned instanceof JsonResponse) {
            return $owned;
        }

        return response()->json([
            'message' => 'Book reservation retrieved.',
            'data' => $this->formatReservation($owned->load(['book', 'heldBook'])),
        ]);
    }

    public function destroy(Request $request, BookReservation $reservation): JsonResponse
    {
        $owned = $this->ownedReservation($request, $reservation);

        if ($owned instanceof JsonResponse) {
            return $owned;
        }

        if (! in_array($owned->status, [BookReservation::STATUS_PENDING, BookReservation::STATUS_READY], true)) {
            return response()->json([
                'message' => 'Only active reservations can be cancelled.',
                'data' => null,
            ], 409);
        }

        $wasReady = $owned->status === BookReservation::STATUS_READY;
        $heldBook = $owned->heldBook;
        $title = $owned->book?->title_statement ?? 'Reserved book';

        $owned->update([
            'status' => BookReservation::STATUS_CANCELLED,
            'cancelled_at' => Carbon::now('Asia/Manila'),
        ]);

        if ($wasReady && $heldBook) {
            app(CirculationService::class)->releaseHold($heldBook);
            self::fulfilNextInQueue($heldBook->fresh());
        }

        $this->recalculateQueuePositions($owned->book);

        StudentNotification::query()->create([
            'student_id' => $owned->student_id,
            'type' => 'book_reservation_cancelled',
            'title' => 'Reservation cancelled',
            'message' => "Your reservation for \"{$title}\" was cancelled.",
        ]);

        return response()->json([
            'message' => 'Reservation cancelled.',
            'data' => $this->formatReservation($owned->load(['book', 'heldBook'])),
        ]);
    }

    public static function fulfilNextInQueue(Book $returnedBook): void
    {
        $copies = self::bookGroupCopiesStatic($returnedBook);

        $nextReservation = BookReservation::query()
            ->whereIn('book_id', $copies->pluck('id'))
            ->where('status', BookReservation::STATUS_PENDING)
            ->orderBy('reserved_at')
            ->first();

        if (! $nextReservation) {
            return;
        }

        $now = Carbon::now('Asia/Manila');
        $holdExpires = $now->copy()->addDays(Setting::reservationHoldDays());

        app(CirculationService::class)->placeHold($returnedBook);

        $nextReservation->update([
            'status' => BookReservation::STATUS_READY,
            'held_book_id' => $returnedBook->id,
            'available_at' => $now,
            'hold_expires_at' => $holdExpires,
        ]);

        self::recalculateQueuePositionsStatic($returnedBook);

        $title = $returnedBook->title_statement ?? 'Reserved book';
        StudentNotification::query()->create([
            'student_id' => $nextReservation->student_id,
            'type' => 'book_reservation_ready',
            'title' => 'Reserved book available',
            'message' => "Your reserved book \"{$title}\" is ready. Visit the library desk to claim it before {$holdExpires->format('M j, Y H:i')}.",
        ]);
    }

    public static function expireStaleHolds(): void
    {
        $expired = BookReservation::query()
            ->with('heldBook')
            ->where('status', BookReservation::STATUS_READY)
            ->where('hold_expires_at', '<', Carbon::now('Asia/Manila'))
            ->get();

        foreach ($expired as $reservation) {
            $heldBook = $reservation->heldBook;
            $title = $reservation->book?->title_statement ?? 'Reserved book';

            if ($heldBook) {
                app(CirculationService::class)->releaseHold($heldBook);
            }

            $reservation->update([
                'status' => BookReservation::STATUS_EXPIRED,
                'held_book_id' => null,
            ]);

            StudentNotification::query()->create([
                'student_id' => $reservation->student_id,
                'type' => 'book_reservation_expired',
                'title' => 'Reservation expired',
                'message' => "Your reservation for \"{$title}\" expired because it was not claimed in time.",
            ]);

            if ($heldBook) {
                self::fulfilNextInQueue($heldBook->fresh());
            }
        }
    }

    public static function fulfillAtDesk(BookReservation $reservation, User $staff): BookReservation
    {
        if ($reservation->status !== BookReservation::STATUS_READY) {
            throw new \RuntimeException('Only ready reservations can be fulfilled at the desk.');
        }

        $student = $reservation->student;
        $heldBook = $reservation->heldBook;

        if (! $student || ! $heldBook) {
            throw new \RuntimeException('Reservation is missing patron or held copy.');
        }

        return DB::transaction(function () use ($reservation, $staff, $student, $heldBook) {
            app(CirculationService::class)->checkoutHeldBook($student, $heldBook, $reservation);

            $now = Carbon::now('Asia/Manila');
            $title = $reservation->book?->title_statement ?? 'Reserved book';

            $reservation->update([
                'status' => BookReservation::STATUS_FULFILLED,
                'fulfilled_at' => $now,
                'fulfilled_by' => $staff->id,
            ]);

            StudentNotification::query()->create([
                'student_id' => $student->id,
                'type' => 'book_reservation_fulfilled',
                'title' => 'Reservation fulfilled',
                'message' => "You checked out your reserved book \"{$title}\".",
            ]);

            return $reservation->fresh(['book', 'heldBook', 'student']);
        });
    }

    public static function staffCancel(BookReservation $reservation, User $staff, ?string $note = null): BookReservation
    {
        if (! in_array($reservation->status, [BookReservation::STATUS_PENDING, BookReservation::STATUS_READY], true)) {
            throw new \RuntimeException('Only active reservations can be cancelled.');
        }

        $wasReady = $reservation->status === BookReservation::STATUS_READY;
        $heldBook = $reservation->heldBook;
        $title = $reservation->book?->title_statement ?? 'Reserved book';

        $reservation->update([
            'status' => BookReservation::STATUS_CANCELLED,
            'cancelled_at' => Carbon::now('Asia/Manila'),
            'fulfilled_by' => $staff->id,
        ]);

        if ($wasReady && $heldBook) {
            app(CirculationService::class)->releaseHold($heldBook);
            self::fulfilNextInQueue($heldBook->fresh());
        }

        if ($reservation->book) {
            (new self)->recalculateQueuePositions($reservation->book);
        }

        StudentNotification::query()->create([
            'student_id' => $reservation->student_id,
            'type' => 'book_reservation_cancelled',
            'title' => 'Reservation cancelled',
            'message' => $note
                ? "Your reservation for \"{$title}\" was cancelled by staff: {$note}"
                : "Your reservation for \"{$title}\" was cancelled by staff.",
        ]);

        return $reservation->fresh(['book', 'heldBook', 'student']);
    }

    private function ownedReservation(Request $request, BookReservation $reservation): BookReservation|JsonResponse
    {
        $student = $this->resolveStudent($request);

        if ($student instanceof JsonResponse) {
            return $student;
        }

        if ((int) $reservation->student_id !== (int) $student->id) {
            return response()->json([
                'message' => 'Book reservation not found.',
                'data' => null,
            ], 404);
        }

        return $reservation;
    }

    private function bookGroupCopies(Book $book)
    {
        return self::bookGroupCopiesStatic($book);
    }

    private static function bookGroupCopiesStatic(Book $book)
    {
        return Book::query()
            ->whereNull('archived_at')
            ->where('title_statement', $book->title_statement)
            ->where('main_author', $book->main_author)
            ->where('pub_year', $book->pub_year)
            ->get();
    }

    private function recalculateQueuePositions(Book $book): void
    {
        self::recalculateQueuePositionsStatic($book);
    }

    private static function recalculateQueuePositionsStatic(Book $book): void
    {
        $copies = self::bookGroupCopiesStatic($book);

        $pending = BookReservation::query()
            ->whereIn('book_id', $copies->pluck('id'))
            ->where('status', BookReservation::STATUS_PENDING)
            ->orderBy('reserved_at')
            ->get();

        $position = 1;
        foreach ($pending as $reservation) {
            $reservation->update(['queue_position' => $position]);
            $position++;
        }
    }

    private function formatReservation(BookReservation $reservation): array
    {
        $book = $reservation->book;
        $heldCopy = $reservation->heldBook;

        return [
            'id' => $reservation->id,
            'book_id' => $reservation->book_id,
            'held_book_id' => $reservation->held_book_id,
            'status' => $reservation->status,
            'queue_position' => (int) $reservation->queue_position,
            'reserved_at' => $reservation->reserved_at?->toDateTimeString(),
            'available_at' => $reservation->available_at?->toDateTimeString(),
            'expires_at' => $reservation->hold_expires_at?->toDateTimeString(),
            'fulfilled_at' => $reservation->fulfilled_at?->toDateTimeString(),
            'cancelled_at' => $reservation->cancelled_at?->toDateTimeString(),
            'held_copy' => $heldCopy ? [
                'id' => $heldCopy->id,
                'call_number' => $heldCopy->call_number,
                'accession_no' => $heldCopy->accession_no,
                'barcode' => $heldCopy->barcode,
            ] : null,
            'book' => [
                'id' => $book?->id,
                'group' => [
                    'title' => $book?->title_statement,
                    'author' => $book?->main_author,
                    'publication_year' => $book?->pub_year,
                ],
                'description' => [
                    'title' => $book?->title_statement,
                    'author' => $book?->main_author,
                    'call_number' => $book?->call_number,
                    'cover_url' => filled($book?->cover_image) ? asset('storage/'.$book->cover_image) : null,
                ],
            ],
        ];
    }
}
