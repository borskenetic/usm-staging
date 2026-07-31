@extends('layouts.sidebar')

@section('content')
<div class="container py-4">
    <h1 class="h3 mb-3">Books in circulation</h1>
    <p class="text-muted mb-4">Currently borrowed books. Confirm a return when the student brings the copy back to the desk.</p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($loans->isEmpty())
        <div class="alert alert-info mb-0">No books are currently checked out.</div>
    @else
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Patron</th>
                        <th>Book</th>
                        <th>Copy</th>
                        <th>Checked out</th>
                        <th>Due</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loans as $loan)
                        @php
                            $isOverdue = $loan->due_date && $loan->due_date->lt(now()->startOfDay());
                        @endphp
                        <tr class="{{ $isOverdue ? 'table-danger' : '' }}">
                            <td>
                                <div>{{ $loan->student?->lastname }}, {{ $loan->student?->firstname }}</div>
                                <div class="small text-muted">{{ $loan->student?->id_number }}</div>
                            </td>
                            <td>{{ $loan->book?->title_statement }}</td>
                            <td>
                                <div>{{ $loan->book?->call_number }}</div>
                                <div class="small text-muted">{{ $loan->book?->accession_no }}</div>
                            </td>
                            <td>{{ $loan->timestamp?->format('M j, Y H:i') }}</td>
                            <td>{{ $loan->due_date?->format('M j, Y') ?? '—' }}</td>
                            <td>
                                @if($isOverdue)
                                    <span class="badge bg-danger">Overdue</span>
                                @else
                                    <span class="badge bg-primary">Borrowed</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button
                                    type="button"
                                    class="btn btn-success btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#confirmReturnModal"
                                    data-action="{{ route('checkouts.confirm_return', $loan->id) }}"
                                    data-patron="{{ $loan->student?->lastname }}, {{ $loan->student?->firstname }}"
                                    data-book="{{ $loan->book?->title_statement }}"
                                    data-copy="{{ $loan->book?->call_number }} / {{ $loan->book?->accession_no }}"
                                >
                                    Confirm return
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="modal fade" id="confirmReturnModal" tabindex="-1" aria-labelledby="confirmReturnModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="confirmReturnForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title h5" id="confirmReturnModalLabel">Confirm book return</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Confirm that this copy has been returned by the patron.</p>
                    <dl class="mb-0">
                        <dt>Patron</dt>
                        <dd id="confirmReturnPatron" class="mb-2"></dd>
                        <dt>Book</dt>
                        <dd id="confirmReturnBook" class="mb-2"></dd>
                        <dt>Copy</dt>
                        <dd id="confirmReturnCopy" class="mb-0"></dd>
                    </dl>
                    <p class="small text-muted mt-3 mb-0">If a reservation is waiting, the next patron will be notified automatically.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Confirm return</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('confirmReturnModal')?.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const form = document.getElementById('confirmReturnForm');
    form.action = button.getAttribute('data-action');
    document.getElementById('confirmReturnPatron').textContent = button.getAttribute('data-patron') || '';
    document.getElementById('confirmReturnBook').textContent = button.getAttribute('data-book') || '';
    document.getElementById('confirmReturnCopy').textContent = button.getAttribute('data-copy') || '';
});
</script>
@endpush
@endsection
