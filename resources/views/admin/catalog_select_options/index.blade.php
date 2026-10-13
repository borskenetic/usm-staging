@extends('layouts.sidebar')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Catalog dropdown options</h1>
            <p class="text-muted small mb-0">
                Manage lists for RDA fields 336–338 and location fields 852 ‡b (library) and 852 ‡c (sublocation). Changes apply on Add/Edit book.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.catalog_frameworks.index') }}" class="btn btn-outline-secondary btn-sm">MARC frameworks</a>
            <a href="{{ route('book.index') }}" class="btn btn-outline-secondary btn-sm">Books</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (count($sections) === 0)
        <div class="alert alert-warning">
            No extensible fields found. Run <code>php artisan migrate</code> and
            <code>php artisan db:seed --class=MarcFrameworkSeeder</code>.
        </div>
    @endif

    <div class="row g-4">
        @foreach ($sections as $section)
            @php
                $def = $section['def'];
                $marc = $section['marc'];
                $anchor = $def['tag'] . ($def['subfield'] ?? '');
                $sub = $def['subfield'] ?? null;
                $display = $marc->tag . ($marc->subfield ? ' ‡' . $marc->subfield : '');
            @endphp
            <div class="col-lg-4" id="field-{{ $anchor }}">
                <div class="card h-100 shadow-sm {{ request('field') === $anchor ? 'border-primary' : '' }}">
                    <div class="card-header">
                        <div class="fw-semibold">{{ $def['title'] ?? $marc->label }}</div>
                        <div class="small text-muted">{{ $display }} · maps to <code>books.{{ $def['book_column'] ?? '—' }}</code></div>
                    </div>
                    <div class="card-body">
                        <h2 class="h6">Saved options</h2>
                        @if (count($section['saved']) === 0)
                            <p class="text-muted small">No saved options yet.</p>
                        @else
                            <ul class="list-group list-group-flush mb-3">
                                @foreach ($section['saved'] as $opt)
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span>{{ $opt }}</span>
                                        <form method="POST" action="{{ route('admin.catalog_select_options.destroy') }}"
                                              onsubmit="return confirm('Remove this option from the dropdown list?');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="tag" value="{{ $def['tag'] }}">
                                            <input type="hidden" name="subfield" value="{{ $sub }}">
                                            <input type="hidden" name="option" value="{{ $opt }}">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if (count($section['from_records']) > 0)
                            <h2 class="h6">On existing books only</h2>
                            <p class="small text-muted">Shown in cataloging dropdowns but not in the saved list above.</p>
                            <ul class="list-group list-group-flush mb-3">
                                @foreach ($section['from_records'] as $opt)
                                    <li class="list-group-item px-0 text-muted">{{ $opt }}</li>
                                @endforeach
                            </ul>
                        @endif

                        <h2 class="h6">Add option</h2>
                        <form method="POST" action="{{ route('admin.catalog_select_options.store') }}" class="row g-2 align-items-end">
                            @csrf
                            <input type="hidden" name="tag" value="{{ $def['tag'] }}">
                            <input type="hidden" name="subfield" value="{{ $sub }}">
                            <div class="col">
                                <label class="form-label visually-hidden" for="opt-{{ $anchor }}">New option</label>
                                <input type="text" class="form-control form-control-sm" id="opt-{{ $anchor }}"
                                       name="option" maxlength="255" required placeholder="New {{ strtolower($def['title'] ?? 'option') }}">
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary btn-sm">Add</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
