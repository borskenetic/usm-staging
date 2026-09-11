<?php

namespace App\Services;

use App\Models\LibraryAttendanceLog;
use App\Models\LibraryEmployee;
use App\Models\LibraryStudent;
use Carbon\Carbon;

class LibraryVisitScanService
{
    /**
     * Resolve a Library patron by QR code or ID number.
     *
     * @return array{student: ?LibraryStudent, employee: ?LibraryEmployee}
     */
    public function resolve(string $rawToken): array
    {
        $token = trim(str_replace("\r", '', $rawToken));

        $student = LibraryStudent::query()
            ->where(function ($query) use ($token) {
                $query->where('qrcode', $token)
                    ->orWhere('id_number', $token);
            })
            ->first();

        $employee = $student ? null : LibraryEmployee::query()
            ->where(function ($query) use ($token) {
                $query->where('qrcode', $token)
                    ->orWhere('employee_id', $token);
            })
            ->first();

        if (! $student && ! $employee) {
            $parsed = $this->parseLegacyQr($rawToken);

            if ($parsed['student_no']) {
                $student = LibraryStudent::query()
                    ->where('id_number', $parsed['student_no'])
                    ->first();
            }

            if (! $student && $parsed['full_name']) {
                $normalized = $this->normalizeName($parsed['full_name']);

                $student = LibraryStudent::query()
                    ->where('normalized_name', $normalized)
                    ->first();

                if (! $student) {
                    $employee = LibraryEmployee::query()
                        ->where('normalized_name', $normalized)
                        ->first();
                }
            }
        }

        return [
            'student' => $student,
            'employee' => $employee,
        ];
    }

    /**
     * Record an IN/OUT library visit for the resolved patron.
     *
     * @return array{status: string, log: LibraryAttendanceLog, student: ?LibraryStudent, employee: ?LibraryEmployee}
     */
    public function record(?LibraryStudent $student, ?LibraryEmployee $employee, ?string $section = null): array
    {
        if (! $student && ! $employee) {
            throw new \InvalidArgumentException('A library student or employee is required.');
        }

        $lastLog = LibraryAttendanceLog::query()
            ->when($student, fn ($query) => $query->where('student_id', $student->id))
            ->when($employee, fn ($query) => $query->where('employee_id', $employee->id))
            ->latest('scanned_at')
            ->latest('id')
            ->first();

        $status = $lastLog && strtoupper((string) $lastLog->status) === 'IN' ? 'OUT' : 'IN';

        $log = LibraryAttendanceLog::query()->create([
            'student_id' => $student?->id,
            'employee_id' => $employee?->id,
            'status' => $status,
            'section' => $section,
            'scanned_at' => Carbon::now('Asia/Manila'),
        ]);

        return [
            'status' => $status,
            'log' => $log,
            'student' => $student,
            'employee' => $employee,
        ];
    }

    /**
     * @return array{student_no: ?string, full_name: ?string, course: ?string}
     */
    private function parseLegacyQr(string $raw): array
    {
        $raw = trim(str_replace("\r", '', $raw));

        if (str_contains($raw, "\n")) {
            $lines = array_values(array_filter(array_map('trim', explode("\n", $raw))));

            return [
                'student_no' => $lines[0] ?? null,
                'full_name' => $lines[1] ?? null,
                'course' => $lines[2] ?? null,
            ];
        }

        $parts = array_map('trim', explode(',', $raw));

        if (preg_match('/^\d{2}-\d+$/', $parts[0] ?? '')) {
            return [
                'student_no' => $parts[0] ?? null,
                'full_name' => $parts[1] ?? null,
                'course' => $parts[2] ?? null,
            ];
        }

        return [
            'student_no' => null,
            'full_name' => $parts[0] ?? null,
            'course' => $parts[1] ?? null,
        ];
    }

    private function normalizeName(string $fullName): string
    {
        $name = strtoupper($fullName);
        $name = preg_replace('/[^A-Z\s]/', '', $name) ?? '';
        $name = preg_replace('/\b[A-Z]\b/', '', $name) ?? '';

        return preg_replace('/\s+/', '', $name) ?? '';
    }
}
