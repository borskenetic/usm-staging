<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AttendancePendingEmployee;
use App\Models\AttendancePendingStudent;
use App\Models\BookLog;
use App\Models\Feedback;
use App\Models\PendingEmployee;
use App\Models\PendingStudent;
use App\Models\RoomReservation;
use App\Models\StudentEditRequest;
use App\Models\User;
use App\Services\Auth\ModuleAccessService;
use Illuminate\Support\Collection;

final class TopbarNotificationService
{
    public function __construct(private readonly ModuleAccessService $moduleAccess)
    {
    }

    /**
     * Return actionable notifications the user is authorized to open.
     *
     * @return Collection<int, array{title: string, message: string, url: string}>
     */
    public function forUser(User $user): Collection
    {
        $notifications = collect();

        if ($this->moduleAccess->hasLibraryAdminAccess($user)) {
            $pendingPatrons = PendingStudent::query()->count() + PendingEmployee::query()->count();
            $this->addCountNotification(
                $notifications,
                $pendingPatrons,
                'Pending library patrons',
                'patron registration',
                route('pending.index', ['tab' => 'students']),
            );

            $pendingRooms = RoomReservation::query()->where('status', 'pending')->count();
            $this->addCountNotification(
                $notifications,
                $pendingRooms,
                'Pending room reservations',
                'room reservation',
                route('rooms.pending'),
            );

            $pendingEdits = StudentEditRequest::query()->where('status', 'pending')->count();
            $this->addCountNotification(
                $notifications,
                $pendingEdits,
                'Pending profile changes',
                'student profile change',
                route('students.pending.requests'),
            );

            $overdueLoans = BookLog::query()
                ->where('status', 'Checked Out')
                ->whereDate('due_date', '<', today())
                ->count();
            $this->addCountNotification(
                $notifications,
                $overdueLoans,
                'Overdue loans',
                'overdue loan',
                route('logs.index'),
            );

            $unreadFeedback = Feedback::query()->unread()->count();
            $this->addCountNotification(
                $notifications,
                $unreadFeedback,
                'New library feedback',
                'feedback submission',
                route('feedback.index'),
            );
        }

        if ($this->moduleAccess->hasAttendanceAdminAccess($user)) {
            $pendingAttendance = AttendancePendingStudent::query()->count()
                + AttendancePendingEmployee::query()->count();
            $this->addCountNotification(
                $notifications,
                $pendingAttendance,
                'Pending attendance registrations',
                'attendance registration',
                route('attendance.pending.index'),
            );
        }

        return $notifications->values();
    }

    /** @param Collection<int, array{title: string, message: string, url: string}> $notifications */
    private function addCountNotification(
        Collection $notifications,
        int $count,
        string $title,
        string $itemLabel,
        string $url,
    ): void {
        if ($count === 0) {
            return;
        }

        $notifications->push([
            'title' => $title,
            'message' => $count.' '.$itemLabel.($count === 1 ? '' : 's').' need review.',
            'url' => $url,
        ]);
    }
}
