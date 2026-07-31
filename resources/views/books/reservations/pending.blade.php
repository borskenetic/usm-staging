@extends('layouts.sidebar')

@section('content')
<div class="container py-4">
    <h1 class="h3 mb-3">Book reservation queue</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($reservations->isEmpty())
        <div class="alert alert-info mb-0">No pending or ready book reservations.</div>
    @else
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Patron</th>
                        <th>Book</th>
                        <th>Status</th>
                        <th>Queue</th>
                        <th>Held copy</th>
                        <th>Expires</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reservations as $reservation)
                        <tr class="{{ $reservation->status === 'ready' ? 'table-warning' : '' }}">
                            <td>
                                <div>{{ $reservation->student?->lastname }}, {{ $reservation->student?->firstname }}</div>
                                <div class="small text-muted">{{ $reservation->student?->id_number }}</div>
                            </td>
                            <td>{{ $reservation->book?->title_statement }}</td>
                            <td><span class="badge bg-secondary">{{ $reservation->status }}</span></td>
                            <td>#{{ $reservation->queue_position }}</td>
                            <td>
                                @if($reservation->heldBook)
                                    {{ $reservation->heldBook->call_number }} / {{ $reservation->heldBook->accession_no }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $reservation->hold_expires_at?->format('M j, Y H:i') ?? '—' }}</td>
                            <td class="text-end">
                                @if($reservation->status === 'ready')
                                    <form action="{{ route('books.reservations.fulfill', $reservation->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm">Fulfill at desk</button>
                                    </form>
                                @endif
                                <form action="{{ route('books.reservations.cancel', $reservation->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel this reservation?');">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Cancel</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
