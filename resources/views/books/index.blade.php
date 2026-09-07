@extends('layouts.sidebar')

@section('title', 'Books')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/books/index.css') }}">
@endsection
@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@php
    $hasActiveQuery = $hasActiveQuery ?? false;
    $showAll = request()->boolean('show_all');
    $statusFilter = request('status');
@endphp

<div class="books-index-layout">

    {{-- Left sidebar: search, filters, actions --}}
    <aside class="books-index-sidebar card card-border p-3">

        <div class="books-sidebar-section-title">
            <i class="bi bi-search" aria-hidden="true"></i>
            <h6 class="books-sidebar-heading">Find books</h6>
        </div>

        <form action="{{ route('book.index') }}" method="GET" class="books-sidebar-form">
            @if($statusFilter)
                <input type="hidden" name="status" value="{{ $statusFilter }}">
            @endif

            <fieldset class="fieldset">
                <legend class="fieldset-legend">
                    <i class="bi bi-type" aria-hidden="true"></i>
                    Search
                </legend>
                <label class="input input-sm w-full">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="text" name="search"
                           placeholder="Title, author, accession..."
                           value="{{ request('search') }}">
                </label>
                <p class="label">Matches title, author, ISBN, accession, barcode, RFID, and subjects.</p>
            </fieldset>

            <fieldset class="fieldset">
                <legend class="fieldset-legend">
                    <i class="bi bi-mortarboard" aria-hidden="true"></i>
                    Program
                </legend>
                <select name="program" class="select select-sm w-full">
                    <option value="">All programs</option>
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}" {{ (string) request('program') === (string) $program->id ? 'selected' : '' }}>
                            {{ $program->program_name }}
                        </option>
                    @endforeach
                </select>
            </fieldset>

            <fieldset class="fieldset">
                <legend class="fieldset-legend">
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                    Publication year
                </legend>
                <select name="year_filter" class="select select-sm w-full">
                    <option value="">Any year</option>
                    <option value="exact" {{ request('year_filter') == 'exact' ? 'selected' : '' }}>Exact year</option>
                    <option value="before" {{ request('year_filter') == 'before' ? 'selected' : '' }}>Before or during</option>
                    <option value="after" {{ request('year_filter') == 'after' ? 'selected' : '' }}>After or during</option>
                    <option value="between" {{ request('year_filter') == 'between' ? 'selected' : '' }}>Between years</option>
                </select>

                <label class="input input-sm w-full">
                    <i class="bi bi-calendar-event" aria-hidden="true"></i>
                    <input type="number" name="year1" placeholder="Start year"
                           value="{{ request('year1') }}" min="0" max="{{ date('Y') + 1 }}">
                </label>

                <label id="year2Field"
                       class="input input-sm w-full {{ request('year_filter') == 'between' ? '' : 'd-none' }}">
                    <i class="bi bi-calendar-check" aria-hidden="true"></i>
                    <input type="number" name="year2" placeholder="End year"
                           value="{{ request('year2') }}" min="0" max="{{ date('Y') + 1 }}">
                </label>
            </fieldset>

            <fieldset class="d-none" disabled>

            <label class="form-label small text-muted mb-1">Search</label>
            <input type="text" name="search" class="form-control mb-2"
                   placeholder="Title, author, accession…"
                   value="{{ request('search') }}">

            <label class="form-label small text-muted mb-1 mt-2">Program</label>
            <select name="program" class="form-select mb-2">
                <option value="">All programs</option>
                @foreach($programs as $program)
                    <option value="{{ $program->id }}" {{ request('program') == $program->id ? 'selected' : '' }}>
                        {{ $program->program_name }}
                    </option>
                @endforeach
            </select>

            <label class="form-label small text-muted mb-1">Publication year</label>
            <select name="year_filter" class="form-select mb-2">
                <option value="">Year filter</option>
                <option value="exact" {{ request('year_filter') == 'exact' ? 'selected' : '' }}>Exact</option>
                <option value="before" {{ request('year_filter') == 'before' ? 'selected' : '' }}>Before</option>
                <option value="after" {{ request('year_filter') == 'after' ? 'selected' : '' }}>After</option>
                <option value="between" {{ request('year_filter') == 'between' ? 'selected' : '' }}>Between</option>
            </select>

            <input type="number" name="year1" class="form-control mb-2" placeholder="Year"
                   value="{{ request('year1') }}">

            <div id="legacyYear2Field" class="mb-2" style="{{ request('year_filter') == 'between' ? '' : 'display:none;' }}">
                <input type="number" name="year2" class="form-control" placeholder="Year (end)"
                       value="{{ request('year2') }}">
            </div>
            </fieldset>

            <button type="submit" class="btn btn-primary btn-sm w-100 mb-2">
                <i class="bi bi-funnel" aria-hidden="true"></i>
                Search / Apply filters
            </button>

            @if($hasActiveQuery)
                <a href="{{ route('book.index') }}" class="btn btn-outline btn-sm w-100">
                    <i class="bi bi-x-circle" aria-hidden="true"></i>
                    Clear &amp; start over
                </a>
            @endif
        </form>

        <hr class="my-3">

        <div class="books-sidebar-section-title">
            <i class="bi bi-lightning-charge" aria-hidden="true"></i>
            <h6 class="books-sidebar-heading">Quick view</h6>
        </div>
        <nav class="books-sidebar-nav">
            <a href="{{ route('book.index', ['show_all' => 1]) }}"
               class="btn btn-primary w-100 {{ $showAll && !request('search') && !request('program') && !request('year1') && !$statusFilter ? 'active' : '' }}">
                <i class="bi bi-bookshelf" aria-hidden="true"></i>
                Show all books
            </a>
            <a href="{{ route('book.index', array_merge(request()->except('status', 'page'), ['status' => 'Available'])) }}"
               class="btn btn-available w-100 {{ $statusFilter === 'Available' ? 'active' : '' }}">
                <i class="bi bi-check-circle" aria-hidden="true"></i>
                Available
            </a>
            <a href="{{ route('book.index', array_merge(request()->except('status', 'page'), ['status' => 'Borrowed'])) }}"
               class="btn btn-borrowed w-100 {{ $statusFilter === 'Borrowed' ? 'active' : '' }}">
                <i class="bi bi-clock-history" aria-hidden="true"></i>
                Borrowed
            </a>
        </nav>

        <hr class="my-3">

        <div class="books-sidebar-section-title">
            <i class="bi bi-folder2-open" aria-hidden="true"></i>
            <h6 class="books-sidebar-heading">Catalog &amp; collections</h6>
        </div>
        <nav class="books-sidebar-nav">
            <a href="{{ route('book.create') }}" class="btn btn-addbook w-100">
                <i class="bi bi-plus-circle" aria-hidden="true"></i>
                Cataloging
            </a>
            <a href="{{ route('ebooks.index') }}" class="btn btn-e-book w-100">
                <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                View E-Resources
            </a>
            <a href="{{ route('books.archived') }}" class="btn btn-secondary w-100">
                <i class="bi bi-archive" aria-hidden="true"></i>
                Archived
            </a>
            <a href="{{ route('books.trash') }}" class="btn btn-outline-danger w-100">
                <i class="bi bi-trash" aria-hidden="true"></i>
                Trash
            </a>
        </nav>

        <hr class="my-3">

        <div class="books-sidebar-section-title">
            <i class="bi bi-arrow-down-up" aria-hidden="true"></i>
            <h6 class="books-sidebar-heading">Import / export</h6>
        </div>
        <form action="{{ route('books.import') }}" method="POST" enctype="multipart/form-data" class="books-sidebar-form">
            @csrf
            <input type="file" name="file" class="file-input file-input-sm w-full mb-2" required accept=".csv,.xlsx">
            <button type="submit" class="btn btn-import w-100 mb-2">
                <i class="bi bi-upload" aria-hidden="true"></i>
                Import books
            </button>
            @if($hasActiveQuery)
                <a href="{{ route('export.books', request()->query()) }}" class="btn btn-export w-100">
                    <i class="bi bi-download" aria-hidden="true"></i>
                    Export results
                </a>
            @else
                <span class="btn btn-export w-100 disabled" title="Search or filter first to export">
                    <i class="bi bi-download" aria-hidden="true"></i>
                    Export books
                </span>
            @endif
        </form>

    </aside>

    {{-- Main: table only after search/filter --}}
    <main class="books-index-main">

        @if($hasActiveQuery)

            <div class="books-results-summary mb-3">
                <span class="text-muted">
                    Showing {{ $books->total() }} {{ $books->total() === 1 ? 'title' : 'titles' }}
                    @if($showAll && !request('search') && !request('program') && !request('year1') && !$statusFilter)
                        (entire catalog)
                    @endif
                    @if(request('search'))
                        matching “{{ request('search') }}”
                    @endif
                    @if($statusFilter)
                        · {{ $statusFilter }} only
                    @endif
                </span>
            </div>

            <div class="card p-4">
                <div class="table-responsive books-table-responsive">
                    <table class="table table-hover align-middle table-book-list">
                        <thead class="table-dark">
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Year Published</th>
                                <th>Resource Type</th>
                                <th>Copies</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($books as $book)
                                <tr>
                                    <td>{{ $book->title_statement }}</td>
                                    <td>{{ $book->main_author }}</td>
                                    <td>{{ $book->pub_year }}</td>
                                    <td>{{ $book->content_type }}</td>
                                    <td>{{ $book->copies }}</td>

                                    @if($book->copies == 1)
                                        @php $copy = \App\Models\Book::find($book->sample_id); @endphp
                                        <td class="book-status-cell">
                                            <span class="book-status-badge {{ $copy->availability === 'Available' ? 'book-status-badge--available' : 'book-status-badge--borrowed' }}">
                                                <i class="bi {{ $copy->availability === 'Available' ? 'bi-check-circle' : 'bi-clock-history' }}" aria-hidden="true"></i>
                                                <span>{{ $copy->availability }}</span>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="dropdown1">
                                                <button type="button" class="btn btn-neutral btn-sm dropdown1-button books-row-action-btn">
                                                    <i class="bi bi-sliders" aria-hidden="true"></i>
                                                    <span>Actions</span>
                                                </button>
                                                <div class="dropdown1-content">
                                                    <a href="{{ route('book.show', $copy->id) }}" class="dropdown-item1 books-row-action-item">
                                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                                        <span>View</span>
                                                    </a>
                                                    <a href="{{ route('book.edit', $copy->id) }}" class="dropdown-item2 books-row-action-item">
                                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                                        <span>Edit</span>
                                                    </a>
                                                    <form action="{{ route('books.archive', $copy->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item-archive books-row-action-item">
                                                            <i class="bi bi-archive" aria-hidden="true"></i>
                                                            <span>Archive</span>
                                                        </button>
                                                    </form>
                                                    <button class="dropdown-item3 books-row-action-item books-row-action-item--danger" type="button" data-bs-toggle="modal"
                                                        data-bs-target="#deleteModal{{ $copy->id }}">
                                                        <i class="bi bi-trash3" aria-hidden="true"></i>
                                                        <span>Delete</span>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="modal fade" id="deleteModal{{ $copy->id }}" tabindex="-1"
                                                aria-labelledby="deleteModalLabel{{ $copy->id }}" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content rounded-3 shadow-lg">
                                                        <div class="modal-header bg-danger text-white">
                                                            <h5 class="modal-title" id="deleteModalLabel{{ $copy->id }}">Confirm Delete</h5>
                                                            <button type="button" class="btn-close btn-close-white"
                                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body text-wraph">
                                                            Are you sure you want to delete <strong>{{ $copy->title_statement }}</strong>?
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">Cancel</button>
                                                            <form action="{{ route('books.destroy', $copy->id) }}" method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger">Yes, Delete</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    @else
                                        <td></td>
                                        <td class="text-end">
                                            <div class="dropdown1">
                                                <button type="button" class="btn btn-neutral btn-sm dropdown1-button books-row-action-btn">
                                                    <i class="bi bi-sliders" aria-hidden="true"></i>
                                                    <span>Actions</span>
                                                </button>
                                                <div class="dropdown1-content">
                                                    <a href="{{ route('books.copies.staff', [
                                                        'title' => $book->title_statement,
                                                        'author' => $book->main_author,
                                                        'year' => $book->pub_year
                                                    ]) }}" class="dropdown-item1 books-row-action-item">
                                                        <i class="bi bi-collection" aria-hidden="true"></i>
                                                        <span>View Copies</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No books match your search or filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-3">
                    {{ $books->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
            </div>

        @else

            <div class="card card-border books-index-welcome text-center">
                <div class="books-index-welcome-icon mb-3" aria-hidden="true">📚</div>
                <div class="badge badge-soft badge-info mb-2">
                    <i class="bi bi-funnel" aria-hidden="true"></i>
                    Catalog filters
                </div>
                <h5 class="card-title mb-2">Search or filter to view the catalog</h5>
                <p class="text-muted mb-3">
                    Use the panel on the left to search by title or author, filter by program or publication year,
                    or choose <strong>Available</strong> / <strong>Borrowed</strong> to load results here.
                </p>
                <div class="books-welcome-actions">
                    <a href="{{ route('book.index', ['show_all' => 1]) }}" class="btn btn-primary btn-lg">
                        <i class="bi bi-bookshelf" aria-hidden="true"></i>
                        Show all books
                    </a>
                    <div class="books-welcome-quicklinks" aria-label="Quick catalog options">
                        <a href="{{ route('book.index', ['status' => 'Available']) }}" class="btn btn-available">
                            <i class="bi bi-check-circle" aria-hidden="true"></i>
                            Available
                        </a>
                        <a href="{{ route('book.index', ['status' => 'Borrowed']) }}" class="btn btn-borrowed">
                            <i class="bi bi-clock-history" aria-hidden="true"></i>
                            Borrowed
                        </a>
                        <a href="{{ route('ebooks.index') }}" class="btn btn-e-book">
                            <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                            E-Resources
                        </a>
                    </div>
                </div>
            </div>

        @endif

    </main>

</div>

<script>
    document.querySelector('[name="year_filter"]')?.addEventListener('change', function () {
        const el = document.getElementById('year2Field');
        if (el) {
            el.classList.toggle('d-none', this.value !== 'between');
        }
    });
</script>
@endsection
