@extends('layouts.sidebar')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/tailwind-build.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/attendance_logs/index.css') }}">
@endsection

@section('content')

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 class="fw-bold mb-1">Submitted Feedbacks</h2>
                @if (($unreadCount ?? 0) > 0)
                    <span class="badge bg-danger">{{ $unreadCount }} unread</span>
                @endif
            </div>
            <div class="d-flex gap-2">
                @if (($unreadCount ?? 0) > 0)
                    <form method="POST" action="{{ route('feedback.read-all') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary">
                            Mark all read
                        </button>
                    </form>
                @endif
                <a href="{{ url()->previous() }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if ($feedbacks->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>No feedbacks submitted yet.</p>
            </div>
        @else
            <div class="card shadow-sm rounded-4 border-0">
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:48px">#</th>
                                    <th>Category</th>
                                    <th>Source</th>
                                    <th>Submitter</th>
                                    <th>Message</th>
                                    <th class="text-center" style="width:170px">Submitted At</th>
                                    <th class="text-center" style="width:120px">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($feedbacks as $index => $feedback)
                                    <tr class="{{ $feedback->isUnread() ? 'table-warning' : '' }}">
                                        <td>{{ $feedbacks->firstItem() + $index }}</td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                {{ $feedback->category ?: 'General' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $feedback->source === 'mobile' ? 'bg-primary' : 'bg-info text-dark' }}">
                                                {{ ucfirst($feedback->source ?: 'web') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div>{{ $feedback->name ?: 'Anonymous' }}</div>
                                            @if ($feedback->student?->id_number)
                                                <small class="text-muted">ID: {{ $feedback->student->id_number }}</small>
                                            @elseif ($feedback->email)
                                                <small class="text-muted">{{ $feedback->email }}</small>
                                            @endif
                                        </td>
                                        <td style="max-width:420px; white-space:pre-line;">{{ $feedback->comments }}</td>
                                        <td class="text-center">{{ $feedback->created_at->format('M d, Y h:i A') }}</td>
                                        <td class="text-center">
                                            @if ($feedback->isUnread())
                                                <form method="POST" action="{{ route('feedback.read', $feedback) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                                        Mark read
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted small">Read</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 d-flex justify-content-center">
                        {{ $feedbacks->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        @endif
    </div>

@endsection
