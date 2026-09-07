@extends('layouts.sidebar')

@section('title', 'Library Attendance Logs')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/library/attendance-logs.css') }}?v={{ filemtime(public_path('css/library/attendance-logs.css')) }}">
@endsection

@php
    $isStudentsTab = ($tab ?? 'students') === 'students';
    $studentsTabUrl = route('library.attendance.logs', ['tab' => 'students']);
    $employeesTabUrl = route('library.attendance.logs', ['tab' => 'employees']);
@endphp

@section('content')
<div class="container-fluid px-3 px-lg-4 py-4 library-logs-page" data-patrons-root>
    <header class="ll-hero">
        <div>
            <p class="ll-kicker">Reports · Library visits</p>
            <h1 class="ll-title">
                {{ $isStudentsTab ? 'Student visit logs' : 'Faculty & Staff visit logs' }}
            </h1>
            <p class="ll-subtitle">
                @if ($isStudentsTab)
                    Library visit IN/OUT scans for registered students.
                    Filter by date, program, or name, then export or open analytics.
                @else
                    Library visit IN/OUT scans for registered faculty and staff.
                    Filter by date, program, or name, then export or open analytics.
                @endif
            </p>
        </div>
        <div class="ll-hero-actions">
            <div class="ll-tabs" role="tablist" aria-label="Patron type">
                <a href="{{ $studentsTabUrl }}"
                   class="ll-tab {{ $isStudentsTab ? 'is-active' : '' }}"
                   data-patrons-tab
                   @if($isStudentsTab) aria-current="page" @endif>
                    Students
                </a>
                <a href="{{ $employeesTabUrl }}"
                   class="ll-tab {{ ! $isStudentsTab ? 'is-active' : '' }}"
                   data-patrons-tab
                   @if(! $isStudentsTab) aria-current="page" @endif>
                    Faculty &amp; Staff
                </a>
            </div>
            <a href="{{ route('library.attendance.scanner') }}" class="ll-btn ll-btn--ghost">
                <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
                Library scanner
            </a>
            <a href="{{ route('book.index') }}" class="ll-btn ll-btn--ghost">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Catalog
            </a>
        </div>
    </header>

    <div class="ll-toolbar">
        <a href="{{ route('library.attendance.reports', ['tab' => $tab]) }}" class="ll-btn ll-btn--active">
            <i class="bi bi-graph-up-arrow" aria-hidden="true"></i>
            Reports &amp; analytics
        </a>
        <a href="{{ url('/library/attendance/logs/export/pdf') }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}" class="ll-btn ll-btn--ghost">
            <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
            Export PDF
        </a>
        <a href="{{ url('/library/attendance/logs/export/excel') }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}" class="ll-btn ll-btn--ghost">
            <i class="bi bi-file-earmark-excel" aria-hidden="true"></i>
            Export Excel
        </a>
    </div>

    <form method="GET" action="{{ route('library.attendance.logs') }}" class="ll-filters">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="ll-filters-grid">
            <div class="ll-field">
                <label for="library-logs-search">Search</label>
                <input
                    id="library-logs-search"
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Name, program, status…"
                    value="{{ request('search') }}"
                >
            </div>

            <div class="ll-field">
                <label for="library-logs-from">From</label>
                <input id="library-logs-from" type="date" name="from" class="form-control" value="{{ request('from') }}">
            </div>

            <div class="ll-field">
                <label for="library-logs-to">To</label>
                <input id="library-logs-to" type="date" name="to" class="form-control" value="{{ request('to') }}">
            </div>

            <div class="ll-field">
                <label for="library-logs-program">Program</label>
                <select id="library-logs-program" name="program" class="form-select">
                    <option value="">All programs</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program }}" @selected(request('program') === $program)>{{ $program }}</option>
                    @endforeach
                </select>
            </div>

            <div class="ll-field">
                <label for="library-logs-year">{{ $isStudentsTab ? 'Year level' : 'Start year' }}</label>
                <select id="library-logs-year" name="year_level" class="form-select">
                    <option value="">{{ $isStudentsTab ? 'All years' : 'All start years' }}</option>
                    @foreach ($years as $year)
                        <option value="{{ $year }}" @selected(request('year_level') === $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>

            <div class="ll-field ll-field--actions">
                <label class="visually-hidden" for="library-logs-submit">Filter actions</label>
                <div class="ll-filter-actions">
                    <button id="library-logs-submit" type="submit" class="ll-btn ll-btn--primary">
                        <i class="bi bi-funnel" aria-hidden="true"></i>
                        Apply filters
                    </button>
                    <a
                        href="{{ route('library.attendance.logs', ['tab' => $tab]) }}"
                        class="ll-btn ll-btn--ghost"
                        id="library-logs-clear"
                    >
                        <i class="bi bi-x-circle" aria-hidden="true"></i>
                        Clear filters
                    </a>
                </div>
            </div>
        </div>
    </form>

    <p class="ll-count">{{ number_format($total) }} {{ \Illuminate\Support\Str::plural('scan', $total) }} found</p>

    <section class="ll-table-card">
        <div class="table-responsive">
            <table class="table ll-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">{{ $isStudentsTab ? 'Student' : 'Employee' }}</th>
                        <th scope="col">Program</th>
                        <th scope="col">{{ $isStudentsTab ? 'Year' : 'Start year' }}</th>
                        <th scope="col">Status</th>
                        <th scope="col">Scanned at</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $patron = $isStudentsTab ? $log->student : $log->employee;
                            $program = $isStudentsTab
                                ? ($patron->course ?? '—')
                                : ($patron->program ?? $patron->department ?? '—');
                            $year = $isStudentsTab
                                ? ($patron->year ?? '—')
                                : ($patron->year_start_work ?? '—');
                            $idNumber = $isStudentsTab
                                ? ($patron->id_number ?? '—')
                                : ($patron->employee_id ?? '—');
                            $meta = $isStudentsTab
                                ? 'ID '.$idNumber
                                : 'ID '.$idNumber.($patron->designation ? ' · '.$patron->designation : '');
                            $status = strtoupper(trim((string) $log->status));
                            $scanned = $log->scanned_at
                                ? $log->scanned_at->timezone('Asia/Manila')
                                : null;
                        @endphp
                        <tr>
                            <td>
                                <span class="ll-patron-name">
                                    {{ $patron?->lastname ?? 'Unknown' }}, {{ $patron?->firstname ?? 'Unknown' }}
                                </span>
                                <span class="ll-patron-id">{{ $meta }}</span>
                            </td>
                            <td><span class="ll-program">{{ $program }}</span></td>
                            <td><span class="ll-year">{{ $year }}</span></td>
                            <td>
                                @if ($status === 'IN')
                                    <span class="ll-status ll-status--in">IN</span>
                                @elseif ($status === 'OUT')
                                    <span class="ll-status ll-status--out">OUT</span>
                                @else
                                    <span class="ll-status ll-status--unknown">{{ $status ?: '—' }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($scanned)
                                    <span class="ll-scan-date">{{ $scanned->format('M j, Y') }}</span>
                                    <span class="ll-scan-time">{{ $scanned->format('g:i A') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="ll-empty">
                                {{ $isStudentsTab ? 'No student visit logs found.' : 'No faculty or staff visit logs found.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="ll-footer">
            <div>{{ $logs->withQueryString()->links('pagination::bootstrap-5') }}</div>
        </div>
    </section>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('js/patrons-tabs.js') }}?v={{ filemtime(public_path('js/patrons-tabs.js')) }}"></script>
@endsection
