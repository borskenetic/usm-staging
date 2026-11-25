<?php

namespace App\Services;

use App\Models\LibraryAttendanceLog;
use App\Models\LibraryEmployee;
use App\Models\LibraryStudent;
use App\Support\UsmIdQrParser;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LibraryVisitScanService
{
    /**
     * Ignore repeat scans for the same patron within this window.
     * USB/QR readers often fire many times while a card is held at the gate.
     */
    public const SCAN_COOLDOWN_SECONDS = 15;

    public function __construct(
        private readonly UsmIdQrParser $qrParser = new UsmIdQrParser,
    ) {}

    /**
     * Resolve a Library patron by QR code or ID number.
     *
     * @return array{student: ?LibraryStudent, employee: ?LibraryEmployee}
     */
    public function resolve(string $rawToken): array
    {
        $token = trim(str_replace("\r", '', $rawToken));

        // 1) Exact payload / Pantas code / bare ID.
        $student = $this->findStudentByToken($token);
        $employee = $student ? null : $this->findEmployeeByToken($token);

        if ($student || $employee) {
            return compact('student', 'employee');
        }

        $parsed = $this->qrParser->parse($rawToken);

        // 2) Student number embedded in any USM QR dialect.
        if ($parsed['student_no']) {
            $student = $this->findStudentByToken($parsed['student_no']);
            if (! $student) {
                $student = $this->findStudentByIdVariants($parsed['student_no']);
            }
        }

        // 3) Name (+ optional course) — required for name-only ARO formats.
        if (! $student && $parsed['full_name']) {
            $student = $this->findStudentByPrintedName(
                $parsed['full_name'],
                $parsed['course']
            );

            if (! $student) {
                $employee = $this->findEmployeeByPrintedName(
                    $parsed['full_name'],
                    $parsed['course']
                );
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
     * Repeat scans within {@see SCAN_COOLDOWN_SECONDS} reuse the latest log
     * instead of flipping status (prevents rapid IN/OUT spam).
     *
     * @return array{
     *     status: string,
     *     log: LibraryAttendanceLog,
     *     student: ?LibraryStudent,
     *     employee: ?LibraryEmployee,
     *     duplicate: bool
     * }
     */
    public function record(?LibraryStudent $student, ?LibraryEmployee $employee, ?string $section = null): array
    {
        if (! $student && ! $employee) {
            throw new \InvalidArgumentException('A library student or employee is required.');
        }

        return DB::transaction(function () use ($student, $employee, $section) {
            if ($student) {
                LibraryStudent::query()->whereKey($student->id)->lockForUpdate()->first();
            } else {
                LibraryEmployee::query()->whereKey($employee->id)->lockForUpdate()->first();
            }

            $now = Carbon::now('Asia/Manila');

            $lastLog = LibraryAttendanceLog::query()
                ->when($student, fn ($query) => $query->where('student_id', $student->id))
                ->when($employee, fn ($query) => $query->where('employee_id', $employee->id))
                ->latest('scanned_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($lastLog && $lastLog->scanned_at) {
                $lastAt = Carbon::parse($lastLog->scanned_at, 'Asia/Manila');
                if ($lastAt->diffInSeconds($now) < self::SCAN_COOLDOWN_SECONDS) {
                    return [
                        'status' => strtoupper((string) $lastLog->status),
                        'log' => $lastLog,
                        'student' => $student,
                        'employee' => $employee,
                        'duplicate' => true,
                    ];
                }
            }

            $status = $lastLog && strtoupper((string) $lastLog->status) === 'IN' ? 'OUT' : 'IN';

            $log = LibraryAttendanceLog::query()->create([
                'student_id' => $student?->id,
                'employee_id' => $employee?->id,
                'status' => $status,
                'section' => $section,
                'scanned_at' => $now,
            ]);

            return [
                'status' => $status,
                'log' => $log,
                'student' => $student,
                'employee' => $employee,
                'duplicate' => false,
            ];
        });
    }

    private function findStudentByToken(string $token): ?LibraryStudent
    {
        if ($token === '') {
            return null;
        }

        return LibraryStudent::query()
            ->where(function ($query) use ($token) {
                $query->where('qrcode', $token)
                    ->orWhere('id_number', $token)
                    ->orWhereRaw('LOWER(TRIM(id_number)) = ?', [strtolower($token)]);
            })
            ->first();
    }

    private function findEmployeeByToken(string $token): ?LibraryEmployee
    {
        if ($token === '') {
            return null;
        }

        return LibraryEmployee::query()
            ->where(function ($query) use ($token) {
                $query->where('qrcode', $token)
                    ->orWhere('employee_id', $token)
                    ->orWhereRaw('LOWER(TRIM(employee_id)) = ?', [strtolower($token)]);
            })
            ->first();
    }

    /**
     * Try common ARO ID variants (22-4467 ↔ 2022-4467).
     */
    private function findStudentByIdVariants(string $studentNo): ?LibraryStudent
    {
        $variants = [$studentNo];

        if (preg_match('/^(\d{2})-(\d+)$/', $studentNo, $m)) {
            $year = (int) $m[1];
            // School years 00-79 → 2000s; 80-99 → 1900s (defensive).
            $fullYear = $year >= 80 ? 1900 + $year : 2000 + $year;
            $variants[] = $fullYear.'-'.$m[2];
        } elseif (preg_match('/^(\d{4})-(\d+)$/', $studentNo, $m)) {
            $variants[] = substr($m[1], -2).'-'.$m[2];
        }

        $variants = array_values(array_unique($variants));

        return LibraryStudent::query()
            ->whereIn('id_number', $variants)
            ->first();
    }

    private function findStudentByPrintedName(string $fullName, ?string $course): ?LibraryStudent
    {
        $normalized = $this->qrParser->normalizeName($fullName);
        if ($normalized === '') {
            return null;
        }

        $exact = LibraryStudent::query()
            ->where('normalized_name', $normalized)
            ->get();

        if ($exact->isEmpty()) {
            // Backfill-style match for rows with null normalized_name.
            $lastname = $this->qrParser->guessLastName($fullName);
            if ($lastname) {
                $exact = LibraryStudent::query()
                    ->where('lastname', $lastname)
                    ->get()
                    ->filter(fn (LibraryStudent $s) => $this->studentNormalizedName($s) === $normalized)
                    ->values();
            }
        }

        $match = $this->pickBestNameMatch($exact, $course);
        if ($match) {
            return $match;
        }

        return $this->findStudentByFuzzyName($fullName, $course, $normalized);
    }

    private function findEmployeeByPrintedName(string $fullName, ?string $course): ?LibraryEmployee
    {
        $normalized = $this->qrParser->normalizeName($fullName);
        if ($normalized === '') {
            return null;
        }

        $exact = LibraryEmployee::query()
            ->where('normalized_name', $normalized)
            ->get();

        return $this->pickBestEmployeeNameMatch($exact, $course);
    }

    /**
     * Handle ARO typos in lastnames (FROTEZ vs FORTEZ) when firstname tokens align.
     */
    private function findStudentByFuzzyName(string $fullName, ?string $course, string $normalized): ?LibraryStudent
    {
        $lastname = $this->qrParser->guessLastName($fullName);
        if (! $lastname || strlen($lastname) < 4) {
            return null;
        }

        $initial = substr($lastname, 0, 1);
        $len = strlen($lastname);

        // Allow distance 2 so single-letter ARO typos / transpositions
        // (FROTEZ ↔ FORTEZ) still resolve when the rest of the name is strong.
        $maxLastDistance = $len >= 5 ? 2 : 1;

        $candidates = LibraryStudent::query()
            ->where('lastname', 'like', $initial.'%')
            ->limit(400)
            ->get()
            ->filter(function (LibraryStudent $student) use ($lastname, $normalized, $maxLastDistance, $len) {
                $dbLast = strtoupper((string) $student->lastname);
                if (abs(strlen($dbLast) - $len) > 1) {
                    return false;
                }
                if (levenshtein($dbLast, $lastname) > $maxLastDistance) {
                    return false;
                }

                // Require strong firstname overlap so we don't grab random near-surnames.
                $studentKey = $this->studentNormalizedName($student);
                if ($studentKey === '') {
                    return false;
                }

                similar_text($studentKey, $normalized, $percent);

                return $percent >= 90.0;
            })
            ->values();

        return $this->pickBestNameMatch($candidates, $course);
    }

    /**
     * @param  Collection<int, LibraryStudent>  $candidates
     */
    private function pickBestNameMatch(Collection $candidates, ?string $course): ?LibraryStudent
    {
        if ($candidates->isEmpty()) {
            return null;
        }

        if ($candidates->count() === 1) {
            return $candidates->first();
        }

        $wanted = $this->qrParser->normalizeCourse($course);
        if ($wanted) {
            $byCourse = $candidates->filter(function (LibraryStudent $student) use ($wanted) {
                $have = $this->qrParser->normalizeCourse($student->course);
                if (! $have) {
                    return false;
                }

                return $have === $wanted
                    || str_starts_with($have, $wanted)
                    || str_starts_with($wanted, $have);
            });

            if ($byCourse->count() === 1) {
                return $byCourse->first();
            }

            if ($byCourse->isNotEmpty()) {
                return $byCourse->first();
            }
        }

        // Ambiguous identical names — refuse rather than clock in the wrong person.
        return null;
    }

    /**
     * @param  Collection<int, LibraryEmployee>  $candidates
     */
    private function pickBestEmployeeNameMatch(Collection $candidates, ?string $course): ?LibraryEmployee
    {
        if ($candidates->isEmpty()) {
            return null;
        }

        if ($candidates->count() === 1) {
            return $candidates->first();
        }

        $wanted = $this->qrParser->normalizeCourse($course);
        if ($wanted) {
            $byCourse = $candidates->filter(function (LibraryEmployee $employee) use ($wanted) {
                $have = $this->qrParser->normalizeCourse($employee->program ?? $employee->department);
                if (! $have) {
                    return false;
                }

                return $have === $wanted
                    || str_starts_with($have, $wanted)
                    || str_starts_with($wanted, $have);
            });

            if ($byCourse->count() === 1) {
                return $byCourse->first();
            }
        }

        return null;
    }

    private function studentNormalizedName(LibraryStudent $student): string
    {
        if (! empty($student->normalized_name)) {
            return (string) $student->normalized_name;
        }

        return $this->qrParser->normalizeName(trim($student->firstname.' '.$student->lastname));
    }
}
