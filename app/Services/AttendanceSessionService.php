<?php

namespace App\Services;

use App\Models\AttendanceEmployee;
use App\Models\AttendanceLog;
use App\Models\AttendanceStudent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceSessionService
{
    public const TZ = 'Asia/Manila';

    public function isInStatus(?string $status): bool
    {
        return $status !== null && strtolower(trim((string) $status)) === 'in';
    }

    public function isOutStatus(?string $status): bool
    {
        return $status !== null && strtolower(trim((string) $status)) === 'out';
    }

    /**
     * If the patron's last scan is still IN from a **previous** calendar day (Manila),
     * insert one OUT at end of that IN day so the next real scan starts as IN again.
     */
    public function closeStaleOpenInForStudent(AttendanceStudent $student): bool
    {
        $last = AttendanceLog::query()
            ->where('student_id', $student->id)
            ->orderByDesc('scanned_at')
            ->orderByDesc('id')
            ->first();

        if (! $last || ! $this->isInStatus($last->status)) {
            return false;
        }

        $inDayStart = Carbon::parse($last->scanned_at)->timezone(self::TZ)->startOfDay();
        $todayStart = Carbon::now(self::TZ)->startOfDay();

        if ($inDayStart->greaterThanOrEqualTo($todayStart)) {
            return false;
        }

        $outAt = Carbon::parse($last->scanned_at)->timezone(self::TZ)->endOfDay();

        AttendanceLog::create([
            'student_id' => $student->id,
            'status' => 'OUT',
            'scanned_at' => $outAt,
        ]);

        return true;
    }

    /**
     * If the employee's last scan is still IN from a previous calendar day (Manila),
     * insert one OUT at end of that IN day so the next real scan starts as IN again.
     */
    public function closeStaleOpenInForEmployee(AttendanceEmployee $employee): bool
    {
        $last = AttendanceLog::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('scanned_at')
            ->orderByDesc('id')
            ->first();

        if (! $last || ! $this->isInStatus($last->status)) {
            return false;
        }

        $inDayStart = Carbon::parse($last->scanned_at)->timezone(self::TZ)->startOfDay();
        $todayStart = Carbon::now(self::TZ)->startOfDay();

        if ($inDayStart->greaterThanOrEqualTo($todayStart)) {
            return false;
        }

        $outAt = Carbon::parse($last->scanned_at)->timezone(self::TZ)->endOfDay();

        AttendanceLog::create([
            'employee_id' => $employee->id,
            'status' => 'OUT',
            'scanned_at' => $outAt,
        ]);

        return true;
    }

    /**
     * Close every patron who is still "IN" from a prior calendar day (batch job).
     */
    public function closeAllStaleOpenIns(): int
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('attendance_logs')) {
            return 0;
        }

        $today = Carbon::now(self::TZ)->toDateString();

        $staleStudentIds = DB::table('attendance_logs as al')
            ->join(DB::raw('(
                SELECT student_id, MAX(id) AS max_id
                FROM attendance_logs
                WHERE student_id IS NOT NULL
                GROUP BY student_id
            ) AS last'), 'last.max_id', '=', 'al.id')
            ->whereRaw("LOWER(TRIM(al.status)) = 'in'")
            ->whereRaw('DATE(al.scanned_at) < ?', [$today])
            ->pluck('al.student_id');

        $staleEmployeeIds = DB::table('attendance_logs as al')
            ->join(DB::raw('(
                SELECT employee_id, MAX(id) AS max_id
                FROM attendance_logs
                WHERE employee_id IS NOT NULL
                GROUP BY employee_id
            ) AS last'), 'last.max_id', '=', 'al.id')
            ->whereRaw("LOWER(TRIM(al.status)) = 'in'")
            ->whereRaw('DATE(al.scanned_at) < ?', [$today])
            ->pluck('al.employee_id');

        $closed = 0;

        foreach ($staleStudentIds as $sid) {
            $student = AttendanceStudent::query()->find($sid);
            if (! $student) {
                continue;
            }
            if ($this->closeStaleOpenInForStudent($student)) {
                $closed++;
            }
        }

        foreach ($staleEmployeeIds as $eid) {
            $employee = AttendanceEmployee::query()->find($eid);
            if (! $employee) {
                continue;
            }
            if ($this->closeStaleOpenInForEmployee($employee)) {
                $closed++;
            }
        }

        return $closed;
    }
}
