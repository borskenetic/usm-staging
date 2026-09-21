<?php

namespace App\Imports;

use App\Models\Student;
use Carbon\Carbon;
use DateTimeInterface;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class StudentsImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    private int $nextQrNumber;

    public function __construct()
    {
        $lastStudent = Student::query()
            ->whereNotNull('qrcode')
            ->orderByDesc('id')
            ->first();

        $this->nextQrNumber = 1;

        if ($lastStudent && preg_match('/S-(\d+)/', (string) $lastStudent->qrcode, $matches)) {
            $this->nextQrNumber = (int) $matches[1] + 1;
        }
    }

    public function model(array $row)
    {
        $idNumber = trim((string) ($row['id_number'] ?? ''));

        if ($idNumber === '') {
            return null;
        }

        $name = self::parseFullName((string) ($row['full_name'] ?? ''));
        $birthday = self::parseBirthday($row['birth_date'] ?? null);

        $student = Student::query()->firstOrNew(['id_number' => $idNumber]);

        $student->fill([
            'lastname' => $name['lastname'],
            'firstname' => $name['firstname'],
            'middle_initial' => $name['middle_initial'],
            'birthday' => $birthday,
            'course' => self::nullableString($row['program'] ?? null),
            'year' => self::nullableString($row['year_level'] ?? null),
            'mobile_number' => self::nullableString($row['mobile_no'] ?? null),
            'address' => self::nullableString($row['home_address'] ?? null),
        ]);

        $profilePicture = self::profilePicturePath($row['profile_picture'] ?? null);
        if ($profilePicture !== null) {
            $student->profile_picture = $profilePicture;
        }

        if (! $student->exists || blank($student->qrcode)) {
            $student->qrcode = $this->nextQrCode();
        }

        return $student;
    }

    /**
     * Parse "Lastname, Firstname M." into discrete name fields.
     *
     * @return array{lastname: string, firstname: string, middle_initial: string|null}
     */
    public static function parseFullName(string $fullName): array
    {
        $fullName = trim(preg_replace('/\s+/u', ' ', $fullName) ?? '');

        if ($fullName === '') {
            return [
                'lastname' => '',
                'firstname' => '',
                'middle_initial' => null,
            ];
        }

        if (! str_contains($fullName, ',')) {
            return [
                'lastname' => $fullName,
                'firstname' => '',
                'middle_initial' => null,
            ];
        }

        [$lastname, $rest] = array_map('trim', explode(',', $fullName, 2));
        $firstname = $rest;
        $middleInitial = null;

        if (preg_match('/^(.*)\s+([A-Za-z])\.?$/u', $rest, $matches)) {
            $firstname = trim($matches[1]);
            $middleInitial = strtoupper($matches[2]);
        }

        return [
            'lastname' => $lastname,
            'firstname' => $firstname,
            'middle_initial' => $middleInitial,
        ];
    }

    public static function parseBirthday(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::parse($value)->toDateString();
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
        }

        try {
            return Carbon::parse(trim((string) $value))->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Accept a bare filename (e.g. juan.jpg) and store it under images/profile_pictures/.
     */
    public static function profilePicturePath(mixed $value): ?string
    {
        $filename = self::nullableString($value);

        if ($filename === null) {
            return null;
        }

        $filename = basename(str_replace('\\', '/', $filename));

        if ($filename === '' || $filename === '.' || $filename === '..') {
            return null;
        }

        return 'images/profile_pictures/'.$filename;
    }

    private function nextQrCode(): string
    {
        $code = 'S-'.str_pad((string) $this->nextQrNumber, 8, '0', STR_PAD_LEFT);
        $this->nextQrNumber++;

        return $code;
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
