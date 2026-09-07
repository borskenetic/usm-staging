@extends('layouts.sidebar')

@section('title', 'Library Visit Report Dashboard')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/library/visit-reports.css') }}?v={{ filemtime(public_path('css/library/visit-reports.css')) }}">
@endsection

@php
    $isStudentsTab = ($tab ?? 'students') === 'students';
    $range = request()->only(['from', 'to']);
    $queryBase = array_merge($range, ['tab' => $tab]);
    $hasRange = request('from') || request('to');
    $programInsTotal = collect($programVisitTotals ?? [])->sum(fn ($row) => (int) ($row->ins_count ?? 0));
    $registeredPatrons = collect($programVisitTotals ?? [])->sum(fn ($row) => (int) ($row->patron_count ?? 0));
    $topPatron = collect($topPatronsByIns ?? [])->first();
    $busiestHour = collect($busiestHours ?? [])->first();
@endphp

@section('content')
<div class="container-fluid px-3 px-lg-4 py-4 library-reports-page">
    <header class="lr-hero">
        <div>
            <p class="lr-kicker">Reports · Library visits</p>
            <h1 class="lr-title">
                {{ ! empty($only) ? 'Focused visit dashboard' : ($isStudentsTab ? 'Student visit dashboard' : 'Faculty & Staff visit dashboard') }}
            </h1>
            <p class="lr-subtitle">
                Charts and tables based on library visit IN scans for
                {{ $isStudentsTab ? 'students' : 'faculty and staff' }}.
            </p>
        </div>
        <div class="lr-hero-actions">
            <a href="{{ route('library.attendance.reports', $queryBase) }}" class="lr-btn">
                <i class="bi bi-grid" aria-hidden="true"></i>
                Reports menu
            </a>
            <a href="{{ route('library.attendance.reports.export', $queryBase) }}" class="lr-btn lr-btn--success">
                <i class="bi bi-filetype-csv" aria-hidden="true"></i>
                Export CSV
            </a>
            <a href="{{ route('library.attendance.logs', ['tab' => $tab]) }}" class="lr-btn lr-btn--primary">
                <i class="bi bi-list-check" aria-hidden="true"></i>
                Visit logs
            </a>
        </div>
    </header>

    <div class="lr-shell">
        <section class="lr-info">
            <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
            <div>
                <strong>Counting rules</strong>
                <p>
                    Distinct days count at most one IN per patron per calendar date.
                    Faculty &amp; Staff grouping uses program when present, otherwise department.
                </p>
            </div>
        </section>

        <section class="lr-card lr-toolbar">
            <div>
                <span class="lr-kicker">Date range</span>
                <h2 class="lr-title" style="font-size:1.05rem;">
                    {{ $hasRange ? (request('from') ?: 'Start').' to '.(request('to') ?: 'Today') : 'All available scans' }}
                </h2>
            </div>

            <form method="GET" class="lr-dashboard-filter">
                <input type="hidden" name="tab" value="{{ $tab }}">
                @if (! empty($only))
                    <input type="hidden" name="only" value="{{ $only }}">
                @endif
                <label>
                    <span>From</span>
                    <input type="date" name="from" value="{{ request('from') }}">
                </label>
                <label>
                    <span>To</span>
                    <input type="date" name="to" value="{{ request('to') }}">
                </label>
                <button type="submit" class="lr-btn lr-btn--primary">
                    <i class="bi bi-funnel-fill" aria-hidden="true"></i>
                    Apply
                </button>
                <a href="{{ route('library.attendance.reports.dashboard', array_filter(['tab' => $tab, 'only' => $only ?? null])) }}" class="lr-btn">
                    Clear
                </a>
            </form>
        </section>

        <section class="lr-summary" aria-label="Report summary">
            <div class="lr-summary-card lr-summary-card--blue">
                <span><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i></span>
                <div>
                    <small>IN scans</small>
                    <strong>{{ number_format($programInsTotal) }}</strong>
                </div>
            </div>
            <div class="lr-summary-card lr-summary-card--green">
                <span><i class="bi bi-people-fill" aria-hidden="true"></i></span>
                <div>
                    <small>Registered patrons</small>
                    <strong>{{ number_format($registeredPatrons) }}</strong>
                </div>
            </div>
            <div class="lr-summary-card lr-summary-card--amber">
                <span><i class="bi bi-trophy-fill" aria-hidden="true"></i></span>
                <div>
                    <small>Top patron</small>
                    <strong>{{ $topPatron ? number_format((int) $topPatron->ins_count).' INs' : 'No data' }}</strong>
                </div>
            </div>
            <div class="lr-summary-card lr-summary-card--slate">
                <span><i class="bi bi-clock-fill" aria-hidden="true"></i></span>
                <div>
                    <small>Busiest hour</small>
                    <strong>{{ $busiestHour && (int) $busiestHour->count > 0 ? $busiestHour->label : 'No data' }}</strong>
                </div>
            </div>
        </section>

        @if(!empty($only))
            <div class="lr-focused-bar">
                <span>
                    <i class="bi bi-pin-angle-fill" aria-hidden="true"></i>
                    Showing one focused report
                </span>
                <a href="{{ route('library.attendance.reports.dashboard', $queryBase) }}" class="lr-btn">
                    Open full dashboard
                </a>
            </div>
        @endif

        <div class="lr-body">
            @include('library.attendance.partials.visit_reports_body', [
                'only' => $only ?? null,
                'tab' => $tab,
                'isStudentsTab' => $isStudentsTab,
            ])
        </div>
    </div>
</div>
@endsection
