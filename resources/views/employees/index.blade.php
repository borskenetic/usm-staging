@extends('layouts.sidebar')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/students/students.css') }}?v={{ filemtime(public_path('css/students/students.css')) }}">
@endsection

@section('content')
<div class="container-fluid px-3 px-lg-4 py-4 employees-page" data-patrons-root>
    <div class="patrons-shell">
        <header class="patrons-hero">
            <div>
                <p class="patrons-kicker">Library patrons</p>
                <h1 class="patrons-title">Registered Faculty &amp; Staff</h1>
                <p class="patrons-subtitle">Search, filter, and manage employee library accounts.</p>
            </div>
            <div class="patrons-tabs" role="tablist" aria-label="Patron type">
                <a href="{{ route('students.index') }}" class="patrons-tab" data-patrons-tab>Students</a>
                <a href="{{ route('employees.index') }}" class="patrons-tab is-active" data-patrons-tab aria-current="page">Faculty &amp; Staff</a>
            </div>
        </header>

        @if(session('success'))
            <div class="alert alert-success patrons-alert">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger patrons-alert">{{ session('error') }}</div>
        @endif

        <section class="patrons-toolbar">
            <form action="{{ route('employees.index') }}" method="GET" class="patrons-filters">
                <div class="patrons-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="text" name="search" class="form-control"
                           placeholder="Search patrons by name…"
                           value="{{ request('search') }}">
                </div>

                <select name="program" class="form-select">
                    <option value="">All Programs</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->program_code }}"
                            {{ request('program') == $program->program_code ? 'selected' : '' }}>
                            {{ $program->program_name }}
                        </option>
                    @endforeach
                </select>

                <select name="year_start_work" class="form-select">
                    <option value="">All Start Years</option>
                    @foreach ($workStartYears as $yr)
                        <option value="{{ $yr }}" {{ request('year_start_work') == (string) $yr ? 'selected' : '' }}>
                            {{ $yr }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="btn patrons-btn patrons-btn--primary">
                    <i class="bi bi-funnel" aria-hidden="true"></i>
                    Filter
                </button>
            </form>

            <div class="patrons-actions">
                <a href="{{ route('employees.create') }}" class="btn patrons-btn patrons-btn--primary">
                    <i class="bi bi-person-plus" aria-hidden="true"></i>
                    Register Patron
                </a>
                <a href="{{ route('pending.employees') }}" class="btn patrons-btn patrons-btn--ghost">
                    <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                    Pending
                </a>
            </div>
        </section>

        <section class="patrons-table-card">
            <div class="table-responsive students-table-responsive">
                <table class="table patrons-table align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Patron</th>
                            <th scope="col">Designation</th>
                            <th scope="col">Program</th>
                            <th scope="col">Start year</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($faculty as $employee)
                            @php
                                $initials = strtoupper(mb_substr($employee->firstname ?? '', 0, 1).mb_substr($employee->lastname ?? '', 0, 1));
                                $initials = $initials !== '' ? $initials : '?';
                                $programLabel = $employee->program ?: ($employee->department ?: '—');
                                $designation = $employee->designation ?: ($employee->position ?: '—');
                                $idLabel = $employee->employee_id ? 'ID '.$employee->employee_id : 'Faculty & Staff';
                            @endphp
                            <tr>
                                <td>
                                    <div class="patron-identity">
                                        <div class="patron-avatar" data-initials="{{ $initials }}">
                                            @if($employee->formal_picture)
                                                <img
                                                    src="{{ asset($employee->formal_picture) }}"
                                                    alt=""
                                                    class="profile-img"
                                                    loading="lazy"
                                                    onerror="this.remove(); this.parentElement.classList.add('is-fallback');"
                                                >
                                            @else
                                                <span class="patron-avatar__fallback">{{ $initials }}</span>
                                            @endif
                                        </div>
                                        <div class="patron-identity__text">
                                            <span class="patron-name">{{ $employee->lastname }}, {{ $employee->firstname }}</span>
                                            <span class="patron-meta">{{ $idLabel }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="patron-meta d-inline">{{ $designation }}</span>
                                </td>
                                <td>
                                    <span class="patron-chip">{{ $programLabel }}</span>
                                </td>
                                <td>
                                    <span class="patron-year">{{ $employee->year_start_work ?: '—' }}</span>
                                </td>
                                <td class="text-end">
                                    <div class="patron-row-actions">
                                        <div class="dropdown students-row-dropdown">
                                            <button class="btn patrons-btn patrons-btn--row dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-sliders" aria-hidden="true"></i>
                                                <span>Options</span>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end students-row-menu">
                                                <li>
                                                    <a class="dropdown-item students-row-menu__item" href="{{ route('employees.edit', $employee->id) }}">
                                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                                        <span>Edit</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <form action="{{ route('employees.destroy', $employee->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="dropdown-item students-row-menu__item students-row-menu__item--danger" type="submit">
                                                            <i class="bi bi-trash3" aria-hidden="true"></i>
                                                            <span>Delete</span>
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>

                                        <div class="dropdown students-row-dropdown">
                                            <button class="btn patrons-btn patrons-btn--row-accent dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-person-vcard" aria-hidden="true"></i>
                                                <span>ID card</span>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end students-row-menu">
                                                <li>
                                                    <a class="dropdown-item students-row-menu__item" href="{{ route('employees.id.front', $employee->id) }}" target="_blank">
                                                        <i class="bi bi-credit-card-2-front" aria-hidden="true"></i>
                                                        <span>Front</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item students-row-menu__item" href="{{ route('employees.id.back', $employee->id) }}" target="_blank">
                                                        <i class="bi bi-credit-card-2-back" aria-hidden="true"></i>
                                                        <span>Back</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item students-row-menu__item" href="{{ route('employees.id.download', $employee->id) }}">
                                                        <i class="bi bi-file-earmark-zip" aria-hidden="true"></i>
                                                        <span>Download ZIP</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="patrons-empty">No faculty or staff found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="patrons-footer">
                <div class="patrons-pagination">
                    {{ $faculty->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
                <a href="{{ route('book.index') }}" class="btn patrons-btn patrons-btn--ghost">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    Back to Books
                </a>
            </div>
        </section>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('js/patrons-tabs.js') }}?v={{ filemtime(public_path('js/patrons-tabs.js')) }}"></script>
@endsection
