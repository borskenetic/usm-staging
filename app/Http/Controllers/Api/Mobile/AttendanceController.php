<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\ResolvesMobileStudent;
use App\Http\Controllers\Controller;
use App\Models\LibraryAttendanceLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AttendanceController extends Controller
{
    use ResolvesMobileStudent;

    /**
     * GET /mobile/attendance/preview
     *
     * Returns the authenticated student's recent visits and monthly visit count.
     */
    public function preview(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if ($student instanceof JsonResponse) {
            return $student;
        }

        // Fetch all attendance logs for this student, oldest first for pairing
        $logs = LibraryAttendanceLog::query()
            ->where('student_id', $student->id)
            ->orderBy('scanned_at')
            ->get();

        // Pair IN/OUT rows into visits
        $visits = $this->pairVisits($logs);

        // Monthly count: visits that started this month
        $now = Carbon::now('Asia/Manila');
        $monthlyVisitCount = $visits->filter(function (array $visit) use ($now) {
            $timeIn = Carbon::parse($visit['time_in']);
            return $timeIn->month === $now->month && $timeIn->year === $now->year;
        })->count();

        // Recent visits: last 20, newest first
        $recentVisits = $visits
            ->sortByDesc(fn (array $visit) => $visit['time_in'])
            ->take(20)
            ->values();

        return response()->json([
            'message' => 'Attendance preview retrieved.',
            'data' => [
                'recent_visits' => $recentVisits,
                'monthly_visit_count' => $monthlyVisitCount,
            ],
        ]);
    }

    /**
     * Pair IN and OUT log rows into visit arrays.
     */
    private function pairVisits(Collection $logs): Collection
    {
        $visits = [];
        $currentIn = null;

        foreach ($logs as $log) {
            $status = strtoupper(trim((string) $log->status));

            if ($status === 'IN') {
                // Start a new visit
                $currentIn = $log;
            } elseif ($status === 'OUT' && $currentIn !== null) {
                // Complete the current visit
                $timeIn = Carbon::parse($currentIn->scanned_at);
                $timeOut = Carbon::parse($log->scanned_at);
                $durationMinutes = (int) $timeIn->diffInMinutes($timeOut);

                $visits[] = [
                    'id' => $currentIn->id,
                    'time_in' => $timeIn->toIso8601String(),
                    'time_out' => $timeOut->toIso8601String(),
                    'duration_minutes' => $durationMinutes,
                ];

                $currentIn = null;
            }
        }

        // Unpaired IN (student is currently inside)
        if ($currentIn !== null) {
            $timeIn = Carbon::parse($currentIn->scanned_at);
            $visits[] = [
                'id' => $currentIn->id,
                'time_in' => $timeIn->toIso8601String(),
                'time_out' => null,
                'duration_minutes' => null,
            ];
        }

        return collect($visits);
    }
}