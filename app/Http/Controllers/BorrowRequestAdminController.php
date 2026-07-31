<?php

namespace App\Http\Controllers;

use App\Models\BorrowRequest;
use App\Models\BorrowRequestItem;
use App\Models\StudentNotification;
use App\Services\AdminActivityLogger;
use App\Services\CirculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BorrowRequestAdminController extends Controller
{
    public function pending()
    {
        $requests = BorrowRequest::query()
            ->with(['student', 'items.book'])
            ->where('status', BorrowRequest::STATUS_PENDING)
            ->latest('requested_at')
            ->get();

        return view('checkout.requests.pending', compact('requests'));
    }

    public function approve(Request $request, int $id)
    {
        $borrowRequest = BorrowRequest::query()
            ->with(['student', 'items.book'])
            ->findOrFail($id);

        if ($borrowRequest->status !== BorrowRequest::STATUS_PENDING) {
            return back()->with('error', 'Only pending borrow requests can be approved.');
        }

        $validated = $request->validate([
            'item_ids' => ['nullable', 'array'],
            'item_ids.*' => ['integer', 'exists:library_borrow_request_items,id'],
            'staff_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $itemIds = collect($validated['item_ids'] ?? $borrowRequest->items->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->all();

        $circulation = app(CirculationService::class);
        $student = $borrowRequest->student;
        $approvedCount = 0;
        $rejectedCount = 0;

        try {
            DB::transaction(function () use ($borrowRequest, $itemIds, $circulation, $student, $validated, &$approvedCount, &$rejectedCount) {
                foreach ($borrowRequest->items as $item) {
                    if (! in_array((int) $item->id, $itemIds, true)) {
                        $item->update([
                            'status' => BorrowRequestItem::STATUS_REJECTED,
                            'rejection_reason' => 'Not selected for approval.',
                        ]);
                        $rejectedCount++;
                        continue;
                    }

                    $book = $item->book;
                    if (! $book || ! $student) {
                        $item->update([
                            'status' => BorrowRequestItem::STATUS_REJECTED,
                            'rejection_reason' => 'Book or patron not found.',
                        ]);
                        $rejectedCount++;
                        continue;
                    }

                    $reason = $circulation->canPatronBorrow($student, $book);
                    if ($reason !== null) {
                        $item->update([
                            'status' => BorrowRequestItem::STATUS_REJECTED,
                            'rejection_reason' => $reason,
                        ]);
                        $rejectedCount++;
                        continue;
                    }

                    $circulation->checkoutBook($student, $book, Auth::user());
                    $item->update(['status' => BorrowRequestItem::STATUS_APPROVED]);
                    $approvedCount++;
                }

                $status = match (true) {
                    $approvedCount > 0 && $rejectedCount > 0 => BorrowRequest::STATUS_APPROVED,
                    $approvedCount > 0 => BorrowRequest::STATUS_APPROVED,
                    default => BorrowRequest::STATUS_REJECTED,
                };

                $borrowRequest->update([
                    'status' => $status,
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => Carbon::now('Asia/Manila'),
                    'staff_note' => $validated['staff_note'] ?? null,
                ]);
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $type = $approvedCount > 0 ? 'borrow_request_approved' : 'borrow_request_rejected';
        $message = $approvedCount > 0
            ? "Staff approved {$approvedCount} book(s)".($rejectedCount > 0 ? " and rejected {$rejectedCount}." : '.')
            : 'Your borrow request was rejected by staff.';

        StudentNotification::query()->create([
            'student_id' => $borrowRequest->student_id,
            'type' => $type,
            'title' => $approvedCount > 0 ? 'Borrow request approved' : 'Borrow request rejected',
            'message' => $message,
        ]);

        app(AdminActivityLogger::class)->log('library', 'borrow_request.reviewed', 'Borrow request reviewed', "Request #{$borrowRequest->id}: {$approvedCount} approved, {$rejectedCount} rejected");

        return back()->with('success', "Borrow request processed: {$approvedCount} approved, {$rejectedCount} rejected.");
    }

    public function reject(Request $request, int $id)
    {
        $borrowRequest = BorrowRequest::query()->with('items')->findOrFail($id);

        if ($borrowRequest->status !== BorrowRequest::STATUS_PENDING) {
            return back()->with('error', 'Only pending borrow requests can be rejected.');
        }

        $validated = $request->validate([
            'staff_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $borrowRequest->update([
            'status' => BorrowRequest::STATUS_REJECTED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => Carbon::now('Asia/Manila'),
            'staff_note' => $validated['staff_note'] ?? null,
        ]);

        $borrowRequest->items()->update([
            'status' => BorrowRequestItem::STATUS_REJECTED,
            'rejection_reason' => $validated['staff_note'] ?? 'Rejected by staff.',
        ]);

        StudentNotification::query()->create([
            'student_id' => $borrowRequest->student_id,
            'type' => 'borrow_request_rejected',
            'title' => 'Borrow request rejected',
            'message' => $validated['staff_note']
                ? "Your borrow request was rejected: {$validated['staff_note']}"
                : 'Your borrow request was rejected by staff.',
        ]);

        app(AdminActivityLogger::class)->log('library', 'borrow_request.rejected', 'Borrow request rejected', "Request #{$borrowRequest->id} rejected");

        return back()->with('success', 'Borrow request rejected.');
    }

    public function approveItem(int $itemId)
    {
        $item = BorrowRequestItem::query()
            ->with(['borrowRequest.student', 'book'])
            ->findOrFail($itemId);

        $borrowRequest = $item->borrowRequest;

        if (! $borrowRequest || $borrowRequest->status !== BorrowRequest::STATUS_PENDING) {
            return back()->with('error', 'Only pending borrow requests can be reviewed.');
        }

        if ($item->status !== BorrowRequestItem::STATUS_PENDING) {
            return back()->with('error', 'This item has already been reviewed.');
        }

        $circulation = app(CirculationService::class);
        $student = $borrowRequest->student;
        $book = $item->book;

        if (! $student || ! $book) {
            $item->update([
                'status' => BorrowRequestItem::STATUS_REJECTED,
                'rejection_reason' => 'Book or patron not found.',
            ]);
            $this->resolveRequestStatus($borrowRequest->fresh('items'));

            return back()->with('error', 'Book or patron not found.');
        }

        $reason = $circulation->canPatronBorrow($student, $book);
        if ($reason !== null) {
            $item->update([
                'status' => BorrowRequestItem::STATUS_REJECTED,
                'rejection_reason' => $reason,
            ]);
            $this->resolveRequestStatus($borrowRequest->fresh('items'));

            return back()->with('error', $reason);
        }

        try {
            $circulation->checkoutBook($student, $book, Auth::user());
        } catch (\Throwable $e) {
            $item->update([
                'status' => BorrowRequestItem::STATUS_REJECTED,
                'rejection_reason' => $e->getMessage(),
            ]);
            $this->resolveRequestStatus($borrowRequest->fresh('items'));

            return back()->with('error', $e->getMessage());
        }

        $item->update(['status' => BorrowRequestItem::STATUS_APPROVED]);
        $this->resolveRequestStatus($borrowRequest->fresh('items'));

        app(AdminActivityLogger::class)->log('library', 'borrow_request.item_approved', 'Borrow request item approved', "Request #{$borrowRequest->id}, item #{$item->id} approved");

        return back()->with('success', 'Book approved and checked out.');
    }

    public function rejectItem(Request $request, int $itemId)
    {
        $item = BorrowRequestItem::query()
            ->with(['borrowRequest'])
            ->findOrFail($itemId);

        $borrowRequest = $item->borrowRequest;

        if (! $borrowRequest || $borrowRequest->status !== BorrowRequest::STATUS_PENDING) {
            return back()->with('error', 'Only pending borrow requests can be reviewed.');
        }

        if ($item->status !== BorrowRequestItem::STATUS_PENDING) {
            return back()->with('error', 'This item has already been reviewed.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $item->update([
            'status' => BorrowRequestItem::STATUS_REJECTED,
            'rejection_reason' => $validated['rejection_reason'] ?? 'Rejected by staff.',
        ]);

        $this->resolveRequestStatus($borrowRequest->fresh('items'));

        app(AdminActivityLogger::class)->log('library', 'borrow_request.item_rejected', 'Borrow request item rejected', "Request #{$borrowRequest->id}, item #{$item->id} rejected");

        return back()->with('success', 'Book request rejected.');
    }

    private function resolveRequestStatus(BorrowRequest $borrowRequest): void
    {
        $items = $borrowRequest->items;

        if ($items->contains('status', BorrowRequestItem::STATUS_PENDING)) {
            return;
        }

        $approvedCount = $items->where('status', BorrowRequestItem::STATUS_APPROVED)->count();
        $rejectedCount = $items->where('status', BorrowRequestItem::STATUS_REJECTED)->count();

        $status = $approvedCount > 0
            ? BorrowRequest::STATUS_APPROVED
            : BorrowRequest::STATUS_REJECTED;

        $borrowRequest->update([
            'status' => $status,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => Carbon::now('Asia/Manila'),
        ]);

        $type = $approvedCount > 0 ? 'borrow_request_approved' : 'borrow_request_rejected';
        $message = $approvedCount > 0
            ? "Staff approved {$approvedCount} book(s)".($rejectedCount > 0 ? " and rejected {$rejectedCount}." : '.')
            : 'Your borrow request was rejected by staff.';

        StudentNotification::query()->create([
            'student_id' => $borrowRequest->student_id,
            'type' => $type,
            'title' => $approvedCount > 0 ? 'Borrow request approved' : 'Borrow request rejected',
            'message' => $message,
        ]);
    }
}
