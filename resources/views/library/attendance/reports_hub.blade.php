@extends('layouts.sidebar')

@section('title', 'Library Visit Reports')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/library/visit-reports.css') }}?v={{ filemtime(public_path('css/library/visit-reports.css')) }}">
@endsection

@php
    $isStudentsTab = ($tab ?? 'students') === 'students';
    $range = request()->only(['from', 'to']);
    $queryBase = array_merge($range, ['tab' => $tab]);
    $hasRange = request('from') || request('to');
    $studentsHubUrl = route('library.attendance.reports', array_merge($range, ['tab' => 'students']));
    $employeesHubUrl = route('library.attendance.reports', array_merge($range, ['tab' => 'employees']));
    $groupWord = $isStudentsTab ? 'program / course' : 'program / department';
    $focusedReports = [
        [
            'label' => 'Top INs',
            'description' => 'Patrons with the highest number of library IN scans.',
            'only' => 'top-ins',
            'icon' => 'bi-trophy-fill',
            'tone' => 'blue',
        ],
        [
            'label' => 'Distinct IN Days',
            'description' => 'Patrons counted once per calendar day with an IN scan.',
            'only' => 'distinct-days',
            'icon' => 'bi-calendar-check-fill',
            'tone' => 'green',
        ],
        [
            'label' => $isStudentsTab ? 'Program Totals' : 'Dept Totals',
            'description' => 'Registered patrons, IN totals, and average use by '.$groupWord.'.',
            'only' => 'program-totals',
            'icon' => 'bi-diagram-3-fill',
            'tone' => 'amber',
        ],
        [
            'label' => 'Weekly Trend',
            'description' => 'Week-by-week library IN scan movement.',
            'only' => 'weekly',
            'icon' => 'bi-calendar-week-fill',
            'tone' => 'cyan',
        ],
        [
            'label' => 'Monthly Trend',
            'description' => 'Month-by-month library IN scan movement.',
            'only' => 'monthly',
            'icon' => 'bi-calendar3',
            'tone' => 'red',
        ],
        [
            'label' => 'Busiest Hour',
            'description' => 'Hours with the highest library IN scan activity.',
            'only' => 'busiest-hour',
            'icon' => 'bi-clock-fill',
            'tone' => 'slate',
        ],
    ];
@endphp

@section('content')
<div class="container-fluid px-3 px-lg-4 py-4 library-reports-page">
    <header class="lr-hero">
        <div>
            <p class="lr-kicker">Reports · Library visits</p>
            <h1 class="lr-title">
                {{ $isStudentsTab ? 'Student visit reports' : 'Faculty & Staff visit reports' }}
            </h1>
            <p class="lr-subtitle">
                Analytics for library visit IN scans.
                Filter by date, open the full dashboard, or export a combined CSV.
            </p>
        </div>
        <div class="lr-hero-actions">
            <div class="lr-tabs" role="tablist" aria-label="Patron type">
                <a href="{{ $studentsHubUrl }}"
                   class="lr-tab {{ $isStudentsTab ? 'is-active' : '' }}"
                   @if($isStudentsTab) aria-current="page" @endif>
                    Students
                </a>
                <a href="{{ $employeesHubUrl }}"
                   class="lr-tab {{ ! $isStudentsTab ? 'is-active' : '' }}"
                   @if(! $isStudentsTab) aria-current="page" @endif>
                    Faculty &amp; Staff
                </a>
            </div>
            <a href="{{ route('library.attendance.logs', ['tab' => $tab]) }}" class="lr-btn">
                <i class="bi bi-list-check" aria-hidden="true"></i>
                Visit logs
            </a>
        </div>
    </header>

    <div class="lr-shell">
        <section class="lr-info">
            <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
            <div>
                <strong>How reports are counted</strong>
                <p>
                    Summaries use library visit <strong>IN</strong> scans for
                    {{ $isStudentsTab ? 'registered students' : 'registered faculty and staff' }} only.
                    Distinct days count at most one IN per patron per calendar date.
                </p>
            </div>
        </section>

        <section class="lr-card">
            <div class="lr-card-heading">
                <div>
                    <span>Date range</span>
                    <h2>Filter report period</h2>
                </div>
                @if ($hasRange)
                    <span class="lr-range-chip">
                        {{ request('from') ?: 'Start' }} to {{ request('to') ?: 'Today' }}
                    </span>
                @endif
            </div>

            <form method="GET" class="lr-filter-form">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <label>
                    <span>From</span>
                    <input type="date" name="from" value="{{ request('from') }}">
                </label>
                <label>
                    <span>To</span>
                    <input type="date" name="to" value="{{ request('to') }}">
                </label>
                <div class="lr-filter-actions">
                    <button type="submit" class="lr-btn lr-btn--primary">
                        <i class="bi bi-funnel-fill" aria-hidden="true"></i>
                        Apply filter
                    </button>
                    <a href="{{ route('library.attendance.reports', ['tab' => $tab]) }}" class="lr-btn">
                        Clear
                    </a>
                </div>
            </form>
        </section>

        <section class="lr-primary-grid" aria-label="Primary report actions">
            <a href="{{ route('library.attendance.reports.dashboard', $queryBase) }}" class="lr-primary-card">
                <span class="lr-primary-icon"><i class="bi bi-bar-chart-line-fill" aria-hidden="true"></i></span>
                <span>
                    <strong>Open Full Dashboard</strong>
                    <small>Charts and tables for all report categories.</small>
                </span>
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>

            <a href="{{ route('library.attendance.reports.export', $queryBase) }}" class="lr-primary-card lr-primary-card--export">
                <span class="lr-primary-icon"><i class="bi bi-filetype-csv" aria-hidden="true"></i></span>
                <span>
                    <strong>Download Combined CSV</strong>
                    <small>Export the filtered report pack for spreadsheet review.</small>
                </span>
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
        </section>

        <section class="lr-card">
            <div class="lr-section-heading">
                <div>
                    <span>Focused reports</span>
                    <h2>Open one report view</h2>
                </div>
                <p>Use these when you only need one chart and table.</p>
            </div>

            <div class="lr-report-grid">
                @foreach ($focusedReports as $report)
                    <a
                        href="{{ route('library.attendance.reports.dashboard', array_merge($queryBase, ['only' => $report['only']])) }}"
                        class="lr-report-card lr-report-card--{{ $report['tone'] }}"
                    >
                        <span class="lr-report-icon">
                            <i class="bi {{ $report['icon'] }}" aria-hidden="true"></i>
                        </span>
                        <span>
                            <strong>{{ $report['label'] }}</strong>
                            <small>{{ $report['description'] }}</small>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</div>
@endsection
