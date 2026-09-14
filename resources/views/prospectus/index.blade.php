@extends('layouts.sidebar')

@section('title', 'Prospectus Manager')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/prospectus/index.css') }}?v={{ filemtime(public_path('css/prospectus/index.css')) }}">
@endsection

@section('content')
@php
    $yearOrdinal = static function (int $level): string {
        return match ($level) {
            1 => '1st Year',
            2 => '2nd Year',
            3 => '3rd Year',
            default => $level.'th Year',
        };
    };
@endphp

<div id="prospectus-page" class="prospectus-page" data-prospectus-root>
    <header class="prospectus-hero">
        <div>
            <h1 class="prospectus-title">Prospectus manager</h1>
            <p class="prospectus-subtitle">Add programs and courses by year level for catalog linking and discovery.</p>
        </div>
    </header>

    @if (session('success'))
        <div class="alert alert-success prospectus-alert">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger prospectus-alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="prospectus-stats">
        <div class="prospectus-stat">
            <span class="prospectus-stat__label">Programs</span>
            <strong class="prospectus-stat__value" data-stat="programs">{{ number_format($programCount) }}</strong>
        </div>
        <div class="prospectus-stat">
            <span class="prospectus-stat__label">Courses</span>
            <strong class="prospectus-stat__value" data-stat="courses">{{ number_format($courseCount) }}</strong>
        </div>
        <div class="prospectus-stat">
            <span class="prospectus-stat__label">Showing</span>
            <strong class="prospectus-stat__value" data-stat="showing">{{ number_format($showingCount) }}</strong>
        </div>
    </div>

    <section class="prospectus-card">
        <h2 class="prospectus-card__title">Add program or strand</h2>
        <form method="POST" action="{{ route('prospectus.storeProgram') }}" class="prospectus-add-form">
            @csrf
            <div class="prospectus-field">
                <label for="program_code">Code</label>
                <input id="program_code" type="text" name="program_code" value="{{ old('program_code') }}"
                       placeholder="e.g. BSIT" required maxlength="50">
            </div>
            <div class="prospectus-field prospectus-field--grow">
                <label for="program_name">Program name</label>
                <input id="program_name" type="text" name="program_name" value="{{ old('program_name') }}"
                       placeholder="Bachelor of Science in Information Technology" required maxlength="255">
            </div>
            <div class="prospectus-field prospectus-field--years">
                <label for="total_years">Years</label>
                <input id="total_years" type="number" name="total_years" value="{{ old('total_years', 4) }}"
                       min="1" max="6" required>
            </div>
            <button type="submit" class="prospectus-btn prospectus-btn--primary">Add program</button>
        </form>
    </section>

    <section class="prospectus-card">
        <h2 class="prospectus-card__title">Search programs &amp; courses</h2>
        <form method="GET" action="{{ route('prospectus.index') }}" class="prospectus-search-form">
            <input type="search" name="q" value="{{ $search }}"
                   placeholder="Program code, name, or course..." aria-label="Search programs and courses">
            <button type="submit" class="prospectus-btn prospectus-btn--primary">Search</button>
            @if ($search !== '')
                <a href="{{ route('prospectus.index') }}" class="prospectus-btn prospectus-btn--ghost">Clear</a>
            @endif
        </form>
    </section>

    <div class="prospectus-list" id="prospectus-list">
        @forelse ($programs as $program)
            @php
                $programCourseCount = $program->years->sum(fn ($year) => $year->courses->count());
            @endphp
            <article class="prospectus-program" data-program-id="{{ $program->id }}" id="program-card-{{ $program->id }}">
                <div class="prospectus-program__head">
                    <div class="prospectus-program__identity">
                        <span class="prospectus-badge">{{ $program->program_code }}</span>
                        <div>
                            <h3 class="prospectus-program__name" id="program-name-{{ $program->id }}">
                                {{ $program->program_name }}
                            </h3>
                            <p class="prospectus-program__meta">
                                {{ (int) $program->total_years }} {{ (int) $program->total_years === 1 ? 'year' : 'years' }}
                                ·
                                <span data-program-course-count="{{ $program->id }}">{{ $programCourseCount }}</span>
                                <span data-program-course-label="{{ $program->id }}">{{ $programCourseCount === 1 ? 'course' : 'courses' }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="prospectus-program__actions">
                        <button type="button"
                                class="prospectus-btn prospectus-btn--outline"
                                data-prospectus-panel="#program-{{ $program->id }}"
                                data-collapse-label="Collapse"
                                data-expand-label="Expand"
                                aria-expanded="true"
                                aria-controls="program-{{ $program->id }}">
                            Collapse
                        </button>
                        <button type="button"
                                class="prospectus-btn prospectus-btn--outline"
                                onclick="openProgramEditModal({{ $program->id }}, @js($program->program_code), @js($program->program_name))">
                            Edit
                        </button>
                        <button type="button"
                                class="prospectus-btn prospectus-btn--danger"
                                onclick="openProgramDeleteModal({{ $program->id }}, @js($program->program_code))">
                            Delete
                        </button>
                    </div>
                </div>

                <div id="program-{{ $program->id }}" class="prospectus-program__body">
                    <div class="prospectus-years">
                        @foreach ($program->years as $year)
                            <div class="prospectus-year" data-year-id="{{ $year->id }}">
                                <div class="prospectus-year__head">
                                    <h4 class="prospectus-year__title">{{ $yearOrdinal((int) $year->year_level) }}</h4>
                                    <span class="prospectus-year__count" data-year-course-count="{{ $year->id }}">
                                        {{ $year->courses->count() }} {{ $year->courses->count() === 1 ? 'course' : 'courses' }}
                                    </span>
                                </div>

                                <ul class="prospectus-course-list" id="year-{{ $year->id }}-list">
                                    @forelse ($year->courses as $course)
                                        @include('prospectus.partials.course_item', ['course' => $course])
                                    @empty
                                        <li class="prospectus-course-empty">No courses yet.</li>
                                    @endforelse
                                </ul>

                                <form method="POST"
                                      action="{{ route('prospectus.storeCourse', $year->id) }}"
                                      class="add-course-form prospectus-add-course"
                                      data-year="{{ $year->id }}"
                                      data-program="{{ $program->id }}">
                                    @csrf
                                    <input type="text" name="course_code" placeholder="Code" required maxlength="50">
                                    <input type="text" name="course_name" placeholder="Course name" required maxlength="255">
                                    <button type="submit" class="prospectus-btn prospectus-btn--soft">
                                        <span class="btn-text">Add</span>
                                        <span class="spinner hidden"></span>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            </article>
        @empty
            <div class="prospectus-empty">
                @if ($search !== '')
                    <p>No programs or courses match “{{ $search }}”.</p>
                    <a href="{{ route('prospectus.index') }}" class="prospectus-btn prospectus-btn--primary">Clear search</a>
                @else
                    <p>No programs yet. Add a program or strand to get started.</p>
                @endif
            </div>
        @endforelse
    </div>
</div>

{{-- Delete course modal --}}
<div id="deleteModal" class="prospectus-modal hidden" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
    <div class="prospectus-modal__dialog">
        <h2 id="deleteModalTitle" class="prospectus-modal__title">Confirm delete</h2>
        <p id="deleteMessage" class="prospectus-modal__text"></p>
        <form id="deleteForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="prospectus-modal__actions">
                <button type="button" onclick="closeDeleteModal()" class="prospectus-btn prospectus-btn--ghost">Cancel</button>
                <button type="submit" id="deleteBtn" class="prospectus-btn prospectus-btn--danger">
                    <span class="btn-text">Delete</span>
                    <span class="spinner hidden"></span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Edit course modal --}}
