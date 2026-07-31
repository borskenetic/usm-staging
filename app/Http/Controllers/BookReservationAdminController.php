<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\Mobile\BookReservationController as MobileBookReservationController;
use App\Models\BookReservation;
use App\Services\AdminActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookReservationAdminController extends Controller
{
    public function pending()
    {
        $reservations = BookReservation::query()
            ->with(['student', 'book', 'heldBook'])
            ->whereIn('status', [BookReservation::STATUS_PENDING, BookReservation::STATUS_READY])
            ->orderByRaw("CASE WHEN status = 'ready' THEN 0 ELSE 1 END")
            ->orderBy('reserved_at')
            ->get();

        return view('books.reservations.pending', compact('reservations'));
    }

    public function fulfill(int $id)
    {
        $reservation = BookReservation::query()->with(['student', 'book', 'heldBook'])->findOrFail($id);

        try {
            MobileBookReservationController::fulfillAtDesk($reservation, Auth::user());
            app(AdminActivityLogger::class)->log('library', 'book_reservation.fulfilled', 'Reservation fulfilled at desk', "Reservation #{$reservation->id} fulfilled for {$reservation->student?->id_number}");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Reservation fulfilled and book checked out.');
    }

    public function cancel(Request $request, int $id)
    {
        $reservation = BookReservation::query()->findOrFail($id);

        try {
            MobileBookReservationController::staffCancel(
                $reservation,
                Auth::user(),
                $request->input('note')
            );
            app(AdminActivityLogger::class)->log('library', 'book_reservation.cancelled', 'Reservation cancelled', "Reservation #{$reservation->id} cancelled by staff");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Reservation cancelled.');
    }
}
