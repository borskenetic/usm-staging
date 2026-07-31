<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BookLog;
use App\Services\AdminActivityLogger;
use App\Services\CirculationService;
use Illuminate\Http\Request;

class ActiveCheckoutController extends Controller
{
    public function index(CirculationService $circulation)
    {
        $loans = $circulation->activeCheckoutLoans();

        return view('checkout.active', compact('loans'));
    }

    public function confirmReturn(Request $request, int $id, CirculationService $circulation)
    {
        $loan = BookLog::query()
            ->with(['book', 'student'])
            ->findOrFail($id);

        if ($loan->status !== 'Checked Out' || $loan->circulation_type !== BookLog::CIRCULATION_CHECKOUT) {
            return back()->with('error', 'Only active borrowed books can be returned from this page.');
        }

        $book = $loan->book;
        $student = $loan->student;

        if (! $book || ! $student) {
            return back()->with('error', 'Book or patron record not found.');
        }

        try {
            $result = $circulation->checkInBook($book, $student);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($result['overdue_modal']) {
            session()->flash('overdue_modal', $result['overdue_modal']);
        }

        app(AdminActivityLogger::class)->log(
            'library',
            'checkout.return_confirmed',
            'Book return confirmed',
            "{$student->lastname}, {$student->firstname} returned \"{$book->title_statement}\""
        );

        return back()->with('success', 'Return confirmed. The book has been checked in.');
    }
}
