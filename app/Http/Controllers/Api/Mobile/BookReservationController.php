<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookReservation;
use App\Models\Student;
use App\Models\StudentNotification;
use App\Models\User;
use App\Services\Auth\ModuleAccessService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookReservationController extends Controller
{
    /**
     * Hold duration: how long a "ready" reservation stays valid before it
     * expires and the next student in the queue is notified.
     */
    private const HOLD_DURATION_HOURS = 48;

    /**
     * Reserve a book (join the queue) when no copies are available.
     */
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

        // Check that the book group is actually unavailable.
        $copies = $this->bookGroupCopies($book);

        if ($copies->contains(fn (Book $copy) => $copy->availability === 'Available')) {
            return response()->json([
                'message' => 'This book has available copies — no reservation needed.',
                'data' => null,
            ], 409);
        }

        // Prevent duplicate active reservations by the same student for the same group.
        $existing = BookReservation::query()
            ->where('student_id', $student->id)
            ->whereIn('book_id', $copies->pluck('id'))
            ->whereIn('status', ['pending', 'ready'])
            ->exists();

        if ($existing) {
            return response()->json([
                'message' => 'You already have an active reservation for this book.',
                'data' => null,
            ], 409);
        }

        // Calculate queue position: count existing pending reservations for
        // the same book group + 1.
        $queuePosition = BookReservation::query()
            ->whereIn('book_id', $copies->pluck('id'))
            ->where('status', 'pending')
            ->count() + 1;

        $reservation = BookReservation::query()->create([
            'student_id' => $student->id,
            'book_id' => $book->id,
            'status' => 'pending',
            'queue_position' => $queuePosition,
            'reserved_at' => Carbon::now('Asia/Manila'),
        ]);

        return response()->json([
            'message' => "Book reserved. You are #{$queuePosition} in the queue.",
            'data' => $this->formatReservation($reservation->load('book')),
        ], 201);
    }

    /**
     * List the current student's reservations.
     */
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
            ->map(fn (BookReservation $reservation) => $this->formatReservation($reservation->load('book')));

        return response()->json([
            'message' => 'Book reservations retrieved.',
            'data' => $reservations,
        ]);
    }

    /**
     * Show a single reservation.
     */
    public function show(Request $request, BookReservation $reservation): JsonResponse
    {
        $owned = $this->ownedReservation($request, $reservation);

        if ($owned instanceof JsonResponse) {
            return $owned;
        }

        return response()->json([
            'message' => 'Book reservation retrieved.',
            'data' => $this->formatReservation($owned->load('book')),
        ]);
    }

    /**
     * Cancel an active reservation (leave the queue).
     */
    public function destroy(Request $request, BookReservation $reservation): JsonResponse
    {
        $owned = $this->ownedReservation($request, $reservation);

        if ($owned instanceof JsonResponse) {
            return $owned;
        }

        if (! in_array($owned->status, ['pending', 'ready'], true)) {
            return response()->json([
                'message' => 'Only active reservations can be cancelled.',
                'data' => null,
            ], 409);
        }

        $owned->update([
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now('Asia/Manila'),
        ]);

        // Re-calculate queue positions for remaining pending reservations.
        $this->recalculateQueuePositions($owned->book);

        return response()->json([
            'message' => 'Reservation cancelled.',
            'data' => $this->formatReservation($owned->load('book')),
        ]);
    }

    /**
     * Called when a book is returned (checked in) to notify the next student
     * in the reservation queue that their reserved book is available.
     *
     * This method is intended to be called from the BookController check-in
     * flow or a scheduled job. It:
     * 1. Finds the next pending reservation for the book group.
     * 2. Marks it as "ready" with a hold expiry.
     * 3. Creates an in-app notification (and triggers email/SMS if configured).
     */
    public static function fulfilNextInQueue(Book $returnedBook): void
    {
        $copies = self::bookGroupCopiesStatic($returnedBook);

        // Only proceed if there are pending reservations.
        $nextReservation = BookReservation::query()
            ->whereIn('book_id', $copies->pluck('id'))
            ->where('status', 'pending')
            ->orderBy('reserved_at')
            ->first();

        if (! $nextReservation) {
            return;
        }

        $now = Carbon::now('Asia/Manila');

        $nextReservation->update([
            'status' => 'ready',
            'available_at' => $now,
            'hold_expires_at' => $now->copy()->addHours(self::HOLD_DURATION_HOURS),
        ]);

        // Re-calculate queue positions for remaining pending reservations.
        self::recalculateQueuePositionsStatic($returnedBook);

        // Create in-app notification.
        StudentNotification::query()->create([
            'student_id' => $nextReservation->student_id,
            'type' => 'book_reservation_ready',
            'title' => 'Reserved book available',
            'message' => "Your reserved book \"{$returnedBook->title_statement}\" is now available. Please claim it at the library before {$nextReservation->hold_expires_at->format('M j, Y H:i')}.",
        ]);

        // Email and SMS notifications can be dispatched here via queued jobs.
        // Example:
        // Mail::to($nextReservation->student)->send(new ReservationReadyMail($nextReservation));
        // SmsService::send($nextReservation->student->mobile_number, $message);
    }

    /**
     * Expire "ready" reservations whose hold has expired and notify the next
     * student in the queue. Intended to be called by a scheduled command.
     */
    public static function expireStaleHolds(): void
    {
        $expired = BookReservation::query()
            ->where('status', 'ready')
            ->where('hold_expires_at', '<', Carbon::now('Asia/Manila'))
            ->get();

        foreach ($expired as $reservation) {
            $reservation->update(['status' => 'expired']);

            StudentNotification::query()->create([
                'student_id' => $reservation->student_id,
                'type' => 'book_reservation_expired',
                'title' => 'Reservation expired',
                'message' => "Your reservation for \"{$reservation->book?->title_statement}\" has expired because it was not claimed in time.",
            ]);

            // Notify the next student in the queue if the book is still available.
            $book = $reservation->book;
            if ($book && $book->availability === 'Available') {
                self::fulfilNextInQueue($book);
            }
        }
    }

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    private function resolveStudent(Request $request): Student|JsonResponse
    {
        $tokenable = $request->user();

        if ($tokenable instanceof Student) {
            return $tokenable;
        }

        if ($tokenable instanceof User) {
            if (app(ModuleAccessService::class)->availableModules($tokenable) !== []) {
                return response()->json([
                    'message' => 'This account is not allowed to use mobile book reservations.',
                    'data' => null,
                ], 403);
            }

            $tokenable->loadMissing('student');

            if ($tokenable->student) {
                return $tokenable->student;
            }
        }

        return response()->json([
            'message' => 'No student profile is linked to this account.',
            'data' => null,
        ], 409);
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

    /**
     * Get all copies in the same book group (same title + author + pub_year).
     */
    private function bookGroupCopies(Book $book)
    {
        return $this::bookGroupCopiesStatic($book);
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
        $this::recalculateQueuePositionsStatic($book);
    }

    private static function recalculateQueuePositionsStatic(Book $book): void
    {
        $copies = self::bookGroupCopiesStatic($book);

        $pending = BookReservation::query()
            ->whereIn('book_id', $copies->pluck('id'))
            ->where('status', 'pending')
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

        return [
            'id' => $reservation->id,
            'book_id' => $reservation->book_id,
            'status' => $reservation->status,
            'queue_position' => (int) $reservation->queue_position,
            'reserved_at' => $reservation->reserved_at?->toDateTimeString(),
            'available_at' => $reservation->available_at?->toDateTimeString(),
            'expires_at' => $reservation->hold_expires_at?->toDateTimeString(),
            'cancelled_at' => $reservation->cancelled_at?->toDateTimeString(),
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