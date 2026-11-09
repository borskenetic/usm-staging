<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookLog;
use App\Models\FineSetting;
use App\Models\Student;
use App\Services\AdminActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function process(Request $request)
    {
        Log::info('Checkout request data: ', $request->all());

        try {
            $request->validate([
                'student_id' => 'required|string',
                'book_id' => 'nullable|integer',
                'books' => 'nullable|array',
                'books.*.id' => 'required_with:books|integer',
            ]);

            $student = Student::where('id_number', $request->student_id)->first();

            if (! $student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student ID not found.',
                ]);
            }

            $patronLegacyName = "{$student->lastname}, {$student->firstname}";

            $hasOverdue = BookLog::where('status', 'Checked Out')
                ->whereDate('due_date', '<', now())
                ->where(function ($q) use ($student, $patronLegacyName) {
                    $q->where('student_id', $student->id)
                        ->orWhere('patron_name', $patronLegacyName);
                })
                ->exists();

            if ($hasOverdue) {
                return response()->json([
                    'success' => false,
                    'message' => 'Checkout blocked: student has overdue book(s).',
                ]);
            }

            $bookIds = [];
            if ($request->book_id) {
                $bookIds[] = (int) $request->book_id;
            }
            if ($request->books) {
                foreach ($request->books as $b) {
                    $bookIds[] = (int) $b['id'];
                }
            }

            if ($bookIds === []) {
                return response()->json([
                    'success' => false,
                    'message' => 'No books provided.',
                ]);
            }

            $availableIds = [];
            foreach (array_unique($bookIds) as $bookId) {
                $b = Book::find($bookId);
                if ($b && $b->availability === 'Available') {
                    $availableIds[] = (int) $bookId;
                }
            }

            if ($availableIds === []) {
                return response()->json([
                    'success' => false,
                    'message' => 'No available copies could be checked out.',
                ]);
            }

            $checkoutBooks = Book::query()->whereIn('id', $availableIds)->get();
            $capacityError = FineSetting::capacityErrorForBooks((int) $student->id, $checkoutBooks);
            if ($capacityError !== null) {
                return response()->json([
                    'success' => false,
                    'message' => $capacityError,
                ]);
            }

            $borrowedAt = Carbon::now();
            $processedBooks = [];

            foreach ($availableIds as $bookId) {
                $book = Book::find($bookId);

                if (! $book || $book->availability !== 'Available') {
                    continue;
                }

                $issued = $this->issueRoomUse($student, $book, $patronLegacyName, $borrowedAt);
                if ($issued === null) {
                    continue;
                }

                $processedBooks[] = $issued;
            }

            if ($processedBooks === []) {
                return response()->json([
                    'success' => false,
                    'message' => 'No available copies could be used in the library (some may be blocked by the re-borrow cooldown).',
                ]);
            }

            $this->notifyKioskRoomUse($patronLegacyName, $processedBooks);

            return response()->json([
                'success' => true,
                'student' => [
                    'name' => $patronLegacyName,
                    'id_number' => $student->id_number,
                    'course' => $student->course,
                ],
                'books' => $processedBooks,
                'book' => count($processedBooks) === 1 ? $processedBooks[0] : null,
                'due_date' => $processedBooks[count($processedBooks) - 1]['due_date'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Checkout Exception: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred: '.$e->getMessage(),
            ], 500);
        }
    }

    public function bulk(Request $request)
    {
        $request->validate([
            'student_id' => 'required|string',
            'book_ids' => 'required|array',
        ]);

        $student = Student::where('id_number', $request->student_id)->first();

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found',
            ]);
        }

        $patronLegacyName = "{$student->lastname}, {$student->firstname}";

        $hasOverdue = BookLog::where('status', 'Checked Out')
            ->whereDate('due_date', '<', now())
            ->where(function ($q) use ($student, $patronLegacyName) {
                $q->where('student_id', $student->id)
                    ->orWhere('patron_name', $patronLegacyName);
            })
            ->exists();

        if ($hasOverdue) {
            return response()->json([
                'success' => false,
                'message' => 'Checkout blocked: student has overdue book(s).',
            ]);
        }

        $availableIds = [];
        foreach ($request->book_ids as $bookId) {
            $book = Book::find($bookId);
            if ($book && $book->availability === 'Available') {
                $availableIds[] = (int) $book->id;
            }
        }

        if ($availableIds === []) {
            return response()->json(['success' => false, 'message' => 'No available copies could be checked out.']);
        }

        $checkoutBooks = Book::query()->whereIn('id', $availableIds)->get();
        $capacityError = FineSetting::capacityErrorForBooks((int) $student->id, $checkoutBooks);
        if ($capacityError !== null) {
            return response()->json([
                'success' => false,
                'message' => $capacityError,
            ]);
        }

        $borrowedAt = Carbon::now();
        $results = [];

        foreach ($availableIds as $bookId) {
            $book = Book::find($bookId);

            if (! $book || $book->availability !== 'Available') {
                continue;
            }

            $issued = $this->issueRoomUse($student, $book, $patronLegacyName, $borrowedAt);
            if ($issued === null) {
                continue;
            }

            $results[] = $issued;
        }

        if ($results === []) {
            return response()->json(['success' => false, 'message' => 'No available copies could be checked out.']);
        }

        $this->notifyKioskRoomUse($patronLegacyName, $results);

        return response()->json([
            'success' => true,
            'student' => [
                'name' => $patronLegacyName,
                'id_number' => $student->id_number,
            ],
            'books' => $results,
        ]);
    }

    /**
     * Kiosk loans stay in the library: no due date and no outside-loan fine term.
     *
     * @return array{id: int, title: ?string, author: ?string, barcode: ?string, due_date: null}|null
     */
    private function issueRoomUse(Student $student, Book $book, string $patronName, Carbon $borrowedAt): ?array
    {
        if ($book->availability !== Book::AVAILABILITY_AVAILABLE) {
            return null;
        }

        $latestReturn = BookLog::query()
            ->where('student_id', $student->id)
            ->where('book_id', $book->id)
            ->where('status', 'Checked In')
            ->whereNotNull('returned_date')
            ->orderByDesc('returned_date')
            ->value('returned_date');

        if ($latestReturn) {
            $policy = FineSetting::forSection($book->section);
            $allowedAt = Carbon::parse($latestReturn)->timezone('Asia/Manila')->addDays($policy->reborrowCooldownDays());
            if (Carbon::now('Asia/Manila')->lt($allowedAt)) {
                return null;
            }
        }

        BookLog::create([
            'book_id' => $book->id,
            'student_id' => $student->id,
            'patron_name' => $patronName,
            'status' => 'Checked Out',
            'circulation_type' => BookLog::CIRCULATION_ROOM_USE,
            'renew_count' => 0,
            'timestamp' => $borrowedAt,
            'due_date' => null,
            'fine_incurred' => 0,
        ]);

        $book->update(['availability' => Book::AVAILABILITY_BORROWED]);

        return [
            'id' => $book->id,
            'title' => $book->title_statement,
            'author' => $book->main_author,
            'barcode' => $book->barcode,
            'due_date' => null,
        ];
    }

    /**
     * @param  list<array{title?: ?string}>  $books
     */
    private function notifyKioskRoomUse(string $patronName, array $books): void
    {
        $titles = [];
        foreach ($books as $book) {
            $title = trim((string) ($book['title'] ?? ''));
            if ($title !== '') {
                $titles[] = $title;
            }
        }

        app(AdminActivityLogger::class)->selfCheckout($patronName, count($books), $titles);
    }
}
