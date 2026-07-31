@extends('layouts.sidebar')

@section('content')
<div class="container py-4">
    <h1 class="h3 mb-3">Pending mobile borrow requests</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($requests->isEmpty())
        <div class="alert alert-info mb-0">No pending borrow requests.</div>
    @else
        @foreach($requests as $borrowRequest)
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                        <div>
                            <h2 class="h5 mb-1">{{ $borrowRequest->student?->lastname }}, {{ $borrowRequest->student?->firstname }}</h2>
                            <div class="text-muted small">{{ $borrowRequest->student?->id_number }} · {{ $borrowRequest->requested_at?->format('M j, Y H:i') }}</div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>Copy</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($borrowRequest->items as $item)
                                    <tr>
                                        <td>{{ $item->book?->title_statement }}</td>
                                        <td>
                                            <div>{{ $item->book?->call_number }}</div>
                                            <div class="small text-muted">{{ $item->book?->accession_no }}</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $item->status }}</span>
                                            @if($item->rejection_reason)
                                                <div class="small text-muted mt-1">{{ $item->rejection_reason }}</div>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($item->status === 'pending')
                                                <form action="{{ route('checkout.requests.items.approve', $item->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                                </form>
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-danger btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#rejectItemModal"
                                                    data-action="{{ route('checkout.requests.items.reject', $item->id) }}"
                                                    data-book="{{ $item->book?->title_statement }}"
                                                >
                                                    Reject
                                                </button>
                                            @else
                                                <span class="text-muted small">Reviewed</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>

<div class="modal fade" id="rejectItemModal" tabindex="-1" aria-labelledby="rejectItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="rejectItemForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title h5" id="rejectItemModalLabel">Reject book request</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Reject <strong id="rejectItemBook"></strong>?</p>
                    <label for="rejection_reason" class="form-label">Reason (optional)</label>
                    <textarea id="rejection_reason" name="rejection_reason" class="form-control" rows="3" maxlength="1000" placeholder="Optional note for the student"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject book</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('rejectItemModal')?.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const form = document.getElementById('rejectItemForm');
    form.action = button.getAttribute('data-action');
    document.getElementById('rejectItemBook').textContent = button.getAttribute('data-book') || 'this book';
    document.getElementById('rejection_reason').value = '';
});
</script>
@endpush
@endsection
