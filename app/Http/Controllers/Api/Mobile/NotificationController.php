<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\BookLog;
use App\Models\BookReservation;
use App\Models\RoomReservation;
use App\Models\Student;
use App\Models\StudentNotification;
use App\Models\User;
use App\Services\Auth\ModuleAccessService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if ($student instanceof JsonResponse) {
            return $student;
        }

        $notifications = collect()
            ->merge($this->borrowNotifications($student))
            ->merge($this->roomReservationNotifications($request, $student))
            ->merge($this->bookReservationNotifications($student))
            ->merge($this->systemNotifications($student))
            ->sortByDesc(fn (array $notification) => $notification['sort_at'])
            ->values()
            ->map(function (array $notification) {
                unset($notification['sort_at']);

                return $notification;
            });

        return response()->json([
            'message' => 'Notifications retrieved.',
            'data' => $notifications,
        ]);
    }

    private function systemNotifications(Student $student): Collection
    {
        return StudentNotification::query()
            ->where('student_id', $student->id)
            ->latest()
            ->limit(20)
            ->get()
            ->map(function (StudentNotification $note) {
                return [
                    'id' => 'system-'.$note->id,
                    'type' => $note->type,
                    'title' => $note->title,
                    'message' => $note->message,
                    'severity' => 'info',
                    'source' => [
                        'kind' => 'system_notification',
                        'id' => $note->id,
                    ],
                    'date' => $note->created_at?->toDateString(),
                    'created_at' => $note->created_at?->toDateTimeString(),
                    'sort_at' => $note->created_at?->timestamp ?? 0,
                ];
            });
    }

    private function borrowNotifications(Student $student): Collection
    {
        $today = Carbon::now('Asia/Manila')->startOfDay();
        $nearDueEnd = $today->copy()->addDays(3);

        $latestIds = DB::table('library_book_logs')
            ->select(DB::raw('MAX(id) as id'))
            ->where('student_id', $student->id)
            ->groupBy('book_id');

        return BookLog::query()
            ->with('book:id,title_statement,main_author,call_number,accession_no,barcode')
            ->whereIn('id', $latestIds)
            ->where('status', 'Checked Out')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $nearDueEnd->toDateString())
            ->get()
            ->map(function (BookLog $log) use ($today) {
                $dueDate = Carbon::parse($log->due_date, 'Asia/Manila')->startOfDay();
                $isOverdue = $dueDate->lt($today);
                $days = $isOverdue ? $dueDate->diffInDays($today) : $today->diffInDays($dueDate);
                $title = $log->book?->title_statement ?? 'Borrowed book';

                return [
                    'id' => 'borrow-'.$log->id,
                    'type' => $isOverdue ? 'borrow_overdue' : 'borrow_due_soon',
                    'title' => $isOverdue ? 'Book overdue' : 'Book due soon',
                    'message' => $isOverdue
                        ? "{$title} is {$days} day(s) overdue."
                        : "{$title} is due in {$days} day(s).",
                    'severity' => $isOverdue ? 'danger' : 'warning',
                    'source' => [
                        'kind' => 'book_log',
                        'id' => $log->id,
                        'book_id' => $log->book_id,
                    ],
                    'date' => $dueDate->toDateString(),
                    'created_at' => $log->updated_at?->toDateTimeString() ?? $log->created_at?->toDateTimeString(),
                    'sort_at' => $isOverdue
                        ? $today->copy()->addDays(1000 + $days)->timestamp
                        : $dueDate->timestamp,
                ];
            });
    }

    private function roomReservationNotifications(Request $request, Student $student): Collection
    {
        return RoomReservation::query()
            ->with('room')
            ->where('student_id', $student->id)
            ->whereIn('status', ['pending', 'approved', 'rejected', 'cancelled'])
            ->latest('updated_at')
            ->limit(20)
            ->get()
            ->map(function (RoomReservation $reservation) {
                $status = (string) $reservation->status;
                $roomName = $reservation->room?->name ?? 'Room reservation';
                $date = Carbon::parse($reservation->date)->toDateString();
                $time = Carbon::parse((string) $reservation->start_time)->format('H:i');

                return [
                    'id' => 'room-'.$reservation->id.'-'.$status,
                    'type' => 'room_reservation_'.$status,
                    'title' => 'Room reservation '.str_replace('_', ' ', $status),
                    'message' => "{$roomName} reservation for {$date} at {$time} is {$status}.",
                    'severity' => match ($status) {
                        'approved' => 'success',
                        'rejected', 'cancelled' => 'danger',
                        default => 'info',
                    },
                    'source' => [
                        'kind' => 'room_reservation',
                        'id' => $reservation->id,
                        'room_id' => $reservation->room_id,
                    ],
                    'date' => $date,
                    'created_at' => $reservation->updated_at?->toDateTimeString() ?? $reservation->created_at?->toDateTimeString(),
                    'sort_at' => $reservation->updated_at?->timestamp ?? $reservation->created_at?->timestamp ?? 0,
                ];
            });
    }

    private function bookReservationNotifications(Student $student): Collection
    {
        return BookReservation::query()
            ->with('book:id,title_statement,main_author,call_number')
            ->where('student_id', $student->id)
            ->whereIn('status', ['ready', 'expired', 'cancelled'])
            ->latest('updated_at')
            ->limit(20)
            ->get()
            ->map(function (BookReservation $reservation) {
                $status = (string) $reservation->status;
                $title = $reservation->book?->title_statement ?? 'Reserved book';

                return [
                    'id' => 'book-reservation-'.$reservation->id.'-'.$status,
                    'type' => 'book_reservation_'.$status,
                    'title' => match ($status) {
                        'ready' => 'Reserved book available',
                        'expired' => 'Reservation expired',
                        'cancelled' => 'Reservation cancelled',
                        default => 'Book reservation',
                    },
                    'message' => match ($status) {
                        'ready' => "Your reserved book \"{$title}\" is now available. Please claim it at the library.",
                        'expired' => "Your reservation for \"{$title}\" has expired.",
                        'cancelled' => "Your reservation for \"{$title}\" was cancelled.",
                        default => "Reservation for \"{$title}\" is {$status}.",
                    },
                    'severity' => match ($status) {
                        'ready' => 'success',
                        'expired' => 'warning',
                        'cancelled' => 'danger',
                        default => 'info',
                    },
                    'source' => [
                        'kind' => 'book_reservation',
                        'id' => $reservation->id,
                        'book_id' => $reservation->book_id,
                    ],
                    'date' => $reservation->updated_at?->toDateString() ?? $reservation->created_at?->toDateString(),
                    'created_at' => $reservation->updated_at?->toDateTimeString() ?? $reservation->created_at?->toDateTimeString(),
                    'sort_at' => $reservation->updated_at?->timestamp ?? $reservation->created_at?->timestamp ?? 0,
                ];
            });
    }

    private function resolveStudent(Request $request): Student|JsonResponse
    {
        $tokenable = $request->user();

        if ($tokenable instanceof Student) {
            return $tokenable;
        }

        if ($tokenable instanceof User) {
            if (app(ModuleAccessService::class)->availableModules($tokenable) !== []) {
                return response()->json([
                    'message' => 'This account is not allowed to use mobile notifications.',
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
}