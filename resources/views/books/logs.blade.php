@extends('layouts.sidebar')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/books/logs.css') }}">
<link rel="stylesheet" href="{{ asset('css/books/circulation-report.css') }}">
@endsection

@section('content')
@php
    $hasFilters = request()->filled('student_id')
        || request()->filled('filter_patron')
        || request()->filled('book_title')
        || request()->filled('start_date')
        || request()->filled('end_date')
        || request()->filled('circulation_type');
@endphp

<div class="circulation-page">

    <header class="circulation-page__hero">
        <div>
            <p class="circulation-page__eyebrow">Circulation</p>
            <h1 class="circulation-page__title">Book transactions</h1>
            <p class="circulation-page__lead">Check books in or out, then search and review the transaction history.</p>
        </div>
        <a href="{{ route('book.index') }}" class="btn btn-outline-secondary btn-sm">← Catalog</a>
    </header>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('circulation_mail'))
        <div class="alert alert-info">{{ session('circulation_mail') }}</div>
    @endif
    @error('student_id')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <section class="circulation-card">
        <div class="circulation-card__head">
            <h2 class="circulation-card__title">Record transaction</h2>
            <p class="circulation-card__hint text-muted mb-0">
                Scan or type the copy <strong>accession number</strong> (recommended), barcode, or RFID — then select patron and circulation type.
            </p>
        </div>
        <form action="{{ route('logs.store') }}" method="POST" id="logTransactionForm" class="circulation-card__body">
            @csrf
            <div class="row g-3">
                <div class="col-md-4 position-relative">
                    <label for="copy_identifier_input" class="form-label">Copy ID</label>
                    <input type="text" class="form-control circulation-input" name="copy_identifier" id="copy_identifier_input"
                           value="{{ $prefillCopyIdentifier ?? request('copy_identifier', request('rfid')) }}"
                           placeholder="Accession, barcode, or RFID" autocomplete="off" required>
                    <p class="form-text text-muted small mb-0">Each physical copy should have a unique accession no. (949). RFID is optional.</p>
                    <ul id="bookSuggestions" class="circulation-suggest list-group"></ul>
                </div>
                <div class="col-md-4 position-relative">
                    <label for="patron_name" class="form-label">Patron</label>
                    <input type="hidden" name="student_id" id="student_id" value="{{ request('student_id') }}">
                    <input type="text" id="patron_name" class="form-control circulation-input" autocomplete="off"
                           placeholder="Search name or ID…" value="{{ $prefillPatronLabel ?? '' }}">
                    <ul id="patronSuggestions" class="circulation-suggest list-group"></ul>
                </div>
                <div class="col-md-4">
                    <label for="status_select" class="form-label">Circulation</label>
                    <select name="status" id="status_select" class="form-select circulation-input" required>
                        <option value="checked_out" {{ request('status', 'checked_out') === 'checked_out' ? 'selected' : '' }}>Check out (outside library)</option>
                        <option value="room_use" {{ request('status') === 'room_use' ? 'selected' : '' }}>Room use (in library)</option>
                        <option value="checked_in" {{ request('status') === 'checked_in' ? 'selected' : '' }}>Check in</option>
                    </select>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary" id="record">Record transaction</button>
            </div>
        </form>
    </section>

    <section class="circulation-card">
        <div class="circulation-card__head circulation-card__head--split">
            <div>
                <h2 class="circulation-card__title">Transaction logs</h2>
                <p class="circulation-card__hint text-muted mb-0">Search by patron or book title; narrow by date and loan type. The report uses these filters.</p>
            </div>
            <button type="submit" form="logFilterForm" formaction="{{ route('transactions.export') }}" formmethod="get" class="circulation-download">
                <i class="bi bi-file-earmark-excel" aria-hidden="true"></i>
                Download Excel report
            </button>
        </div>
        <form method="GET" action="{{ route('logs.index') }}" id="logFilterForm" class="circulation-card__body">
            <div class="row g-3">
                <div class="col-md-6 col-lg-4 position-relative">
                    <label for="filter_patron" class="form-label">Patron</label>
                    <input type="hidden" name="student_id" id="filter_student_id" value="{{ request('student_id') }}">
                    <input type="text" name="filter_patron" id="filter_patron" class="form-control circulation-input"
                           value="{{ $prefillPatronLabel ?? '' }}" autocomplete="off"
                           placeholder="Search patron name or ID…">
                    <ul id="filterPatronSuggestions" class="circulation-suggest list-group"></ul>
                </div>
                <div class="col-md-6 col-lg-4 position-relative">
                    <label for="filter_book_title" class="form-label">Book title</label>
                    <input type="text" name="book_title" id="filter_book_title" class="form-control circulation-input"
                           value="{{ $filterBookTitle ?? '' }}" autocomplete="off"
                           placeholder="Search book title…">
                    <ul id="filterBookTitleSuggestions" class="circulation-suggest list-group"></ul>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label for="start_date" class="form-label">From</label>
                    <input type="date" class="form-control circulation-input" name="start_date" id="start_date"
                           value="{{ request('start_date') }}">
                </div>
                <div class="col-md-6 col-lg-2">
                    <label for="end_date" class="form-label">To</label>
                    <input type="date" class="form-control circulation-input" name="end_date" id="end_date"
                           value="{{ request('end_date') }}">
                </div>
                <div class="col-md-6 col-lg-4">
                    <label for="circulation_type" class="form-label">Loan type</label>
                    <select class="form-select circulation-input" name="circulation_type" id="circulation_type">
                        <option value="">All types</option>
                        <option value="checkout" {{ request('circulation_type') === 'checkout' ? 'selected' : '' }}>Check out</option>
                        <option value="room_use" {{ request('circulation_type') === 'room_use' ? 'selected' : '' }}>Room use</option>
                    </select>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mt-3">
                <p class="text-muted small mb-0">
                    @if($hasFilters)
                        Filters active — {{ $logs->total() }} {{ $logs->total() === 1 ? 'match' : 'matches' }}
                    @else
                        Showing latest transactions
                    @endif
                </p>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="apply">Apply filters</button>
                    <a href="{{ route('logs.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </div>
        </form>

        <div class="circulation-table-wrap">
            <table class="table table-hover circulation-table mb-0">
                <thead>
                    <tr>
                        <th>Book</th>
                        <th>Copy ID</th>
                        <th>Patron</th>
                        <th>Status</th>
                        <th>Loan type</th>
                        <th>When</th>
                        <th>Due</th>
                        <th>Renewals</th>
                        <th>Returned</th>
                        <th>Overdue</th>
                        <th>Days late</th>
                        <th>Fine</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    @php
                        $isCheckIn = $log->status === 'Checked In';
                        $daysLate = 0;
                        $isOverdue = false;
                        $tooltip = '';
                        $effectiveLate = 0;

                        if ($isCheckIn && $log->due_date && $log->returned_date) {
                            $daysLate = max(0, $log->due_date->diffInDays($log->returned_date, false));
                            $daysLateWhole = floor($daysLate);
                            $settings = \App\Models\FineSetting::forSection($log->book->section ?? null);
                            $gracePeriod = $settings->grace_period_days;
                            $finePerDay = $settings->fine_per_day;
                            $effectiveLate = max(0, $daysLateWhole - $gracePeriod);
                            $isOverdue = $effectiveLate > 0;
                            if ($isOverdue) {
                                $tooltip = "{$effectiveLate} day(s) × ₱".number_format($finePerDay, 2)." = ₱".number_format($log->fine_incurred, 2);
                            }
                        }
                    @endphp
                    <tr>
                        <td class="circulation-table__title">{{ $log->book->title_statement ?? 'N/A' }}</td>
                        <td class="small">
                            @if($log->book)
                                <code>{{ $log->book->copyIdentifierForCirculation() ?? '—' }}</code>
                                @if($log->book->copyIdentifierTypeLabel())
                                    <span class="text-muted d-block">{{ $log->book->copyIdentifierTypeLabel() }}</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $log->patronLabel() }}</td>
                        <td>
                            @if($log->status === 'Checked Out')
                                <span class="badge text-bg-warning">Out</span>
                            @else
                                <span class="badge text-bg-success">In</span>
                            @endif
                        </td>
                        <td>{{ $log->circulationLabel() }}</td>
                        <td class="text-nowrap small">{{ $log->timestamp_manila ?? '—' }}</td>
                        <td class="text-nowrap small">{{ $log->due_date ?? '—' }}</td>
                        <td class="small">
                            @if(($log->circulation_type ?? \App\Models\BookLog::CIRCULATION_CHECKOUT) === \App\Models\BookLog::CIRCULATION_CHECKOUT)
                                {{ (int) ($log->renew_count ?? 0) }}/{{ \App\Http\Controllers\BookController::MAX_RENEWALS_PER_LOAN }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-nowrap small">{{ $log->returned_date ?? '—' }}</td>
                        <td>
                            @if($isCheckIn)
                                @if($isOverdue)
                                    <span class="badge text-bg-danger">Overdue</span>
                                @else
                                    <span class="badge text-bg-success">On time</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $isCheckIn && $isOverdue ? $effectiveLate : '—' }}</td>
                        <td class="text-nowrap">
                            @if($isCheckIn && $isOverdue)
                                <span data-bs-toggle="tooltip" title="{{ $tooltip }}">₱{{ number_format($log->fine_incurred, 2) }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-nowrap">
                            @if($log->status === 'Checked Out'
                                && (($log->circulation_type ?? \App\Models\BookLog::CIRCULATION_CHECKOUT) === \App\Models\BookLog::CIRCULATION_CHECKOUT)
                                && $log->due_date
                                && (int) ($log->renew_count ?? 0) < \App\Http\Controllers\BookController::MAX_RENEWALS_PER_LOAN)
                                <form method="POST" action="{{ route('logs.renew', $log->book_id) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="student_id" value="{{ $log->student_id }}">
                                    <button type="submit" class="btn btn-sm btn-outline-primary"
                                        onclick="return confirm('Renew this loan and extend the due date?');">Renew</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="13" class="text-center text-muted py-5">No transactions match your filters.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3 border-top">
            {{ $logs->withQueryString()->links('pagination::bootstrap-5') }}
            <button type="submit" form="logFilterForm" formaction="{{ route('transactions.export') }}" formmethod="get" class="circulation-download">
                <i class="bi bi-file-earmark-excel" aria-hidden="true"></i>
                Download Excel report
            </button>
        </div>
    </section>
</div>

@if(session('overdue_modal'))
<div class="modal fade" id="overdueModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Overdue book notice</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Book:</strong> {{ session('overdue_modal.book_title') }}</p>
                <p><strong>Patron:</strong> {{ session('overdue_modal.patron_name') }}</p>
                <p><strong>Days late:</strong> {{ session('overdue_modal.days_late') }}</p>
                <p><strong>Fine:</strong> ₱{{ number_format(session('overdue_modal.fine'), 2) }}</p>
                <p class="text-muted mb-0"><em>{{ session('overdue_modal.breakdown') }}</em></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const patronSuggestUrl = @json(route('patron.suggestions'));
    const bookSuggestUrl = @json(route('book.suggestions'));
    @php
        $bookTitleLogSuggestUrl = \Illuminate\Support\Facades\Route::has('book.title.log.suggestions')
            ? route('book.title.log.suggestions')
            : url('/book-title-log-suggestions');
    @endphp
    const bookTitleLogSuggestUrl = @json($bookTitleLogSuggestUrl);

    function wireAutocomplete({ input, list, fetchUrl, onSelect, minChars = 1, mapItems }) {
        if (!input || !list) return;

        let debounce = null;

        function hide() {
            list.innerHTML = '';
            list.classList.remove('is-open');
        }

        function showItems(items) {
            list.innerHTML = '';
            if (!items.length) {
                hide();
                return;
            }
            items.forEach(function (item) {
                const li = document.createElement('li');
                li.className = 'list-group-item list-group-item-action';
                li.textContent = item.label;
                li.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    onSelect(item, input, list);
                    hide();
                });
                list.appendChild(li);
            });
            list.classList.add('is-open');
        }

        input.addEventListener('input', function () {
            const query = this.value.trim();
            clearTimeout(debounce);
            if (query.length < minChars) {
                hide();
                return;
            }
            debounce = setTimeout(function () {
                fetch(fetchUrl + '?query=' + encodeURIComponent(query), {
                    headers: { 'Accept': 'application/json' },
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        const items = mapItems(data);
                        showItems(items);
                    })
                    .catch(function () { hide(); });
            }, 200);
        });

        input.addEventListener('blur', function () {
            setTimeout(hide, 150);
        });

        document.addEventListener('click', function (e) {
            if (!list.contains(e.target) && e.target !== input) {
                hide();
            }
        });

        return { hide };
    }

    // Record form — patron
    const studentIdInput = document.getElementById('student_id');
    const logForm = document.getElementById('logTransactionForm');
    if (logForm) {
        logForm.addEventListener('submit', function (e) {
            if (!studentIdInput || !String(studentIdInput.value || '').trim()) {
                e.preventDefault();
                alert('Select a patron from the suggestions list.');
            }
        });
    }

    wireAutocomplete({
        input: document.getElementById('patron_name'),
        list: document.getElementById('patronSuggestions'),
        fetchUrl: patronSuggestUrl,
        onSelect: function (item) {
            document.getElementById('patron_name').value = item.raw.name;
            studentIdInput.value = item.raw.id;
        },
        mapItems: function (data) {
            return (data || []).map(function (p) {
                return { label: p.name, raw: p };
            });
        },
    });

    document.getElementById('patron_name')?.addEventListener('input', function () {
        if (studentIdInput) studentIdInput.value = '';
    });

    // Record form — copy ID (accession / barcode / RFID)
    wireAutocomplete({
        input: document.getElementById('copy_identifier_input'),
        list: document.getElementById('bookSuggestions'),
        fetchUrl: bookSuggestUrl,
        onSelect: function (item) {
            const copyInput = document.getElementById('copy_identifier_input');
            const statusSelect = document.getElementById('status_select');
            const patronInput = document.getElementById('patron_name');
            const b = item.raw;
            copyInput.value = b.copy_identifier || b.accession_no || b.barcode || b.rfid || '';
            if ((b.availability || '') === 'Borrowed') {
                if (statusSelect) statusSelect.value = 'checked_in';
                if (patronInput) patronInput.value = b.last_patron || '';
                if (studentIdInput) studentIdInput.value = b.last_student_id || '';
            } else {
                if (statusSelect) statusSelect.value = 'checked_out';
                if (studentIdInput) studentIdInput.value = '';
            }
        },
        mapItems: function (data) {
            return (data || []).map(function (b) {
                const idPart = b.copy_identifier_summary || 'No copy ID';
                const label = (b.title || 'Untitled') + ' — ' + (b.author || '') + ' · ' + idPart;
                return { label: label, raw: b };
            });
        },
    });

    // Filter — patron
    const filterStudentId = document.getElementById('filter_student_id');
    wireAutocomplete({
        input: document.getElementById('filter_patron'),
        list: document.getElementById('filterPatronSuggestions'),
        fetchUrl: patronSuggestUrl,
        onSelect: function (item) {
            document.getElementById('filter_patron').value = item.raw.name;
            if (filterStudentId) filterStudentId.value = item.raw.id;
        },
        mapItems: function (data) {
            return (data || []).map(function (p) {
                return { label: p.name, raw: p };
            });
        },
    });

    document.getElementById('filter_patron')?.addEventListener('input', function () {
        if (filterStudentId) filterStudentId.value = '';
    });

    // Filter — book title
    wireAutocomplete({
        input: document.getElementById('filter_book_title'),
        list: document.getElementById('filterBookTitleSuggestions'),
        fetchUrl: bookTitleLogSuggestUrl,
        onSelect: function (item) {
            document.getElementById('filter_book_title').value = item.raw.title;
        },
        mapItems: function (data) {
            return (data || []).map(function (row) {
                return { label: row.title, raw: row };
            });
        },
    });

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    const overdueModal = document.getElementById('overdueModal');
    if (overdueModal) {
        bootstrap.Modal.getOrCreateInstance(overdueModal).show();
    }
});
</script>
@endsection
