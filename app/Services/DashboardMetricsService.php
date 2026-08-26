<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AdminActivity;
use App\Models\AttendanceEmployee;
use App\Models\AttendanceFeedback;
use App\Models\AttendanceLog;
use App\Models\AttendancePendingEmployee;
use App\Models\AttendancePendingStudent;
use App\Models\AttendanceStudent;
use App\Models\Book;
use App\Models\BookLog;
use App\Models\LibraryAttendanceLog;
use App\Models\PendingEmployee;
use App\Models\PendingStudent;
use App\Models\RoomReservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class DashboardMetricsService
{
    /** @return array<string, mixed> */
    public function superAdmin(): array
    {
        $staffRoles = [
            'super_admin' => 'Super Admin',
            'library_admin' => 'Library Admin',
            'library_staff' => 'Library Staff',
            'attendance_admin' => 'Attendance Admin',
            'attendance_staff' => 'Attendance Staff',
        ];

        $roleCounts = collect($staffRoles)
            ->mapWithKeys(fn (string $label, string $role): array => [
                $label => User::role($role)->count(),
            ]);

        $totalStaff = User::query()
            ->whereIn('role', array_keys($staffRoles))
            ->orWhereHas('roles', fn (Builder $query) => $query->whereIn('name', array_keys($staffRoles)))
            ->count();

        return [
            'stats' => [
                ['label' => 'Total Staff', 'value' => $totalStaff, 'icon' => 'bi-people'],
                ['label' => 'Active Staff', 'value' => $this->staffStatusCount(true), 'icon' => 'bi-person-check'],
                ['label' => 'Inactive Staff', 'value' => $this->staffStatusCount(false), 'icon' => 'bi-person-x'],
                ['label' => 'Staff by Module', 'value' => $roleCounts->filter()->count(), 'icon' => 'bi-grid-1x2'],
            ],
            'quickActions' => [
                ['label' => 'Create Staff', 'route' => 'users.create', 'icon' => 'bi-person-plus'],
                ['label' => 'View Staff Accounts', 'route' => 'users.index', 'icon' => 'bi-person-gear'],
                ['label' => 'Switch to Library', 'route' => 'dashboard.library-admin', 'icon' => 'bi-book'],
                ['label' => 'Switch to Attendance', 'route' => 'dashboard.attendance-admin', 'icon' => 'bi-clock-history'],
            ],
            'recent' => $this->adminActivities(),
            'charts' => [
                [
                    'id' => 'staffDistributionChart',
                    'title' => 'Staff Distribution',
                    'type' => 'doughnut',
                    'labels' => $roleCounts->keys()->values(),
                    'data' => $roleCounts->values(),
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function libraryAdmin(): array
    {
        return [
            'stats' => [
                ['label' => 'Total Books', 'value' => Book::count(), 'icon' => 'bi-bookshelf'],
                ['label' => 'Available Books', 'value' => Book::where('availability', 'Available')->count(), 'icon' => 'bi-check2-circle'],
                ['label' => 'Borrowed Books', 'value' => Book::where('availability', 'Borrowed')->count(), 'icon' => 'bi-arrow-left-right'],
                ['label' => 'Pending Patrons', 'value' => PendingStudent::count() + PendingEmployee::count(), 'icon' => 'bi-person-plus'],
                ['label' => 'Pending Rooms', 'value' => RoomReservation::where('status', 'pending')->count(), 'icon' => 'bi-hourglass-split'],
                ['label' => 'Outstanding Fines', 'value' => $this->outstandingFineCount(), 'icon' => 'bi-exclamation-circle'],
                ['label' => 'Library Visits Today', 'value' => $this->todayCount(LibraryAttendanceLog::query(), 'scanned_at'), 'icon' => 'bi-door-open'],
            ],
            'quickActions' => [
                ['label' => 'Add Book', 'route' => 'book.create', 'icon' => 'bi-plus-circle'],
                ['label' => 'Pending Patrons', 'route' => 'pending.index', 'parameters' => ['tab' => 'students'], 'icon' => 'bi-person-plus'],
                ['label' => 'Library Scanner', 'route' => 'attendance.scan', 'icon' => 'bi-upc-scan'],
                ['label' => 'Pending Rooms', 'route' => 'rooms.pending', 'icon' => 'bi-calendar-check'],
            ],
            'recent' => $this->circulationActivity(),
            'secondaryRecent' => $this->adminActivities('library'),
            'charts' => [
                $this->dateTrendChart('borrowingTrendChart', 'Borrowing Trend', BookLog::query()->where('status', 'Checked Out'), 'timestamp'),
                $this->dateTrendChart('libraryVisitsChart', 'Library Visits', LibraryAttendanceLog::query(), 'scanned_at'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function libraryStaff(): array
    {
        return [
            'stats' => [
                ['label' => 'Borrowed Today', 'value' => $this->todayCount(BookLog::where('status', 'Checked Out'), 'timestamp'), 'icon' => 'bi-box-arrow-up-right'],
                ['label' => 'Returned Today', 'value' => $this->todayCount(BookLog::where('status', 'Checked In'), 'returned_date'), 'icon' => 'bi-box-arrow-in-down-left'],
                ['label' => 'Due Today', 'value' => BookLog::where('status', 'Checked Out')->whereDate('due_date', today())->count(), 'icon' => 'bi-calendar-day'],
                ['label' => 'Overdue Loans', 'value' => BookLog::where('status', 'Checked Out')->whereDate('due_date', '<', today())->count(), 'icon' => 'bi-alarm'],
                ['label' => 'Library Visits Today', 'value' => $this->todayCount(LibraryAttendanceLog::query(), 'scanned_at'), 'icon' => 'bi-door-open'],
            ],
            'quickActions' => [
                ['label' => 'Books', 'route' => 'book.index', 'icon' => 'bi-grid'],
                ['label' => 'Library Scanner', 'route' => 'attendance.scan', 'icon' => 'bi-upc-scan'],
                ['label' => 'Room Schedule', 'route' => 'rooms.schedule', 'icon' => 'bi-calendar-week'],
                ['label' => 'OPAC', 'route' => 'landing', 'icon' => 'bi-search'],
            ],
            'recent' => $this->circulationActivity(),
            'charts' => [
                $this->statusChart('libraryInOutChart', 'Today Library IN/OUT', LibraryAttendanceLog::query(), 'scanned_at'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function attendanceAdmin(): array
    {
        return [
            'stats' => [
                ['label' => 'Scans Today', 'value' => $this->todayCount(AttendanceLog::query(), 'scanned_at'), 'icon' => 'bi-upc-scan'],
                ['label' => 'Currently IN', 'value' => $this->currentlyInCount(AttendanceLog::query()), 'icon' => 'bi-person-check'],
                ['label' => 'Attendance Students', 'value' => AttendanceStudent::count(), 'icon' => 'bi-mortarboard'],
                ['label' => 'Attendance Employees', 'value' => AttendanceEmployee::count(), 'icon' => 'bi-person-workspace'],
                ['label' => 'Pending Registrations', 'value' => AttendancePendingStudent::count() + AttendancePendingEmployee::count(), 'icon' => 'bi-person-plus'],
                ['label' => 'Feedback Responses', 'value' => AttendanceFeedback::count(), 'icon' => 'bi-chat-square-text'],
            ],
            'quickActions' => [
                ['label' => 'Scanner', 'route' => 'attendance.scan', 'icon' => 'bi-upc-scan'],
                ['label' => 'Logs', 'route' => 'attendance_logs.index', 'icon' => 'bi-list-check'],
                ['label' => 'Absences', 'route' => 'attendance_logs.absences', 'icon' => 'bi-person-x'],
                ['label' => 'Reports', 'route' => 'attendance_logs.reports.hub', 'icon' => 'bi-bar-chart'],
                ['label' => 'Pending Registrations', 'route' => 'attendance.pending.index', 'icon' => 'bi-person-plus'],
            ],
            'recent' => $this->attendanceActivity(),
            'charts' => [
                $this->dateTrendChart('attendanceTrendChart', 'Attendance Trend', AttendanceLog::query(), 'scanned_at'),
                $this->statusChart('attendanceInOutChart', 'Today IN vs OUT', AttendanceLog::query(), 'scanned_at'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function attendanceStaff(): array
    {
        $lastScan = AttendanceLog::query()->latest('scanned_at')->first();

        return [
            'stats' => [
                ['label' => 'Scans Today', 'value' => $this->todayCount(AttendanceLog::query(), 'scanned_at'), 'icon' => 'bi-upc-scan'],
                ['label' => 'Currently IN', 'value' => $this->currentlyInCount(AttendanceLog::query()), 'icon' => 'bi-person-check'],
                ['label' => 'Last Scan Time', 'value' => $lastScan?->scanned_at?->timezone('Asia/Manila')->format('h:i A') ?? 'None', 'icon' => 'bi-clock'],
                ['label' => 'Feedback Today', 'value' => $this->todayCount(AttendanceFeedback::query(), 'created_at'), 'icon' => 'bi-chat-square-text'],
            ],
            'quickActions' => [
                ['label' => 'Open Scanner', 'route' => 'attendance.scan', 'icon' => 'bi-upc-scan'],
                ['label' => 'Change Video', 'route' => 'attendance.changeVideo', 'icon' => 'bi-camera-video'],
                ['label' => 'Feedback Settings', 'route' => 'attendance.feedback.settings', 'icon' => 'bi-gear'],
            ],
            'recent' => $this->attendanceActivity(),
            'charts' => [
                $this->hourlyChart('attendanceHourlyChart', 'Hourly Scans Today', AttendanceLog::query(), 'scanned_at'),
            ],
        ];
    }

    private function staffStatusCount(bool $active): int
    {
        return User::query()
            ->where('is_active', $active)
            ->where(function (Builder $query): void {
                $query->whereIn('role', ['super_admin', 'library_admin', 'library_staff', 'attendance_admin', 'attendance_staff'])
                    ->orWhereHas('roles', fn (Builder $roleQuery) => $roleQuery->whereIn('name', ['super_admin', 'library_admin', 'library_staff', 'attendance_admin', 'attendance_staff']));
            })
            ->count();
    }

    private function outstandingFineCount(): int
    {
        return BookLog::query()
            ->where(function (Builder $query): void {
                $query->where('fine_balance', '>', 0)
                    ->orWhere(function (Builder $nested): void {
                        $nested->whereNull('fine_balance')->where('fine_incurred', '>', 0);
                    });
            })
            ->whereNull('fine_cleared_at')
            ->count();
    }

    private function todayCount(Builder $query, string $column): int
    {
        return (clone $query)->whereDate($column, today())->count();
    }

    private function currentlyInCount(Builder $query): int
    {
        return (clone $query)
            ->whereDate('scanned_at', today())
            ->where('status', 'IN')
            ->count();
    }

    /** @return Collection<int, AdminActivity> */
    private function adminActivities(?string $module = null): Collection
    {
        return AdminActivity::query()
            ->with('user')
            ->when($module, fn (Builder $query) => $query->where('module', $module))
            ->latest()
            ->take(6)
            ->get();
    }

    /** @return Collection<int, BookLog> */
    private function circulationActivity(): Collection
    {
        return BookLog::query()
            ->with('book', 'student')
            ->latest('timestamp')
            ->take(6)
            ->get();
    }

    /** @return Collection<int, AttendanceLog> */
    private function attendanceActivity(): Collection
    {
        return AttendanceLog::query()
            ->with('student', 'employee')
            ->latest('scanned_at')
            ->take(6)
            ->get();
    }

    /** @return array<string, mixed> */
    private function dateTrendChart(string $id, string $title, Builder $query, string $column): array
    {
        $days = collect(range(6, 0))->map(fn (int $offset): CarbonImmutable => CarbonImmutable::today()->subDays($offset));

        return [
            'id' => $id,
            'title' => $title,
            'type' => 'line',
            'labels' => $days->map(fn (CarbonImmutable $day): string => $day->format('M j'))->values(),
            'data' => $days->map(fn (CarbonImmutable $day): int => (clone $query)->whereDate($column, $day)->count())->values(),
        ];
    }

    /** @return array<string, mixed> */
    private function statusChart(string $id, string $title, Builder $query, string $column): array
    {
        return [
            'id' => $id,
            'title' => $title,
            'type' => 'bar',
            'labels' => ['IN', 'OUT'],
            'data' => [
                (clone $query)->whereDate($column, today())->where('status', 'IN')->count(),
                (clone $query)->whereDate($column, today())->where('status', 'OUT')->count(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function hourlyChart(string $id, string $title, Builder $query, string $column): array
    {
        $hours = collect(range(7, 19));

        return [
            'id' => $id,
            'title' => $title,
            'type' => 'bar',
            'labels' => $hours->map(fn (int $hour): string => CarbonImmutable::createFromTime($hour)->format('g A'))->values(),
            'data' => $hours->map(function (int $hour) use ($query, $column): int {
                $start = CarbonImmutable::today()->setTime($hour, 0);
                $end = $start->addHour();

                return (clone $query)->where($column, '>=', $start)->where($column, '<', $end)->count();
            })->values(),
        ];
    }
}
