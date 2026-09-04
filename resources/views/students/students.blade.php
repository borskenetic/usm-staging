@extends('layouts.sidebar')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/students/students.css') }}?v={{ filemtime(public_path('css/students/students.css')) }}">
@endsection

@section('content')
<div class="container-fluid px-3 px-lg-4 py-4 students-page" data-patrons-root>
    <div class="patrons-shell">
        <header class="patrons-hero">
            <div>
                <p class="patrons-kicker">Library patrons</p>
                <h1 class="patrons-title">Registered Students</h1>
                <p class="patrons-subtitle">Search, filter, and manage student library accounts.</p>
            </div>
            <div class="patrons-tabs" role="tablist" aria-label="Patron type">
                <a href="{{ route('students.index') }}" class="patrons-tab is-active" data-patrons-tab aria-current="page">Students</a>
                <a href="{{ route('employees.index') }}" class="patrons-tab" data-patrons-tab>Faculty &amp; Staff</a>
            </div>
        </header>

        @if(session('success'))
            <div class="alert alert-success patrons-alert">{{ session('success') }}</div>
        @endif

        <section class="patrons-toolbar">
            <form action="{{ route('students.index') }}" method="GET" class="patrons-filters">
                <div class="patrons-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="text" name="search" class="form-control"
                           placeholder="Search patrons by name…"
                           value="{{ request('search') }}">
                </div>

                <select name="program_id" class="form-select">
                    <option value="">All Courses</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->program_code }}"
                            {{ request('program_id') == $program->program_code ? 'selected' : '' }}>
                            {{ $program->program_name }}
                        </option>
                    @endforeach
                </select>

                <select name="year" class="form-select">
                    <option value="">All Years</option>
                    <option value="1st Year" {{ request('year') == '1st Year' ? 'selected' : '' }}>1st Year</option>
                    <option value="2nd Year" {{ request('year') == '2nd Year' ? 'selected' : '' }}>2nd Year</option>
                    <option value="3rd Year" {{ request('year') == '3rd Year' ? 'selected' : '' }}>3rd Year</option>
                    <option value="4th Year" {{ request('year') == '4th Year' ? 'selected' : '' }}>4th Year</option>
                    <option value="5th Year" {{ request('year') == '5th Year' ? 'selected' : '' }}>5th Year</option>
                    <option value="6th Year" {{ request('year') == '6th Year' ? 'selected' : '' }}>6th Year</option>
                </select>

                <button type="submit" class="btn patrons-btn patrons-btn--primary">
                    <i class="bi bi-funnel" aria-hidden="true"></i>
                    Filter
                </button>
            </form>

            <div class="patrons-actions">
                <a href="{{ route('students.create') }}" class="btn patrons-btn patrons-btn--primary">
                    <i class="bi bi-person-plus" aria-hidden="true"></i>
                    Register Patron
                </a>
                <a href="{{ route('pending.index') }}" class="btn patrons-btn patrons-btn--ghost">
                    <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                    Pending
                </a>
                <a href="{{ route('students.pending.requests') }}" class="btn patrons-btn patrons-btn--ghost">
                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                    Edit requests
                </a>
                <a href="{{ route('students.export') }}" class="btn patrons-btn patrons-btn--soft">
                    <i class="bi bi-download" aria-hidden="true"></i>
                    Export CSV
                </a>
                <form action="{{ route('students.import') }}" method="POST" enctype="multipart/form-data" class="patrons-import">
                    @csrf
                    <label class="patrons-file">
                        <input type="file" name="file" accept=".xlsx,.csv" required>
                        <span><i class="bi bi-upload" aria-hidden="true"></i> Choose file</span>
                    </label>
                    <button type="submit" class="btn patrons-btn patrons-btn--soft">Import</button>
                </form>
            </div>
        </section>

        <section class="patrons-table-card">
            <div class="table-responsive students-table-responsive">
                <table class="table patrons-table align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Patron</th>
                            <th scope="col">Course</th>
                            <th scope="col">Year</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            @php
                                $initials = strtoupper(mb_substr($student->firstname ?? '', 0, 1).mb_substr($student->lastname ?? '', 0, 1));
                                $initials = $initials !== '' ? $initials : '?';
                            @endphp
                            <tr>
                                <td>
                                    <div class="patron-identity">
                                        <div class="patron-avatar" data-initials="{{ $initials }}">
                                            @if($student->profile_picture)
                                                <img
                                                    src="{{ asset($student->profile_picture) }}"
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
                                            <span class="patron-name">{{ $student->lastname }}, {{ $student->firstname }}</span>
                                            <span class="patron-meta">Student</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="patron-chip">{{ $student->course ?: '—' }}</span>
                                </td>
                                <td>
                                    <span class="patron-year">{{ $student->year ?: '—' }}</span>
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
                                                    <a class="dropdown-item students-row-menu__item" href="{{ route('students.edit', $student->id) }}">
                                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                                        <span>Edit</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <form action="{{ route('students.destroy', $student->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
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
                                                    <a class="dropdown-item students-row-menu__item" href="{{ url('idcard/front/' . $student->id) }}" target="_blank">
                                                        <i class="bi bi-credit-card-2-front" aria-hidden="true"></i>
                                                        <span>Front</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item students-row-menu__item" href="{{ url('idcard/back/' . $student->id) }}" target="_blank">
                                                        <i class="bi bi-credit-card-2-back" aria-hidden="true"></i>
                                                        <span>Back</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item students-row-menu__item" href="{{ url('idcard/download/' . $student->id) }}">
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
                                <td colspan="4" class="patrons-empty">No students found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="patrons-footer">
                <div class="patrons-pagination">
                    {{ $students->withQueryString()->links('pagination::bootstrap-5') }}
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
