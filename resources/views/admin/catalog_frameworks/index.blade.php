@extends('layouts.sidebar')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">MARC catalog frameworks</h1>
        <a href="{{ route('book.index') }}" class="btn btn-outline-secondary btn-sm">Back to books</a>
    </div>

    <p class="text-muted small">
        Frameworks control which tags appear on Add/Edit book, their order, visibility, and optional mapping to a <code>books</code> column.
        <a href="{{ route('admin.catalog_select_options.index') }}">Catalog dropdown options</a> (content, media, carrier, library, and sublocation).
    </p>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="list-group">
        @forelse ($frameworks as $fw)
            <a href="{{ route('admin.catalog_frameworks.edit', $fw) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <span><strong>{{ $fw->name }}</strong></span>
                <span class="badge bg-secondary rounded-pill">Edit</span>
            </a>
        @empty
            <div class="list-group-item text-muted">No frameworks found. Run <code>php artisan db:seed --class=MarcFrameworkSeeder</code>.</div>
        @endforelse
    </div>
@endsection
