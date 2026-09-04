<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Library Attendance Logs</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #15233b; }
        h2 { margin: 0 0 4px; color: #1b5e20; }
        .meta { margin: 0 0 12px; color: #5b6b7c; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #cfd8dc; padding: 6px; text-align: left; }
        th { background-color: #e8f5e9; color: #1b5e20; }
    </style>
</head>
<body>
    <h2>Library Attendance Logs — {{ ($tab ?? 'students') === 'employees' ? 'Faculty & Staff' : 'Students' }}</h2>
    <p class="meta">Exported {{ now('Asia/Manila')->format('M j, Y g:i A') }} · {{ $logs->count() }} scans</p>
    <table>
        <thead>
            <tr>
                <th>Patron</th>
                <th>ID</th>
                <th>Type</th>
                <th>Program</th>
                <th>Year</th>
                <th>Status</th>
                <th>Scanned At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                @php
                    $patron = $log->student ?: $log->employee;
                    $isStudent = (bool) $log->student;
                @endphp
                <tr>
                    <td>{{ $patron?->lastname }}, {{ $patron?->firstname }}</td>
                    <td>{{ $isStudent ? ($patron->id_number ?? '—') : ($patron->employee_id ?? '—') }}</td>
                    <td>{{ $isStudent ? 'Student' : 'Employee' }}</td>
                    <td>{{ $isStudent ? ($patron->course ?? '—') : ($patron->program ?? $patron->department ?? '—') }}</td>
                    <td>{{ $isStudent ? ($patron->year ?? '—') : ($patron->year_start_work ?? '—') }}</td>
                    <td>{{ strtoupper((string) $log->status) }}</td>
                    <td>
                        {{ $log->scanned_at
                            ? \Carbon\Carbon::parse($log->scanned_at)->timezone('Asia/Manila')->format('Y-m-d h:i A')
                            : '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No library attendance logs found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
