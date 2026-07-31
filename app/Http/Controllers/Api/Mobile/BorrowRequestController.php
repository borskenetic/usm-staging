<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\ResolvesMobileStudent;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BorrowRequest;
use App\Models\BorrowRequestItem;
use App\Models\StudentNotification;
use App\Services\CirculationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BorrowRequestController extends Controller
{
    use ResolvesMobileStudent;

    public function index(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if ($student instanceof JsonResponse) {
            return $student;
        }

        $requests = BorrowRequest::query()
            ->with(['items.book'])
            ->where('student_id', $student->id)
            ->latest('requested_at')
            ->get()
            ->map(fn (BorrowRequest $borrowRequest) => $this->formatRequest($borrowRequest));

        return response()->json([
            'message' => 'Borrow requests retrieved.',
            'data' => $requests,
        ]);
    }

    public function show(Request $request, BorrowRequest $borrowRequest): JsonResponse
    {
        $owned = $this->ownedRequest($request, $borrowRequest);

        if ($owned instanceof JsonResponse) {
            return $owned;
        }

        return response()->json([
            'message' => 'Borrow request retrieved.',
            'data' => $this->formatRequest($owned->load(['items.book'])),
        ]);
    }

    public function destroy(Request $request, BorrowRequest $borrowRequest): JsonResponse
    {
        $owned = $this->ownedRequest($request, $borrowRequest);

        if ($owned instanceof JsonResponse) {
            return $owned;
        }

        if ($owned->status !== BorrowRequest::STATUS_PENDING) {
            return response()->json([
                'message' => 'Only pending borrow requests can be cancelled.',
                'data' => null,
            ], 409);
        }

        $owned->update(['status' => BorrowRequest::STATUS_CANCELLED]);
        $owned->items()->update(['status' => BorrowRequestItem::STATUS_REJECTED]);

        return response()->json([
            'message' => 'Borrow request cancelled.',
            'data' => $this->formatRequest($owned->load(['items.book'])),
        ]);
    }

    public function submitCart(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if ($student instanceof JsonResponse) {
            return $student;
        }

        $validated = $request->validate([
            'book_ids' => ['required', 'array', 'min:1', 'max:10'],
            'book_ids.*' => ['required', 'integer', 'distinct', 'exists:library_books,id'],
        ]);

        $circulation = app(CirculationService::class);

        if ($circulation->hasOverdueLoans($student)) {
            return response()->json([
                'message' => 'Checkout blocked: student has overdue book(s).',
                'data' => null,
            ], 409);
        }

        $bookIds = array_values(array_unique(array_map('intval', $validated['book_ids'])));

        $result = DB::transaction(function () use ($bookIds, $student, $circulation) {
            $books = Book::query()
                ->whereIn('id', $bookIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $accepted = [];
            $rejected = [];

            foreach ($bookIds as $bookId) {
                $book = $books->get($bookId);

                if (! $book) {
                    $rejected[] = ['book_id' => $bookId, 'reason' => 'Book not found.'];
                    continue;
                }

                $reason = $circulation->canPatronBorrow($student, $book);
                if ($reason !== null) {
                    $rejected[] = [
                        'book_id' => $bookId,
                        'title' => $book->title_statement,
                        'reason' => $reason,
                    ];
                    continue;
                }

                $accepted[] = $book;
            }

            if ($accepted === []) {
                return [
                    'status' => 409,
                    'message' => 'No available copies could be submitted for approval.',
                    'request' => null,
                    'rejected' => $rejected,
                ];
            }

            $borrowRequest = BorrowRequest::query()->create([
                'student_id' => $student->id,
                'status' => BorrowRequest::STATUS_PENDING,
                'requested_at' => Carbon::now('Asia/Manila'),
            ]);

            foreach ($accepted as $book) {
                BorrowRequestItem::query()->create([
                    'borrow_request_id' => $borrowRequest->id,
                    'book_id' => $book->id,
                    'status' => BorrowRequestItem::STATUS_PENDING,
                ]);
            }

            StudentNotification::query()->create([
                'student_id' => $student->id,
                'type' => 'borrow_request_submitted',
                'title' => 'Borrow request submitted',
                'message' => 'Your borrow request for '.count($accepted).' book(s) is pending staff approval.',
            ]);

            return [
                'status' => 201,
                'message' => 'Borrow request submitted for staff approval.',
                'request' => $borrowRequest->load(['items.book']),
                'rejected' => $rejected,
            ];
        });

        return response()->json([
            'message' => $result['message'],
            'data' => [
                'request' => $result['request'] ? $this->formatRequest($result['request']) : null,
                'rejected' => $result['rejected'],
            ],
        ], $result['status']);
    }

    private function formatRequest(BorrowRequest $borrowRequest): array
    {
        return [
            'id' => $borrowRequest->id,
            'status' => $borrowRequest->status,
            'requested_at' => $borrowRequest->requested_at?->toDateTimeString(),
            'reviewed_at' => $borrowRequest->reviewed_at?->toDateTimeString(),
            'staff_note' => $borrowRequest->staff_note,
            'items' => $borrowRequest->items->map(fn (BorrowRequestItem $item) => [
                'id' => $item->id,
                'book_id' => $item->book_id,
                'status' => $item->status,
                'rejection_reason' => $item->rejection_reason,
                'title' => $item->book?->title_statement,
                'author' => $item->book?->main_author,
                'call_number' => $item->book?->call_number,
                'accession_no' => $item->book?->accession_no,
            ])->values(),
        ];
    }

    private function ownedRequest(Request $request, BorrowRequest $borrowRequest): BorrowRequest|JsonResponse
    {
        $student = $this->resolveStudent($request);

        if ($student instanceof JsonResponse) {
            return $student;
        }

        if ((int) $borrowRequest->student_id !== (int) $student->id) {
            return response()->json([
                'message' => 'Borrow request not found.',
                'data' => null,
            ], 404);
        }

        return $borrowRequest;
    }
}
