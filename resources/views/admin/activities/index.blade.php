@extends('layouts.sidebar')

@section('title', 'Activity log')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/library/activity-log.css') }}?v={{ filemtime(public_path('css/library/activity-log.css')) }}">
@endsection

@php
    $filterParams = array_filter([
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
    ]);
@endphp

@section('content')
<div class="container-fluid px-3 px-lg-4 py-4 activity-page">
    <header class="activity-page__head d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <p class="activity-page__kicker">Library Attendance · Reports</p>
            <h1 class="activity-page__title">Activity log</h1>
            <p class="activity-page__subtitle">Patron notifications and staff actions, separated by tab.</p>
        </div>
        <div class="activity-page__actions">
            <a href="{{ route('book.index') }}" class="activity-btn">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Catalog
            </a>
        </div>
    </header>

    <form method="GET" action="{{ route('library.attendance.activities') }}" class="activity-filter">
        <input type="hidden" name="category" value="{{ $category }}">
        <div class="activity-filter__grid">
            <div class="activity-filter__field">
                <label for="date_from">From</label>
                <input type="date" name="date_from" id="date_from" value="{{ $dateFrom }}">
            </div>
            <div class="activity-filter__field">
                <label for="date_to">To</label>
                <input type="date" name="date_to" id="date_to" value="{{ $dateTo }}">
            </div>
            <div class="activity-filter__actions">
                <button type="submit" class="activity-btn activity-btn--primary">Apply</button>
                <a href="{{ route('library.attendance.activities', ['category' => $category]) }}" class="activity-btn">Clear dates</a>
            </div>
        </div>
    </form>

    <ul class="nav activity-pills" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $category === 'patron' ? 'active' : '' }}"
               href="{{ route('library.attendance.activities', array_merge($filterParams, ['category' => 'patron'])) }}">
                Patron notifications
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $category === 'staff' ? 'active' : '' }}"
               href="{{ route('library.attendance.activities', array_merge($filterParams, ['category' => 'staff'])) }}">
                Staff activity
            </a>
        </li>
    </ul>

    <div class="activity-feed-card">
        <div class="list-group list-group-flush">
            @include('admin.activities.partials.feed', ['activities' => $activities, 'category' => $category])
        </div>
    </div>

    <div class="mt-3">
        {{ $activities->links() }}
    </div>
</div>
@endsection
