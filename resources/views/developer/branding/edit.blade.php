@extends('layouts.sidebar')

@section('title', 'Branding Settings')

@section('content')
<div class="container-fluid py-3" id="branding-settings">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-uppercase small fw-bold text-muted mb-1">Developer workspace</p>
            <h1 class="h3 mb-1">Branding Settings</h1>
            <p class="text-muted mb-0">Customize the banner, sidebar logo, and Pantas Default color palette.</p>
        </div>
        <span class="badge {{ $branding['is_customized'] ? 'text-bg-primary' : 'text-bg-secondary' }} px-3 py-2">
            {{ $branding['is_customized'] ? 'Customized' : 'Original Pantas' }}
        </span>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('developer.branding.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-xl-7">
                <section class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h5">Banner</h2>
                        <div class="row g-3 align-items-start">
                            <div class="col-md-8">
                                <img id="bannerPreview" src="{{ $bannerUrl }}" alt="Current banner" class="img-fluid rounded border w-100" style="height:190px;object-fit:cover">
                            </div>
                            <div class="col-md-4">
                                <img src="{{ $originalBannerUrl }}" alt="Original Pantas banner" class="img-fluid rounded border mb-2" style="height:70px;width:100%;object-fit:cover">
                                <p class="small fw-semibold mb-2">Original Pantas preview</p>
                                <label for="banner" class="form-label">Upload banner</label>
                                <input class="form-control" type="file" id="banner" name="banner" accept="image/png,image/jpeg,image/webp" data-preview="bannerPreview">
                                <p class="small text-muted mt-2">Original: {{ $defaults['banner_path'] }}</p>
                                <button class="btn btn-sm btn-outline-secondary" form="restore-banner" type="submit">Restore banner</button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h5">OPAC Banner</h2>
                        <p class="small text-muted">This banner appears on the public OPAC (library catalog) page.</p>
                        <div class="row g-3 align-items-start">
                            <div class="col-md-8">
                                <img id="opacBannerPreview" src="{{ $opacBannerUrl }}" alt="Current OPAC banner" class="img-fluid rounded border w-100" style="height:190px;object-fit:cover">
                            </div>
                            <div class="col-md-4">
                                <img src="{{ $originalOpacBannerUrl }}" alt="Original OPAC banner" class="img-fluid rounded border mb-2" style="height:70px;width:100%;object-fit:cover">
                                <p class="small fw-semibold mb-2">Original Pantas preview</p>
                                <label for="opac_banner" class="form-label">Upload OPAC banner</label>
                                <input class="form-control" type="file" id="opac_banner" name="opac_banner" accept="image/png,image/jpeg,image/webp" data-preview="opacBannerPreview">
                                <p class="small text-muted mt-2">Original: {{ $defaults['opac_banner_path'] }}</p>
                                <button class="btn btn-sm btn-outline-secondary" form="restore-opac-banner" type="submit">Restore OPAC banner</button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h5">OPAC Library Logo</h2>
                        <p class="small text-muted">This logo appears on the public OPAC search header.</p>
                        <div class="row g-3 align-items-start">
                            <div class="col-md-8">
                                <img id="opacLogoPreview" src="{{ $opacLogoUrl }}" alt="Current OPAC logo" class="img-fluid rounded border w-100" style="height:100px;object-fit:contain">
                            </div>
                            <div class="col-md-4">
                                <img src="{{ $originalOpacLogoUrl }}" alt="Original OPAC logo" class="img-fluid rounded border mb-2" style="height:60px;width:100%;object-fit:contain">
                                <p class="small fw-semibold mb-2">Original Pantas preview</p>
                                <label for="opac_logo" class="form-label">Upload OPAC logo</label>
                                <input class="form-control" type="file" id="opac_logo" name="opac_logo" accept="image/png,image/jpeg,image/webp" data-preview="opacLogoPreview">
                                <p class="small text-muted mt-2">Original: {{ $defaults['opac_logo_path'] }}</p>
                                <button class="btn btn-sm btn-outline-secondary" form="restore-opac-logo" type="submit">Restore OPAC logo</button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h5">OPAC Default Book Cover</h2>
                        <p class="small text-muted">This image appears when a book has no cover image in the OPAC.</p>
                        <div class="row g-3 align-items-start">
                            <div class="col-md-8">
                                <img id="opacDefaultBookCoverPreview" src="{{ $opacDefaultBookCoverUrl }}" alt="Current default book cover" class="img-fluid rounded border w-100" style="height:220px;object-fit:contain">
                            </div>
                            <div class="col-md-4">
                                <img src="{{ $originalOpacDefaultBookCoverUrl }}" alt="Original default book cover" class="img-fluid rounded border mb-2" style="height:80px;width:100%;object-fit:contain">
                                <p class="small fw-semibold mb-2">Original Pantas preview</p>
                                <label for="opac_default_book_cover" class="form-label">Upload default book cover</label>
                                <input class="form-control" type="file" id="opac_default_book_cover" name="opac_default_book_cover" accept="image/png,image/jpeg,image/webp" data-preview="opacDefaultBookCoverPreview">
                                <p class="small text-muted mt-2">Original: {{ $defaults['opac_default_book_cover_path'] }}</p>
                                <button class="btn btn-sm btn-outline-secondary" form="restore-opac-default-book-cover" type="submit">Restore default book cover</button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h5">Sidebar logo</h2>
                        <div class="d-flex flex-wrap gap-4 align-items-center">
                            <img id="logoPreview" src="{{ $logoUrl }}" alt="Current sidebar logo" class="rounded border p-2" style="width:130px;height:130px;object-fit:contain">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <img src="{{ $originalLogoUrl }}" alt="Original Pantas sidebar logo" class="rounded border p-1" style="width:54px;height:54px;object-fit:contain">
                                    <span class="small fw-semibold">Original Pantas preview</span>
                                </div>
                                <label for="sidebar_logo" class="form-label">Upload logo</label>
                                <input class="form-control" type="file" id="sidebar_logo" name="sidebar_logo" accept="image/png,image/jpeg,image/webp" data-preview="logoPreview">
                                <p class="small text-muted mt-2">Original: {{ $defaults['sidebar_logo_path'] }}</p>
                                <button class="btn btn-sm btn-outline-secondary" form="restore-logo" type="submit">Restore logo</button>
                            </div>
                        </div>
                        <hr>
                        <h3 class="h6">Sidebar brand text</h3>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="sidebar_brand_name" class="form-label">Brand name</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="sidebar_brand_name" name="sidebar_brand_name" value="{{ old('sidebar_brand_name', $branding['sidebar_brand_name']) }}" maxlength="60">
                                    <button class="btn btn-outline-secondary" form="restore-brand-name" type="submit">Reset</button>
                                </div>
                                <small class="text-muted">Default: {{ $defaults['sidebar_brand_name'] }}</small>
                            </div>
                            <div class="col-md-6">
                                <label for="sidebar_brand_subtitle" class="form-label">Brand subtitle</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="sidebar_brand_subtitle" name="sidebar_brand_subtitle" value="{{ old('sidebar_brand_subtitle', $branding['sidebar_brand_subtitle']) }}" maxlength="100">
                                    <button class="btn btn-outline-secondary" form="restore-brand-subtitle" type="submit">Reset</button>
                                </div>
                                <small class="text-muted">Default: {{ $defaults['sidebar_brand_subtitle'] }}</small>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="col-xl-5">
                <section class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h5">Pantas Default colors</h2>
                        <p class="small text-muted">These values do not change the other selectable themes.</p>
                        <div id="palettePreview" class="rounded border overflow-hidden mb-4" style="--preview-sidebar:{{ $branding['sidebar_background_color'] }};--preview-text:{{ $branding['sidebar_text_color'] }};--preview-brand-text:{{ $branding['sidebar_brand_text_color'] }};--preview-active:{{ $branding['sidebar_active_color'] }};--preview-sidebar-hover:{{ $branding['sidebar_hover_background_color'] }};--preview-sidebar-hover-text:{{ $branding['sidebar_hover_text_color'] }};--preview-primary:{{ $branding['primary_color'] }};--preview-button:{{ $branding['button_color'] }};--preview-sidebar-footer:{{ $branding['sidebar_footer_background_color'] }};--preview-table-header:{{ $branding['table_header_color'] }};--preview-table-header-text:{{ $branding['table_header_text_color'] }};--preview-table-border:{{ $branding['table_border_color'] }};--preview-table-hover:{{ $branding['table_hover_color'] }}">
                            <div class="d-flex" style="min-height:150px;background:#f8fafc">
                                <div class="p-3" style="width:42%;background:var(--preview-sidebar);color:var(--preview-text)">
                                    <strong id="brandNamePreview" style="color:var(--preview-brand-text)">{{ $branding['sidebar_brand_name'] }}</strong>
                                    <div id="brandSubtitlePreview" class="small" style="color:var(--preview-brand-text)">{{ $branding['sidebar_brand_subtitle'] }}</div>
                                    <div class="rounded px-2 py-1 mt-3" style="background:var(--preview-active);color:#fff">Active page</div>
                                    <div class="px-2 py-1 mt-1">Navigation</div>
                                    <div class="rounded px-2 py-1 mt-1" style="background:var(--preview-sidebar-hover);color:var(--preview-sidebar-hover-text)">Hover effect</div>
                                    <div class="rounded p-2 mt-4" style="background:var(--preview-sidebar-footer)"><div class="rounded px-2 py-1 text-center" style="border:1px solid #B91C1C;background:#FEF2F2;color:#B91C1C">Logout</div></div>
                                </div>
                                <div class="p-3 flex-grow-1">
                                    <strong style="color:var(--preview-primary)">Live palette</strong>
                                    <p class="small text-muted mt-2">Pantas Default preview</p>
                                    <button type="button" class="btn btn-sm text-white" style="background:var(--preview-button)">Button</button>
                                    <table class="w-100 mt-3 small" style="border-collapse:collapse">
                                        <thead><tr><th class="p-1" style="background:var(--preview-table-header);color:var(--preview-table-header-text);border:1px solid var(--preview-table-border)">Table header</th></tr></thead>
                                        <tbody><tr><td class="p-1" style="background:var(--preview-table-hover);border:1px solid var(--preview-table-border)">Row hover</td></tr></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @php
                            $labels = [
                                'primary_color' => 'Primary', 'secondary_color' => 'Secondary', 'accent_color' => 'Accent',
                                'sidebar_background_color' => 'Sidebar background', 'sidebar_text_color' => 'Sidebar text',
                                'sidebar_brand_text_color' => 'Sidebar brand text',
                                'sidebar_active_color' => 'Active navigation', 'button_color' => 'Primary button',
                                'sidebar_hover_background_color' => 'Sidebar hover background',
                                'sidebar_hover_text_color' => 'Sidebar hover text',
                                'sidebar_footer_background_color' => 'Sidebar logout area background',
                                'table_header_color' => 'Table header', 'table_header_text_color' => 'Table header text',
                                'table_border_color' => 'Table border', 'table_hover_color' => 'Table row hover',
                            ];
                        @endphp
                        @foreach ($labels as $field => $label)
                            <div class="mb-3">
                                <label for="{{ $field }}" class="form-label d-flex justify-content-between">
                                    <span>{{ $label }}</span><small class="text-muted">Default {{ $defaults[$field] }}</small>
                                </label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color branding-color-picker" value="{{ old($field, $branding[$field]) }}" data-target="{{ $field }}_text" aria-label="Choose {{ strtolower($label) }}">
                                    <input type="text" class="form-control branding-color-text" id="{{ $field }}_text" name="{{ $field }}" value="{{ old($field, $branding[$field]) }}" pattern="#[0-9A-Fa-f]{6}" required>
                                    <button class="btn btn-outline-secondary" form="restore-{{ $field }}" type="submit" title="Restore {{ strtolower($label) }}">Reset</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-4">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('dashboard.developer') }}" class="btn btn-outline-secondary">Cancel</a>
            <a href="{{ route('developer.branding.versions') }}" class="btn btn-outline-info">Version History</a>
            <button type="submit" form="restore-all" class="btn btn-outline-danger ms-auto">Restore to Default</button>
        </div>
    </form>

    <form id="restore-banner" method="POST" action="{{ route('developer.branding.restore') }}" class="d-none">@csrf<input type="hidden" name="field" value="banner_path"></form>
    <form id="restore-opac-banner" method="POST" action="{{ route('developer.branding.restore') }}" class="d-none">@csrf<input type="hidden" name="field" value="opac_banner_path"></form>
    <form id="restore-logo" method="POST" action="{{ route('developer.branding.restore') }}" class="d-none">@csrf<input type="hidden" name="field" value="sidebar_logo_path"></form>
    <form id="restore-opac-logo" method="POST" action="{{ route('developer.branding.restore') }}" class="d-none">@csrf<input type="hidden" name="field" value="opac_logo_path"></form>
    <form id="restore-opac-default-book-cover" method="POST" action="{{ route('developer.branding.restore') }}" class="d-none">@csrf<input type="hidden" name="field" value="opac_default_book_cover_path"></form>
    <form id="restore-brand-name" method="POST" action="{{ route('developer.branding.restore') }}" class="d-none">@csrf<input type="hidden" name="field" value="sidebar_brand_name"></form>
    <form id="restore-brand-subtitle" method="POST" action="{{ route('developer.branding.restore') }}" class="d-none">@csrf<input type="hidden" name="field" value="sidebar_brand_subtitle"></form>
    @foreach (array_keys($labels) as $field)
        <form id="restore-{{ $field }}" method="POST" action="{{ route('developer.branding.restore') }}" class="d-none">@csrf<input type="hidden" name="field" value="{{ $field }}"></form>
    @endforeach
    <form id="restore-all" method="POST" action="{{ route('developer.branding.restore') }}" class="d-none" onsubmit="return confirm('Restore the original Pantas banner, logo, and colors?')">@csrf</form>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-preview]').forEach(function (input) {
    input.addEventListener('change', function () {
        const file = this.files && this.files[0];
        const preview = document.getElementById(this.dataset.preview);
        if (file && preview) preview.src = URL.createObjectURL(file);
    });
});
document.querySelectorAll('.branding-color-picker').forEach(function (picker) {
    picker.addEventListener('input', function () {
        document.getElementById(this.dataset.target).value = this.value.toUpperCase();
        refreshPalettePreview();
    });
});
document.querySelectorAll('.branding-color-text').forEach(function (input) {
    input.addEventListener('input', function () {
        const picker = document.querySelector('[data-target="' + this.id + '"]');
        if (/^#[0-9A-Fa-f]{6}$/.test(this.value) && picker) picker.value = this.value;
        refreshPalettePreview();
    });
});
function refreshPalettePreview() {
    const preview = document.getElementById('palettePreview');
    if (!preview) return;
    const fields = {
        sidebar_background_color: '--preview-sidebar', sidebar_text_color: '--preview-text',
        sidebar_brand_text_color: '--preview-brand-text',
        sidebar_active_color: '--preview-active', primary_color: '--preview-primary', button_color: '--preview-button',
        sidebar_hover_background_color: '--preview-sidebar-hover', sidebar_hover_text_color: '--preview-sidebar-hover-text',
        sidebar_footer_background_color: '--preview-sidebar-footer',
        table_header_color: '--preview-table-header', table_header_text_color: '--preview-table-header-text',
        table_border_color: '--preview-table-border', table_hover_color: '--preview-table-hover'
    };
    Object.keys(fields).forEach(function (field) {
        const input = document.getElementById(field + '_text');
        if (input && /^#[0-9A-Fa-f]{6}$/.test(input.value)) preview.style.setProperty(fields[field], input.value);
    });
}
document.getElementById('sidebar_brand_name')?.addEventListener('input', function () {
    document.getElementById('brandNamePreview').textContent = this.value || '{{ $defaults['sidebar_brand_name'] }}';
});
document.getElementById('sidebar_brand_subtitle')?.addEventListener('input', function () {
    document.getElementById('brandSubtitlePreview').textContent = this.value || '{{ $defaults['sidebar_brand_subtitle'] }}';
});
</script>
@endpush
