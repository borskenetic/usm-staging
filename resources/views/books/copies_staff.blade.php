@extends('layouts.sidebar')

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="{{ asset('css/books/index.css') }}">
@endsection

@push('styles')
    <style>
        /* Prevent layout shift when dropdown increases page height (keeps scrollbar stable). */
        html { scrollbar-gutter: stable; }
        body { overflow-y: scroll; }
    </style>
@endpush

@section('content')
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('book.index') }}" class="btn btn-outline-secondary">← Back to Books</a>
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Previous page</a>
    </div>

    <h3 class="mb-1">
        Copies of: <strong>{{ $title }}</strong>
    </h3>
    <div class="text-muted mb-3">{{ $author }} — {{ $year }}</div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle mb-0 table-book-list table-book-copies">
            <thead class="table-dark">
                <tr>
                    <th>Accession No</th>
                    <th>Barcode</th>
                    <th>RFID</th>
                    <th>Availability</th>
                    <th>Date Added</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($copies as $copy)
                    <tr>
                        <td>{{ $copy->accession_no }}</td>
                        <td>{{ $copy->barcode }}</td>
                        <td>{{ $copy->rfid }}</td>
                        <td class="book-status-cell">
                            <span class="book-status-badge {{ $copy->availability === 'Available' ? 'book-status-badge--available' : 'book-status-badge--borrowed' }}">
                                <i class="bi {{ $copy->availability === 'Available' ? 'bi-check-circle' : 'bi-clock-history' }}" aria-hidden="true"></i>
                                <span>{{ $copy->availability }}</span>
                            </span>
                        </td>
                        <td>{{ $copy->created_at?->format('Y-m-d') }}</td>
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
                                        <div class="modal-body">
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
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No copies found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-center mt-3">
        {{ $copies->links('pagination::bootstrap-5') }}
    </div>

    <script src="{{ asset('js/books-row-actions.js') }}"></script>
@endsection
