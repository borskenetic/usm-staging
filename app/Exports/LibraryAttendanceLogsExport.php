<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LibraryAttendanceLogsExport implements FromCollection, WithHeadings
{
    public function __construct(protected Collection $logs) {}

    public function collection(): Collection
    {
        return $this->logs->map(function ($log) {
            $patron = $log->student ?: $log->employee;
            $isStudent = (bool) $log->student;

            return [
                'lastname' => $patron->lastname ?? 'Unknown',
                'firstname' => $patron->firstname ?? 'Unknown',
                'id_number' => $isStudent
                    ? ($patron->id_number ?? '—')
                    : ($patron->employee_id ?? '—'),
                'type' => $isStudent ? 'Student' : 'Employee',
                'program' => $isStudent
                    ? ($patron->course ?? '—')
                    : ($patron->program ?? $patron->department ?? '—'),
                'year' => $isStudent
                    ? ($patron->year ?? '—')
                    : ($patron->year_start_work ?? '—'),
                'status' => strtoupper((string) $log->status),
                'scanned_at' => $log->scanned_at
                    ? Carbon::parse($log->scanned_at)->timezone('Asia/Manila')->format('Y-m-d h:i A')
                    : '—',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Last Name',
            'First Name',
            'ID Number',
            'Type',
            'Program',
            'Year',
            'Status',
            'Scanned At',
        ];
    }
}
