@extends('layouts.sidebar')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/attendance_logs/index.css') }}">
    <link rel="stylesheet" href="{{ asset('css/files/index.css') }}">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
@endsection

@section('content')

<div class="container">
    <h2 class="text-center mb-4" style="background-color: #22333b; color: white; padding: 20px; border-radius: 10px;">
        <i class="bi bi-folder2-open text-primary me-2"></i>Repository — files by folder
    </h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="upload-card">
        <h5 class="mb-3"><i class="bi bi-cloud-upload me-1"></i>Upload</h5>
        <form action="{{ route('files.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label"><i class="bi bi-folder2 me-1"></i>Folder</label>
                    <select name="folder_preset" id="folderPreset" class="form-select @error('folder_preset') is-invalid @enderror" required>
                        @foreach($presetLabels as $value => $label)
                            <option value="{{ $value }}" @selected(old('folder_preset', 'general') === $value)>{{ $label }}</option>
                        @endforeach
                        <option value="custom" @selected(old('folder_preset') === 'custom')>Custom…</option>
                    </select>
                    @error('folder_preset')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div id="folderCustomWrap" class="mt-2" @if(old('folder_preset') !== 'custom') style="display:none" @endif>
                        <label class="form-label small text-muted mb-1">Custom folder name</label>
                        <input type="text" name="folder_custom" value="{{ old('folder_custom') }}"
                            class="form-control @error('folder_custom') is-invalid @enderror"
                            placeholder="e.g. Budget 2025, Accreditation" maxlength="80"
                            @if(old('folder_preset') === 'custom') required @endif>
                        @error('folder_custom')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><i class="bi bi-file-earmark me-1"></i>File</label>
                    <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" required>
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="d-flex justify-content-between flex-wrap gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-cloud-upload-fill me-1"></i>Upload
                </button>
                <a href="{{ url('books') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>
        </form>
    </div>

    <div class="repo-layout">
        <aside class="repo-sidebar">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold" style="background:#22333b;color:#fff;">
                    <i class="bi bi-folder-symlink me-1"></i>Folders
                </div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('files.index') }}"
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $currentFolder === null || $currentFolder === '' ? 'active' : '' }}">
                        All folders
                        <span class="badge bg-secondary rounded-pill">{{ $files->count() }}</span>
                    </a>
                    @forelse($folderCounts as $folderKey => $count)
                        <a href="{{ route('files.index', ['folder' => $folderKey]) }}"
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ (string) $currentFolder === (string) $folderKey ? 'active' : '' }}">
                            <span class="text-truncate me-2" title="{{ $folderKey }}">
                                {{ $filesByFolder[$folderKey]->first()->folderLabel() }}
                            </span>
                            <span class="badge bg-primary rounded-pill">{{ $count }}</span>
                        </a>
                    @empty
                        <div class="list-group-item text-muted small">No files yet.</div>
                    @endforelse
                </div>
            </div>
        </aside>

        <div class="repo-main">
            @if($filteredFiles !== null)
                <h5 class="mb-3 text-secondary">
                    @if($filteredFiles->isEmpty())
                        No files in this folder.
                    @else
                        {{ $filteredFiles->first()->folderLabel() }}
                        <span class="badge bg-light text-dark border">{{ $filteredFiles->count() }} file(s)</span>
                    @endif
                </h5>
                @foreach($filteredFiles as $file)
                    <div class="card file-card mb-3">
                        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div class="min-w-0">
                                <h6 class="mb-1 text-truncate">
                                    <i class="bi bi-file-earmark-text me-1 text-secondary"></i>{{ $file->filename }}
                                </h6>
                                <small class="text-muted">Stored as: {{ basename($file->publicDiskPath()) }}</small>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('files.view', $file->id) }}" target="_blank" class="btn btn-outline-info btn-sm">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('files.download', $file->id) }}" class="btn btn-outline-success btn-sm" download>
                                    <i class="bi bi-download"></i>
                                </a>
                                <form action="{{ route('files.delete', $file->id) }}" method="POST" onsubmit="return confirm('Delete this file?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <p class="text-muted mb-3">Browse by folder using the list on the left, or expand sections below.</p>
                @forelse($filesByFolder as $folderKey => $folderFiles)
                    <div class="repo-folder-block">
                        <p class="repo-folder-heading mb-0 d-flex justify-content-between align-items-center">
                            <span>{{ $folderFiles->first()->folderLabel() }}</span>
                            <a href="{{ route('files.index', ['folder' => $folderKey]) }}" class="btn btn-sm btn-light">Open folder</a>
                        </p>
                        <ul class="list-group list-group-flush">
                            @foreach($folderFiles as $file)
                                <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <span class="text-truncate me-2"><i class="bi bi-file-earmark me-1 text-secondary"></i>{{ $file->filename }}</span>
                                    <div class="d-flex flex-shrink-0 gap-1">
                                        <a href="{{ route('files.view', $file->id) }}" target="_blank" class="btn btn-outline-info btn-sm py-0"><i class="bi bi-eye"></i></a>
                                        <a href="{{ route('files.download', $file->id) }}" class="btn btn-outline-success btn-sm py-0"><i class="bi bi-download"></i></a>
                                        <form action="{{ route('files.delete', $file->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this file?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm py-0"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <div class="alert alert-light border">No files uploaded yet.</div>
                @endforelse
            @endif
        </div>
    </div>
</div>

<script>
(function () {
    var sel = document.getElementById('folderPreset');
    var wrap = document.getElementById('folderCustomWrap');
    var customInput = wrap ? wrap.querySelector('input[name="folder_custom"]') : null;
    if (!sel || !wrap) return;
    function sync() {
        var isCustom = sel.value === 'custom';
        wrap.style.display = isCustom ? 'block' : 'none';
        if (customInput) {
            customInput.required = isCustom;
            customInput.disabled = !isCustom;
            if (!isCustom) {
                customInput.value = '';
            }
        }
    }
    sel.addEventListener('change', sync);
    sync();
})();
</script>

@endsection
