<?php

namespace App\Http\Controllers;

use App\Mail\ReservationApprovedMail;
use App\Models\ReservationLog;
use App\Models\Room;
use App\Models\RoomReservation;
use App\Services\AdminActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class RoomReservationController extends Controller
{
    /**
     * Show booking page
     */
    public function create()
    {
        $rooms = Room::all();

        // Predefined 2-hour intervals between 8AM and 6PM
        $timeSlots = [
            '08:00-10:00' => '8:00 AM - 10:00 AM',
            '10:00-12:00' => '10:00 AM - 12:00 PM',
            '12:00-14:00' => '12:00 PM - 2:00 PM',
            '14:00-16:00' => '2:00 PM - 4:00 PM',
            '16:00-18:00' => '4:00 PM - 6:00 PM',
        ];

        return view('rooms.book', compact('rooms', 'timeSlots'));
    }

    public function destroy($id)
    {
        $reservation = RoomReservation::findOrFail($id);
        $reservation->delete();

        return redirect()->back()->with('success', 'Reservation removed successfully.');
    }

    /**
     * Store booking request
     */
    public function store(Request $request)
    {
        // 🧩 Validate form inputs
        $validated = $request->validate([
            'room_id' => 'required|exists:library_rooms,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|string',
            'start_ampm' => 'required|string|in:AM,PM',
            'end_time' => 'required|string',
            'end_ampm' => 'required|string|in:AM,PM',
            'patron_email' => 'required|email',
            'number_of_students' => 'required|integer|min:1|max:20',
            'student_names' => 'required|array|min:1|max:20',
            'student_names.*' => 'required|string|max:255',
        ]);

        // 🕒 Convert 12-hour time to 24-hour format for MySQL TIME type
        $startTime = \Carbon\Carbon::createFromFormat('g:i A', $request->start_time.' '.$request->start_ampm)->format('H:i:s');
        $endTime = \Carbon\Carbon::createFromFormat('g:i A', $request->end_time.' '.$request->end_ampm)->format('H:i:s');

        // 🧭 Prevent double booking (same room/date/timeslot)
        $exists = RoomReservation::where('room_id', $request->room_id)
            ->where('date', $request->date)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime]);
            })
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'That room and time slot is already booked.');
        }

        // ✅ Insert data safely
        $reservation = \DB::transaction(function () use ($request, $startTime, $endTime) {
            $reservation = RoomReservation::create([
                'room_id' => $request->room_id,
                'date' => $request->date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'patron_email' => $request->patron_email,
                'number_of_students' => $request->number_of_students,
                'status' => 'pending',
            ]);

            foreach ($request->student_names as $name) {
                \App\Models\ReservationStudent::create([
                    'reservation_id' => $reservation->id,
                    'name' => $name,
                ]);
            }

            \App\Models\ReservationLog::create([
                'reservation_id' => $reservation->id,
                'user_id' => \Auth::id(),
                'action' => 'created',
                'meta' => json_encode($request->all()),
            ]);

            return $reservation->load('room');
        });

        app(AdminActivityLogger::class)->roomReservationPending(
            $reservation,
            (string) ($reservation->room?->name ?? 'Room'),
            (string) $reservation->date,
        );

        return back()->with('success', 'Reservation submitted and pending approval.');
    }

    /**
     * Admin view of pending reservations
     */
    public function pending()
    {
        $pending = RoomReservation::with('room', 'students')->where('status', 'pending')->latest()->get();

        return view('rooms.pending', compact('pending'));
    }

    /**
     * Approve a reservation
     */
    public function approve($id, AdminActivityLogger $activities)
    {
        $reservation = RoomReservation::findOrFail($id);

        $reservation->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        ReservationLog::create([
            'reservation_id' => $reservation->id,
            'user_id' => Auth::id(),
            'action' => 'approved',
        ]);
        $activities->log('library', 'room.approved', 'Room reservation approved', $reservation->patron_email, $reservation);

        $flash = ['success' => 'Reservation approved successfully.'];

        if (filled($reservation->patron_email)) {
            try {
                Mail::to($reservation->patron_email)->send(new ReservationApprovedMail($reservation));
            } catch (\Throwable $e) {
                report($e);
                $flash['warning'] = 'The confirmation email could not be sent. Check SMTP settings for support@pantas.org.';
            }
        }

        return back()->with($flash);
    }

    /**
     * Schedule view (all reservations)
     */
    public function schedule()
    {
        $reservations = RoomReservation::with('room')->orderBy('date')->get();
        $rooms = Room::all();

        return view('rooms.schedule', compact('reservations', 'rooms'));
    }

    /**
     * Show reservation details
     */
    public function show($id)
    {
        $reservation = RoomReservation::with(['room', 'students', 'logs'])->findOrFail($id);

        return view('rooms.show', compact('reservation'));
    }

    public function checkAvailability(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:library_rooms,id',
            'date' => 'required|date',
        ]);

        $bookedSlots = RoomReservation::where('room_id', $request->room_id)
            ->where('date', $request->date)
            ->whereIn('status', ['pending', 'approved'])
            ->pluck('time_slot');

        return response()->json($bookedSlots);
    }

    public function logs()
    {
        $logs = \App\Models\ReservationLog::with(['reservation.room', 'user'])
            ->latest()
            ->paginate(20);

        return view('rooms.logs', compact('logs'));
    }

    public function reject($id, AdminActivityLogger $activities)
    {
        $reservation = RoomReservation::findOrFail($id);
        $reservation->status = 'rejected';
        $reservation->save();

        // (Optional) Log the rejection
        ReservationLog::create([
            'reservation_id' => $reservation->id,
            'user_id' => auth()->id(),
            'action' => 'rejected',
        ]);
        $activities->log('library', 'room.rejected', 'Room reservation rejected', $reservation->patron_email, $reservation);

        return redirect()->back()->with('success', 'Reservation rejected successfully.');
    }
}
