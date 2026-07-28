<?php

namespace Database\Seeders;

use App\Models\AttendanceEmployee;
use App\Models\AttendanceLog;
use App\Models\AttendanceStudent;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceLogSeeder extends Seeder
{
    private const TIMEZONE = 'Asia/Manila';

    /**
     * Seed attendance logs for all students and employees.
     */
    public function run(): void
    {
        $this->seedStudentLogs();
        $this->seedEmployeeLogs();

        $this->command?->info('Attendance logs seeded for all students and employees.');
    }

    private function seedStudentLogs(): void
    {
        $students = AttendanceStudent::query()->get();

        if ($students->isEmpty()) {
            $this->command?->warn('No attendance students found. Skipping student logs.');

            return;
        }

        DB::transaction(function () use ($students) {
            AttendanceLog::query()->whereNotNull('student_id')->delete();

            $now = Carbon::now(self::TIMEZONE);
            $rows = [];

            foreach ($students->values() as $index => $student) {
                $visitCount = max(10, 50 - ($index * 3));

                for ($visit = 0; $visit < $visitCount; $visit++) {
                    $scanIn = $this->scanInTime($now, $index, $visit);
                    $scanOut = $scanIn->copy()->addMinutes(30 + (($index + $visit) % 10) * 20);

                    $rows[] = $this->logRow($scanIn, 'IN', studentId: $student->id);

                    // ~90% of visits have an OUT record
                    if (($visit + $index) % 10 !== 0) {
                        $rows[] = $this->logRow($scanOut, 'OUT', studentId: $student->id);
                    }
                }
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                AttendanceLog::query()->insert($chunk);
            }
        });

        $this->command?->info(sprintf('Seeded attendance logs for %d students.', $students->count()));
    }

    private function seedEmployeeLogs(): void
    {
        $employees = AttendanceEmployee::query()->get();

        if ($employees->isEmpty()) {
            $this->command?->warn('No attendance employees found. Skipping employee logs.');

            return;
        }

        DB::transaction(function () use ($employees) {
            AttendanceLog::query()->whereNotNull('employee_id')->delete();

            $now = Carbon::now(self::TIMEZONE);
            $rows = [];

            foreach ($employees->values() as $index => $employee) {
                $visitCount = max(15, 60 - ($index * 5));

                for ($visit = 0; $visit < $visitCount; $visit++) {
                    $scanIn = $this->scanInTime($now, $index + 100, $visit);
                    $scanOut = $scanIn->copy()->addMinutes(60 + (($index + $visit) % 8) * 30);

                    $rows[] = $this->logRow($scanIn, 'IN', employeeId: $employee->id);

                    // ~85% of visits have an OUT record
                    if (($visit + $index) % 7 !== 0) {
                        $rows[] = $this->logRow($scanOut, 'OUT', employeeId: $employee->id);
                    }
                }
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                AttendanceLog::query()->insert($chunk);
            }
        });

        $this->command?->info(sprintf('Seeded attendance logs for %d employees.', $employees->count()));
    }

    private function scanInTime(Carbon $now, int $entityIndex, int $visitIndex): Carbon
    {
        $date = $now->copy()
            ->subDays(($visitIndex * 4) + ($entityIndex * 2))
            ->startOfDay();

        while ($date->isWeekend()) {
            $date->subDay();
        }

        $hour = [8, 9, 10, 13, 14, 15, 16][($visitIndex + $entityIndex) % 7];
        $minute = [0, 5, 10, 15, 20, 30, 45][($visitIndex * 2 + $entityIndex) % 7];

        return $date->setTime($hour, $minute);
    }

    /**
     * @return array<string, string|null>
     */
    private function logRow(
        Carbon $scannedAt,
        string $status = 'IN',
        ?int $studentId = null,
        ?int $employeeId = null,
    ): array {
        $timestamp = $scannedAt->toDateTimeString();

        return [
            'student_id' => $studentId !== null ? (string) $studentId : null,
            'employee_id' => $employeeId !== null ? (string) $employeeId : null,
            'status' => $status,
            'section' => null,
            'scanned_at' => $timestamp,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }
}