<div id="editModal" class="prospectus-modal hidden" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
    <div class="prospectus-modal__dialog">
        <h2 id="editModalTitle" class="prospectus-modal__title">Edit course</h2>
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')
            <div class="prospectus-field">
                <label for="editCourseCode">Course code</label>
                <input type="text" id="editCourseCode" name="course_code" required maxlength="50">
            </div>
            <div class="prospectus-field">
                <label for="editCourseName">Course name</label>
                <input type="text" id="editCourseName" name="course_name" required maxlength="255">
            </div>
            <div class="prospectus-modal__actions">
                <button type="button" onclick="closeEditModal()" class="prospectus-btn prospectus-btn--ghost">Cancel</button>
                <button type="submit" id="editBtn" class="prospectus-btn prospectus-btn--primary">
                    <span class="btn-text">Update</span>
                    <span class="spinner hidden"></span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Edit program modal --}}
<div id="editProgramModal" class="prospectus-modal hidden" role="dialog" aria-modal="true" aria-labelledby="editProgramModalTitle">
    <div class="prospectus-modal__dialog">
        <h2 id="editProgramModalTitle" class="prospectus-modal__title">Edit program</h2>
        <form id="editProgramForm" method="POST">
            @csrf
            @method('PUT')
            <div class="prospectus-field">
                <label for="editProgramCode">Code</label>
                <input type="text" name="program_code" id="editProgramCode" required maxlength="50">
            </div>
            <div class="prospectus-field">
                <label for="editProgramName">Program name</label>
                <input type="text" name="program_name" id="editProgramName" required maxlength="255">
            </div>
            <div class="prospectus-modal__actions">
                <button type="button" onclick="closeProgramEditModal()" class="prospectus-btn prospectus-btn--ghost">Cancel</button>
                <button id="editProgramBtn" type="submit" class="prospectus-btn prospectus-btn--primary">
                    <span class="btn-text">Save</span>
                    <span class="spinner hidden"></span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Delete program modal --}}
<div id="deleteProgramModal" class="prospectus-modal hidden" role="dialog" aria-modal="true" aria-labelledby="deleteProgramModalTitle">
    <div class="prospectus-modal__dialog">
        <h2 id="deleteProgramModalTitle" class="prospectus-modal__title">Delete program</h2>
        <p class="prospectus-modal__text">
            Are you sure you want to delete <strong id="deleteProgramCode"></strong>? This also removes its years and courses.
        </p>
        <form id="deleteProgramForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="prospectus-modal__actions">
                <button type="button" onclick="closeProgramDeleteModal()" class="prospectus-btn prospectus-btn--ghost">Cancel</button>
                <button id="deleteProgramBtn" type="submit" class="prospectus-btn prospectus-btn--danger">
                    <span class="btn-text">Delete</span>
                    <span class="spinner hidden"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<div id="toastContainer" class="prospectus-toasts" aria-live="polite"></div>
<script src="{{ asset('js/prospectus.js') }}?v={{ filemtime(public_path('js/prospectus.js')) }}"></script>
@endsection
