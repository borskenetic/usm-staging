<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\ResolvesMobileStudent;
use App\Http\Controllers\Controller;
use App\Models\ReservationLog;
use App\Models\ReservationStudent;
use App\Models\Room;
use App\Models\RoomReservation;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomReservationController extends Controller
{
    use ResolvesMobileStudent;

    public function rooms(): JsonResponse
    {
        $rooms = Room::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Room $room) => [
                'id' => $room->id,
                'name' => $room->name,
                'description' => $room->description,
                'capacity' => $room->capacity,
            ]);

        return response()->json([
            'message' => 'Rooms retrieved.',
            'data' => $rooms,
        ]);
    }

    public function availability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_id' => ['required', 'integer', 'exists:library_rooms,id'],
            'date' => ['required', 'date'],
        ]);

        $reservations = RoomReservation::query()
            ->where('room_id', $validated['room_id'])
            ->whereDate('date', $validated['date'])
            ->whereIn('status', ['pending', 'approved'])
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'message' => 'Room availability retrieved.',
            'data' => [
                'room_id' => (int) $validated['room_id'],
                'date' => $validated['date'],
                'booked_slots' => $reservations->map(fn (RoomReservation $reservation) => [
                    'reservation_id' => $reservation->id,
                    'start_time' => $this->formatTime($reservation->start_time),
                    'end_time' => $this->formatTime($reservation->end_time),
                    'status' => $reservation->status,
                ])->values(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if ($student instanceof JsonResponse) {
            return $student;
        }

        $validated = $request->validate([
            'room_id' => ['required', 'integer', 'exists:library_rooms,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'string'],
            'start_ampm' => ['required', 'string', 'in:AM,PM'],
            'end_time' => ['required', 'string'],
            'end_ampm' => ['required', 'string', 'in:AM,PM'],
            'number_of_students' => ['required', 'integer', 'min:1', 'max:20'],
            'student_names' => ['required', 'array', 'min:1', 'max:20'],
            'student_names.*' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $room = Room::query()->findOrFail($validated['room_id']);

        if ((int) $validated['number_of_students'] > (int) $room->capacity) {
            return response()->json([
                'message' => 'Reservation blocked: number of students exceeds room capacity.',
                'data' => null,
            ], 422);
        }

        if (count($validated['student_names']) !== (int) $validated['number_of_students']) {
            return response()->json([
                'message' => 'Reservation blocked: student_names count must match number_of_students.',
                'data' => null,
            ], 422);
        }

        $startTime = $this->parseTime($validated['start_time'], $validated['start_ampm']);
        $endTime = $this->parseTime($validated['end_time'], $validated['end_ampm']);

        if ($endTime->lessThanOrEqualTo($startTime)) {
            return response()->json([
                'message' => 'Reservation blocked: end time must be after start time.',
                'data' => null,
            ], 422);
        }

        $date = Carbon::parse($validated['date'])->toDateString();
        $start = $startTime->format('H:i:s');
        $end = $endTime->format('H:i:s');

        $exists = RoomReservation::query()
            ->where('room_id', $room->id)
            ->whereDate('date', $date)
            ->whereIn('status', ['pending', 'approved'])
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Reservation blocked: that room and time slot is already booked.',
                'data' => null,
            ], 409);
        }

        $reservation = DB::transaction(function () use ($date, $end, $request, $room, $start, $student, $validated) {
            $user = $request->user();
            $userId = $user instanceof User ? $user->id : null;

            $reservation = RoomReservation::query()->create([
                'room_id' => $room->id,
                'user_id' => $userId,
                'student_id' => $student->id,
                'date' => $date,
                'start_time' => $start,
                'end_time' => $end,
                'patron_email' => $this->studentContact($student),
                'number_of_students' => $validated['number_of_students'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
            ]);

            foreach ($validated['student_names'] as $name) {
                ReservationStudent::query()->create([
                    'reservation_id' => $reservation->id,
                    'name' => $name,
                ]);
            }

            ReservationLog::query()->create([
                'reservation_id' => $reservation->id,
                'user_id' => $userId,
                'action' => 'created',
                'meta' => [
                    'source' => 'mobile',
                    'student_id' => $student->id,
                    'student_number' => $student->id_number,
                    'room_id' => $room->id,
                    'date' => $date,
                    'start_time' => $start,
                    'end_time' => $end,
                ],
            ]);

            return $reservation->load(['room', 'students']);
        });

        return response()->json([
            'message' => 'Room reservation submitted.',
            'data' => $this->formatReservation($reservation),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if ($student instanceof JsonResponse) {
            return $student;
        }

        $reservations = RoomReservation::query()
            ->with(['room', 'students'])
            ->where('student_id', $student->id)
            ->latest('date')
            ->latest('start_time')
            ->get()
            ->map(fn (RoomReservation $reservation) => $this->formatReservation($reservation));

        return response()->json([
            'message' => 'Room reservations retrieved.',
            'data' => $reservations,
        ]);
    }

    public function show(Request $request, RoomReservation $reservation): JsonResponse
    {
        $owned = $this->ownedReservation($request, $reservation);

        if ($owned instanceof JsonResponse) {
            return $owned;
        }

        return response()->json([
            'message' => 'Room reservation retrieved.',
            'data' => $this->formatReservation($owned->load(['room', 'students'])),
        ]);
    }

    public function destroy(Request $request, RoomReservation $reservation): JsonResponse
    {
        $owned = $this->ownedReservation($request, $reservation);

        if ($owned instanceof JsonResponse) {
            return $owned;
        }

        if ($owned->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending reservations can be cancelled.',
                'data' => null,
            ], 409);
        }

        $owned->status = 'cancelled';
        $owned->save();

        ReservationLog::query()->create([
            'reservation_id' => $owned->id,
            'user_id' => $request->user() instanceof User ? $request->user()->id : null,
            'action' => 'cancelled',
            'meta' => [
                'source' => 'mobile',
                'student_id' => $owned->student_id,
            ],
        ]);

        return response()->json([
            'message' => 'Room reservation cancelled.',
            'data' => $this->formatReservation($owned->load(['room', 'students'])),
        ]);
    }

    private function ownedReservation(Request $request, RoomReservation $reservation): RoomReservation|JsonResponse
    {
        $student = $this->resolveStudent($request);

        if ($student instanceof JsonResponse) {
            return $student;
        }

        if ((int) $reservation->student_id !== (int) $student->id) {
            return response()->json([
                'message' => 'Room reservation not found.',
                'data' => null,
            ], 404);
        }

        return $reservation;
    }

    private function studentContact(Student $student): string
    {
        return $student->mobile_number
            ?: $student->id_number
            ?: 'student-'.$student->id;
    }

    private function parseTime(string $time, string $ampm): Carbon
    {
        return Carbon::createFromFormat('g:i A', trim($time).' '.$ampm, 'Asia/Manila');
    }

    private function formatTime(string $time): string
    {
        return Carbon::parse($time, 'Asia/Manila')->format('H:i:s');
    }

    private function formatReservation(RoomReservation $reservation): array
    {
        return [
            'id' => $reservation->id,
            'room' => [
                'id' => $reservation->room?->id,
                'name' => $reservation->room?->name,
                'capacity' => $reservation->room?->capacity,
            ],
            'date' => Carbon::parse($reservation->date)->toDateString(),
            'start_time' => $this->formatTime((string) $reservation->start_time),
            'end_time' => $this->formatTime((string) $reservation->end_time),
            'status' => $reservation->status,
            'patron_email' => $reservation->patron_email,
            'number_of_students' => $reservation->number_of_students,
            'student_names' => $reservation->students->pluck('name')->values(),
            'notes' => $reservation->notes,
            'approved_at' => $reservation->approved_at ? Carbon::parse($reservation->approved_at)->toDateTimeString() : null,
            'created_at' => $reservation->created_at?->toDateTimeString(),
        ];
    }
}
