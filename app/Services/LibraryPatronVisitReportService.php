<?php

namespace App\Services;

use App\Models\LibraryAttendanceLog;
use App\Models\Program;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LibraryPatronVisitReportService
{
    public const SCOPE_STUDENTS = 'students';

    public const SCOPE_EMPLOYEES = 'employees';

    /**
     * @return array{0:?string,1:?string}
     */
    protected function normalizeDateRange(?string $from, ?string $to): array
    {
        $tz = 'Asia/Manila';

        try {
            $fromDt = $from ? Carbon::parse($from, $tz)->startOfDay() : null;
        } catch (\Throwable $e) {
            $fromDt = null;
        }

        try {
            $toDt = $to ? Carbon::parse($to, $tz)->endOfDay() : null;
        } catch (\Throwable $e) {
            $toDt = null;
        }

        if ($fromDt && $toDt && $fromDt->greaterThan($toDt)) {
            [$fromDt, $toDt] = [$toDt->copy()->startOfDay(), $fromDt->copy()->endOfDay()];
        }

        return [
            $fromDt?->toDateTimeString(),
            $toDt?->toDateTimeString(),
        ];
    }

    public function resolveScope(?string $scope): string
    {
        return $scope === self::SCOPE_EMPLOYEES ? self::SCOPE_EMPLOYEES : self::SCOPE_STUDENTS;
    }

    /**
     * @return array<string, Collection<int, object>|Collection>
     */
    public function build(?string $from = null, ?string $to = null, ?string $scope = null): array
    {
        $scope = $this->resolveScope($scope);

        $empty = [
            'topPatronsByIns' => collect(),
            'topPatronsByDistinctInDays' => collect(),
            'programVisitTotals' => collect(),
            'weeklyInsTrend' => collect(),
            'monthlyInsTrend' => collect(),
            'busiestHours' => collect(),
        ];

        if (! Schema::hasTable('library_attendance_logs')) {
            return $empty;
        }

        [$fromDt, $toDt] = $this->normalizeDateRange($from, $to);
        $inExpr = "LOWER(TRIM(library_attendance_logs.status)) = 'in'";

        if ($scope === self::SCOPE_EMPLOYEES) {
            return $this->buildForEmployees($inExpr, $fromDt, $toDt);
        }

        return $this->buildForStudents($inExpr, $fromDt, $toDt);
    }

    /**
     * @return array<string, Collection<int, object>|Collection>
     */
    protected function buildForStudents(string $inExpr, ?string $fromDt, ?string $toDt): array
    {
        $topPatronsByIns = DB::table('library_attendance_logs')
            ->join('library_students', 'library_students.id', '=', 'library_attendance_logs.student_id')
            ->whereRaw($inExpr)
            ->whereNotNull('library_attendance_logs.student_id')
            ->when($fromDt && $toDt, fn ($q) => $q->whereBetween('library_attendance_logs.scanned_at', [$fromDt, $toDt]))
            ->select(
                'library_students.id',
                'library_students.lastname',
                'library_students.firstname',
                'library_students.course as group_key',
                DB::raw('COUNT(*) as ins_count')
            )
            ->groupBy('library_students.id', 'library_students.lastname', 'library_students.firstname', 'library_students.course')
            ->orderByDesc('ins_count')
            ->limit(10)
            ->get();

        $topPatronsByDistinctInDays = DB::table('library_attendance_logs')
            ->join('library_students', 'library_students.id', '=', 'library_attendance_logs.student_id')
            ->whereRaw($inExpr)
            ->whereNotNull('library_attendance_logs.student_id')
            ->when($fromDt && $toDt, fn ($q) => $q->whereBetween('library_attendance_logs.scanned_at', [$fromDt, $toDt]))
            ->select(
                'library_students.id',
                'library_students.lastname',
                'library_students.firstname',
                'library_students.course as group_key',
                DB::raw('COUNT(DISTINCT DATE(library_attendance_logs.scanned_at)) as distinct_in_days')
            )
            ->groupBy('library_students.id', 'library_students.lastname', 'library_students.firstname', 'library_students.course')
            ->orderByDesc('distinct_in_days')
            ->limit(10)
            ->get();

        $registeredByGroup = DB::table('library_students')
            ->whereNotNull('course')
            ->where('course', '!=', '')
            ->select('course as group_key', DB::raw('COUNT(*) as patron_count'))
            ->groupBy('course')
            ->get()
            ->keyBy('group_key');

        $insByGroup = DB::table('library_attendance_logs')
            ->join('library_students', 'library_students.id', '=', 'library_attendance_logs.student_id')
            ->whereRaw($inExpr)
            ->whereNotNull('library_attendance_logs.student_id')
            ->when($fromDt && $toDt, fn ($q) => $q->whereBetween('library_attendance_logs.scanned_at', [$fromDt, $toDt]))
            ->whereNotNull('library_students.course')
            ->where('library_students.course', '!=', '')
            ->select('library_students.course as group_key', DB::raw('COUNT(*) as ins_count'))
            ->groupBy('library_students.course')
            ->get()
            ->keyBy('group_key');

        $programVisitTotals = $this->mergeGroupTotals($registeredByGroup, $insByGroup);
        $trends = $this->buildTrends($fromDt, $toDt, self::SCOPE_STUDENTS);
        $busiestHours = $this->buildBusiestHours($inExpr, $fromDt, $toDt, self::SCOPE_STUDENTS);

        return array_merge([
            'topPatronsByIns' => $topPatronsByIns,
            'topPatronsByDistinctInDays' => $topPatronsByDistinctInDays,
            'programVisitTotals' => $programVisitTotals,
        ], $trends, ['busiestHours' => $busiestHours]);
    }

    /**
     * @return array<string, Collection<int, object>|Collection>
     */
    protected function buildForEmployees(string $inExpr, ?string $fromDt, ?string $toDt): array
    {
        $groupExpr = "COALESCE(NULLIF(TRIM(library_employees.program), ''), NULLIF(TRIM(library_employees.department), ''), 'Unspecified')";

        $topPatronsByIns = DB::table('library_attendance_logs')
            ->join('library_employees', 'library_employees.id', '=', 'library_attendance_logs.employee_id')
            ->whereRaw($inExpr)
            ->whereNotNull('library_attendance_logs.employee_id')
            ->when($fromDt && $toDt, fn ($q) => $q->whereBetween('library_attendance_logs.scanned_at', [$fromDt, $toDt]))
            ->select(
                'library_employees.id',
                'library_employees.lastname',
                'library_employees.firstname',
                DB::raw("{$groupExpr} as group_key"),
                DB::raw('COUNT(*) as ins_count')
            )
            ->groupBy('library_employees.id', 'library_employees.lastname', 'library_employees.firstname', 'library_employees.program', 'library_employees.department')
            ->orderByDesc('ins_count')
            ->limit(10)
            ->get();

        $topPatronsByDistinctInDays = DB::table('library_attendance_logs')
            ->join('library_employees', 'library_employees.id', '=', 'library_attendance_logs.employee_id')
            ->whereRaw($inExpr)
            ->whereNotNull('library_attendance_logs.employee_id')
            ->when($fromDt && $toDt, fn ($q) => $q->whereBetween('library_attendance_logs.scanned_at', [$fromDt, $toDt]))
            ->select(
                'library_employees.id',
                'library_employees.lastname',
                'library_employees.firstname',
                DB::raw("{$groupExpr} as group_key"),
                DB::raw('COUNT(DISTINCT DATE(library_attendance_logs.scanned_at)) as distinct_in_days')
            )
            ->groupBy('library_employees.id', 'library_employees.lastname', 'library_employees.firstname', 'library_employees.program', 'library_employees.department')
            ->orderByDesc('distinct_in_days')
            ->limit(10)
            ->get();

        $registeredByGroup = DB::table('library_employees')
            ->select(DB::raw("{$groupExpr} as group_key"), DB::raw('COUNT(*) as patron_count'))
            ->groupBy(DB::raw($groupExpr))
            ->get()
            ->keyBy('group_key');

        $insByGroup = DB::table('library_attendance_logs')
            ->join('library_employees', 'library_employees.id', '=', 'library_attendance_logs.employee_id')
            ->whereRaw($inExpr)
            ->whereNotNull('library_attendance_logs.employee_id')
            ->when($fromDt && $toDt, fn ($q) => $q->whereBetween('library_attendance_logs.scanned_at', [$fromDt, $toDt]))
            ->select(DB::raw("{$groupExpr} as group_key"), DB::raw('COUNT(*) as ins_count'))
            ->groupBy(DB::raw($groupExpr))
            ->get()
            ->keyBy('group_key');

        $programVisitTotals = $this->mergeGroupTotals($registeredByGroup, $insByGroup);
        $trends = $this->buildTrends($fromDt, $toDt, self::SCOPE_EMPLOYEES);
        $busiestHours = $this->buildBusiestHours($inExpr, $fromDt, $toDt, self::SCOPE_EMPLOYEES);

        return array_merge([
            'topPatronsByIns' => $topPatronsByIns,
            'topPatronsByDistinctInDays' => $topPatronsByDistinctInDays,
            'programVisitTotals' => $programVisitTotals,
        ], $trends, ['busiestHours' => $busiestHours]);
    }

    /**
     * @param  Collection<string, object>  $registeredByGroup
     * @param  Collection<string, object>  $insByGroup
     * @return Collection<int, object>
     */
    protected function mergeGroupTotals(Collection $registeredByGroup, Collection $insByGroup): Collection
    {
        $codes = $registeredByGroup->keys()->merge($insByGroup->keys())->unique()->sort()->values();

        return $codes->map(function ($code) use ($registeredByGroup, $insByGroup) {
            $pc = (int) ($registeredByGroup->get($code)->patron_count ?? 0);
            $ic = (int) ($insByGroup->get($code)->ins_count ?? 0);

            return (object) [
                'group_key' => $code,
                'patron_count' => $pc,
                'ins_count' => $ic,
                'avg_ins_per_patron' => $pc > 0 ? round($ic / $pc, 2) : 0.0,
            ];
        })->sortByDesc('ins_count')->values();
    }

    /**
     * @return array{weeklyInsTrend: Collection<int, object>, monthlyInsTrend: Collection<int, object>}
     */
    protected function buildTrends(?string $fromDt, ?string $toDt, string $scope): array
    {
        $tz = 'Asia/Manila';
        $weeklyInsTrend = collect();

        if ($fromDt && $toDt) {
            $cursor = Carbon::parse($fromDt, $tz)->startOfWeek(Carbon::MONDAY);
            $endCursor = Carbon::parse($toDt, $tz)->endOfWeek(Carbon::SUNDAY)->endOfDay();
            while ($cursor->lte($endCursor)) {
                $start = $cursor->copy()->startOfWeek(Carbon::MONDAY);
                $end = $cursor->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
                $rangeStart = max($start->toDateTimeString(), $fromDt);
                $rangeEnd = min($end->toDateTimeString(), $toDt);

                $weeklyInsTrend->push((object) [
                    'label' => $start->format('M j').' – '.$end->format('M j, Y'),
                    'count' => $this->countInsBetween($rangeStart, $rangeEnd, $scope),
                ]);

                $cursor->addWeek();
            }
        } else {
            for ($i = 11; $i >= 0; $i--) {
                $start = Carbon::now($tz)->subWeeks($i)->startOfWeek(Carbon::MONDAY);
                $end = $start->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
                $weeklyInsTrend->push((object) [
                    'label' => $start->format('M j').' – '.$end->format('M j, Y'),
                    'count' => $this->countInsBetween($start->toDateTimeString(), $end->toDateTimeString(), $scope),
                ]);
            }
        }

        $monthlyInsTrend = collect();
        if ($fromDt && $toDt) {
            $cursor = Carbon::parse($fromDt, $tz)->startOfMonth();
            $endCursor = Carbon::parse($toDt, $tz)->endOfMonth()->endOfDay();
            while ($cursor->lte($endCursor)) {
                $start = $cursor->copy()->startOfMonth();
                $end = $cursor->copy()->endOfMonth()->endOfDay();
                $rangeStart = max($start->toDateTimeString(), $fromDt);
                $rangeEnd = min($end->toDateTimeString(), $toDt);

                $monthlyInsTrend->push((object) [
                    'label' => $start->format('F Y'),
                    'count' => $this->countInsBetween($rangeStart, $rangeEnd, $scope),
                ]);

                $cursor->addMonth();
            }
        } else {
            for ($i = 11; $i >= 0; $i--) {
                $start = Carbon::now($tz)->subMonths($i)->startOfMonth();
                $end = $start->copy()->endOfMonth()->endOfDay();
                $monthlyInsTrend->push((object) [
                    'label' => $start->format('F Y'),
                    'count' => $this->countInsBetween($start->toDateTimeString(), $end->toDateTimeString(), $scope),
                ]);
            }
        }

        return compact('weeklyInsTrend', 'monthlyInsTrend');
    }

    protected function countInsBetween(string $from, string $to, string $scope): int
    {
        return LibraryAttendanceLog::query()
            ->whereRaw("LOWER(TRIM(status)) = 'in'")
            ->when($scope === self::SCOPE_STUDENTS, fn ($q) => $q->whereNotNull('student_id'))
            ->when($scope === self::SCOPE_EMPLOYEES, fn ($q) => $q->whereNotNull('employee_id'))
            ->whereBetween('scanned_at', [$from, $to])
            ->count();
    }

    protected function hourExpression(string $column = 'scanned_at'): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%H', {$column}) AS INTEGER)"
            : "HOUR({$column})";
    }

    /**
     * @return Collection<int, object>
     */
    protected function buildBusiestHours(string $inExpr, ?string $fromDt, ?string $toDt, string $scope): Collection
    {
        $hourExpr = $this->hourExpression('library_attendance_logs.scanned_at');

        $hourRows = DB::table('library_attendance_logs')
            ->whereRaw($inExpr)
            ->when($scope === self::SCOPE_STUDENTS, fn ($q) => $q->whereNotNull('student_id'))
            ->when($scope === self::SCOPE_EMPLOYEES, fn ($q) => $q->whereNotNull('employee_id'))
            ->when($fromDt && $toDt, fn ($q) => $q->whereBetween('library_attendance_logs.scanned_at', [$fromDt, $toDt]))
            ->select(DB::raw("{$hourExpr} as hr"), DB::raw('COUNT(*) as cnt'))
            ->groupBy(DB::raw($hourExpr))
            ->get()
            ->keyBy(fn ($r) => (int) $r->hr);

        return collect(range(0, 23))->map(function ($h) use ($hourRows) {
            $cnt = (int) ($hourRows->get($h)->cnt ?? 0);

            return (object) [
                'hour' => $h,
                'label' => sprintf('%02d:00–%02d:59', $h, $h),
                'count' => $cnt,
            ];
        })->sortByDesc('count')->values();
    }

    public function streamCsvResponse(?string $from = null, ?string $to = null, ?string $scope = null): StreamedResponse
    {
        $scope = $this->resolveScope($scope);
        $reports = $this->build($from, $to, $scope);
        $programNameByCode = Program::query()->pluck('program_name', 'program_code');
        $isStudents = $scope === self::SCOPE_STUDENTS;
        $label = $isStudents ? 'students' : 'faculty-staff';
        $filename = "library-visit-reports-{$label}-".now()->format('Y-m-d').'.csv';
        $groupLabel = $isStudents ? 'Course code' : 'Program / department';

        return response()->streamDownload(function () use ($reports, $programNameByCode, $isStudents, $groupLabel) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            $w = static function (array $row) use ($out): void {
                fputcsv($out, $row);
            };

            $w([
                $isStudents ? 'Library student visit reports' : 'Library faculty & staff visit reports',
                'Exported at',
                now()->timezone('Asia/Manila')->format('Y-m-d H:i'),
            ]);
            $w([]);

            $w(['# TOP 10 — MOST IN SCANS']);
            $w(['Rank', 'Last name', 'First name', $groupLabel, 'Program name', 'IN count']);
            foreach ($reports['topPatronsByIns'] as $i => $row) {
                $key = $row->group_key ?? '';
                $w([
                    $i + 1,
                    $row->lastname,
                    $row->firstname,
                    $key,
                    $key ? ($programNameByCode->get($key, '')) : '',
                    $row->ins_count,
                ]);
            }
            $w([]);

            $w(['# TOP 10 — DISTINCT DAYS WITH IN']);
            $w(['Rank', 'Last name', 'First name', $groupLabel, 'Program name', 'Distinct days']);
            foreach ($reports['topPatronsByDistinctInDays'] as $i => $row) {
                $key = $row->group_key ?? '';
                $w([
                    $i + 1,
                    $row->lastname,
                    $row->firstname,
                    $key,
                    $key ? ($programNameByCode->get($key, '')) : '',
                    $row->distinct_in_days,
                ]);
            }
            $w([]);

            $w(['# TOTALS BY '.strtoupper($groupLabel)]);
            $w([$groupLabel, 'Program name', 'Registered patrons', 'IN scans', 'Avg INs / patron']);
            foreach ($reports['programVisitTotals'] as $row) {
                $key = $row->group_key ?? '';
                $w([
                    $key,
                    $programNameByCode->get($key, ''),
                    $row->patron_count,
                    $row->ins_count,
                    $row->avg_ins_per_patron ?? 0,
                ]);
            }
            $w([]);

            $w(['# IN SCANS BY WEEK (Asia/Manila)']);
            $w(['Week label', 'IN count']);
            foreach ($reports['weeklyInsTrend'] as $row) {
                $w([$row->label, $row->count]);
            }
            $w([]);

            $w(['# IN SCANS BY MONTH (Asia/Manila)']);
            $w(['Month', 'IN count']);
            foreach ($reports['monthlyInsTrend'] as $row) {
                $w([$row->label, $row->count]);
            }
            $w([]);

            $w(['# BUSIEST HOUR OF DAY (IN scans)']);
            $w(['Hour', 'IN count']);
            foreach ($reports['busiestHours'] as $row) {
                $w([$row->label, $row->count]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
