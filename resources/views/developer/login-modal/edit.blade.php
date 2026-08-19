@extends('layouts.sidebar')

@section('title', 'Login Modal Settings')

@section('content')
<div class="container-fluid py-3" id="login-modal-settings">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-uppercase small fw-bold text-muted mb-1">Developer workspace</p>
            <h1 class="h3 mb-1">Login Modal Settings</h1>
            <p class="text-muted mb-0">Customize the public sign-in experience independently from general application branding.</p>
        </div>
        <span class="badge {{ $isCustomized ? 'text-bg-primary' : 'text-bg-secondary' }} px-3 py-2">
            {{ $isCustomized ? 'Customized' : 'Original Pantas' }}
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

    @php
        $textFields = [
            'login_modal_welcome_label' => ['Welcome label', 80],
            'login_modal_portal_name' => ['Portal name', 100],
            'login_modal_description' => ['Description', 255],
            'login_modal_sign_in_heading' => ['Sign-in heading', 120],
            'login_modal_email_placeholder' => ['Email placeholder', 120],
            'login_modal_password_placeholder' => ['Password placeholder', 120],
        ];
        $colorFields = [
            'login_modal_left_background_color' => 'Left panel background',
            'login_modal_welcome_portal_color' => 'Welcome and portal text',
            'login_modal_description_color' => 'Description text',
            'login_modal_background_color' => 'Modal background',
            'login_modal_form_background_color' => 'Login form background',
            'login_modal_form_border_color' => 'Login form border',
            'login_modal_text_color' => 'Text color',
            'login_modal_button_color' => 'Sign-in button',
        ];
    @endphp

    <form method="POST" action="{{ route('developer.login-modal.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-xl-6">
                <section class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h5">Modal logo</h2>
                        <p class="small text-muted">PNG, JPG, or WebP; 64–1000 px per side; maximum 2 MB.</p>
                        <div class="d-flex flex-wrap align-items-center gap-4">
                            <img id="loginModalLogoPreview" src="{{ $logoUrl }}" alt="Current login modal logo" class="rounded border p-2 bg-white" style="width:120px;height:120px;object-fit:contain">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <img src="{{ $originalLogoUrl }}" alt="Original login modal logo" class="rounded border p-1 bg-white" style="width:54px;height:54px;object-fit:contain">
                                    <span class="small fw-semibold">Original Pantas preview</span>
                                </div>
                                <label for="login_modal_logo" class="form-label">Upload logo</label>
                                <input class="form-control" type="file" id="login_modal_logo" name="login_modal_logo" accept="image/png,image/jpeg,image/webp">
                                <small class="text-muted d-block mt-2">Default: {{ $defaults['login_modal_logo_path'] }}</small>
                                <button class="btn btn-sm btn-outline-secondary mt-2" form="restore-login-modal-logo" type="submit">Restore logo</button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h5">Login text</h2>
                        <div class="row g-3">
                            @foreach ($textFields as $field => [$label, $limit])
                                <div class="{{ $field === 'login_modal_description' ? 'col-12' : 'col-md-6' }}">
                                    <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                                    <div class="input-group">
                                        <input
                                            type="text"
                                            class="form-control login-modal-text-input"
                                            id="{{ $field }}"
                                            name="{{ $field }}"
                                            value="{{ old($field, $branding[$field]) }}"
                                            maxlength="{{ $limit }}"
                                            data-preview-field="{{ $field }}"
                                        >
                                        <button class="btn btn-outline-secondary" form="restore-{{ $field }}" type="submit">Reset</button>
                                    </div>
                                    <small class="text-muted">Default: {{ $defaults[$field] }}</small>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h5">Modal colors</h2>
                        <p class="small text-muted">Use complete six-digit hexadecimal colors.</p>
                        @foreach ($colorFields as $field => $label)
                            <div class="mb-3">
                                <label for="{{ $field }}_text" class="form-label d-flex justify-content-between gap-2">
                                    <span>{{ $label }}</span>
                                    <small class="text-muted">Default {{ $defaults[$field] }}</small>
                                </label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color login-modal-color-picker" value="{{ old($field, $branding[$field]) }}" data-target="{{ $field }}_text" aria-label="Choose {{ strtolower($label) }}">
                                    <input type="text" class="form-control login-modal-color-text" id="{{ $field }}_text" name="{{ $field }}" value="{{ old($field, $branding[$field]) }}" pattern="#[0-9A-Fa-f]{6}" required data-preview-field="{{ $field }}">
                                    <button class="btn btn-outline-secondary" form="restore-{{ $field }}" type="submit">Reset</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="col-xl-6">
                <section class="card shadow-sm sticky-xl-top" style="top:1rem">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <h2 class="h5 mb-1">Live preview</h2>
                                <p class="small text-muted mb-0">Registration panels are intentionally not affected.</p>
                            </div>
                            <div class="btn-group btn-group-sm" role="group" aria-label="Preview size">
                                <button type="button" class="btn btn-primary" data-preview-size="desktop">Desktop</button>
                                <button type="button" class="btn btn-outline-primary" data-preview-size="mobile">Mobile</button>
                            </div>
                        </div>

                        <div class="login-modal-preview-stage rounded border p-3">
                            <div id="loginModalPreview" class="login-modal-preview mx-auto" style="--lm-preview-left:{{ old('login_modal_left_background_color', $branding['login_modal_left_background_color']) }};--lm-preview-welcome-portal:{{ old('login_modal_welcome_portal_color', $branding['login_modal_welcome_portal_color']) }};--lm-preview-description:{{ old('login_modal_description_color', $branding['login_modal_description_color']) }};--lm-preview-bg:{{ old('login_modal_background_color', $branding['login_modal_background_color']) }};--lm-preview-form-bg:{{ old('login_modal_form_background_color', $branding['login_modal_form_background_color']) }};--lm-preview-form-border:{{ old('login_modal_form_border_color', $branding['login_modal_form_border_color']) }};--lm-preview-text:{{ old('login_modal_text_color', $branding['login_modal_text_color']) }};--lm-preview-button:{{ old('login_modal_button_color', $branding['login_modal_button_color']) }}">
                                <div class="login-modal-preview-left">
                                    <small id="previewWelcome">{{ old('login_modal_welcome_label', $branding['login_modal_welcome_label']) }}</small>
                                    <span class="login-modal-preview-logo"><img id="previewLogo" src="{{ $logoUrl }}" alt="Preview logo"></span>
                                    <strong id="previewPortalName">{{ old('login_modal_portal_name', $branding['login_modal_portal_name']) }}</strong>
                                    <p id="previewDescription">{{ old('login_modal_description', $branding['login_modal_description']) }}</p>
                                </div>
                                <div class="login-modal-preview-right">
                                    <h3 id="previewHeading">{{ old('login_modal_sign_in_heading', $branding['login_modal_sign_in_heading']) }}</h3>
                                    <div class="login-modal-preview-form">
                                    <label>Email <span>*</span></label>
                                    <div class="login-modal-preview-input" id="previewEmailPlaceholder">{{ old('login_modal_email_placeholder', $branding['login_modal_email_placeholder']) }}</div>
                                    <label>Password <span>*</span></label>
                                    <div class="login-modal-preview-input" id="previewPasswordPlaceholder">{{ old('login_modal_password_placeholder', $branding['login_modal_password_placeholder']) }}</div>
                                    <div class="login-modal-preview-remember">□ Remember me</div>
                                    <div class="login-modal-preview-button">Sign In</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-4">
            <button type="submit" class="btn btn-primary">Save Login Modal</button>
            <a href="{{ route('dashboard.developer') }}" class="btn btn-outline-secondary">Cancel</a>
            <a href="{{ route('developer.branding.versions') }}" class="btn btn-outline-info">Version History</a>
            <button type="submit" form="restore-login-modal-all" class="btn btn-outline-danger ms-auto">Restore Login Modal Defaults</button>
        </div>
    </form>

    <form id="restore-login-modal-logo" method="POST" action="{{ route('developer.login-modal.restore') }}" class="d-none">@csrf<input type="hidden" name="field" value="login_modal_logo_path"></form>
    @foreach (array_keys($textFields + $colorFields) as $field)
        <form id="restore-{{ $field }}" method="POST" action="{{ route('developer.login-modal.restore') }}" class="d-none">@csrf<input type="hidden" name="field" value="{{ $field }}"></form>
    @endforeach
    <form id="restore-login-modal-all" method="POST" action="{{ route('developer.login-modal.restore') }}" class="d-none" onsubmit="return confirm('Restore all login modal settings to the original Pantas defaults?')">@csrf</form>
</div>

@endsection

@push('styles')
<style>
.login-modal-preview-stage{background:#e5e7eb;min-height:430px;display:grid;place-items:center;overflow:auto}.login-modal-preview{display:grid;grid-template-columns:1fr 1.15fr;width:min(680px,100%);min-height:390px;overflow:hidden;border-radius:22px;background:var(--lm-preview-bg);color:var(--lm-preview-text);box-shadow:0 22px 60px rgba(15,23,42,.22);transition:width .2s ease}.login-modal-preview-left,.login-modal-preview-right{padding:34px;display:flex;flex-direction:column;justify-content:center}.login-modal-preview-left{align-items:center;text-align:center;background:var(--lm-preview-left);color:var(--lm-preview-welcome-portal)}.login-modal-preview-left small{text-transform:uppercase;letter-spacing:.12em;font-weight:700}.login-modal-preview-logo{display:grid;place-items:center;width:78px;height:78px;margin:18px;border-radius:22px;background:#fff}.login-modal-preview-logo img{width:62px;height:62px;object-fit:contain}.login-modal-preview-left strong{font-size:28px}.login-modal-preview-left p{font-size:13px;margin:14px 0 0;color:var(--lm-preview-description)}.login-modal-preview-right h3{font-size:23px;margin-bottom:22px}.login-modal-preview-form{padding:16px 24px;border:1px solid var(--lm-preview-form-border);border-radius:12px;background:var(--lm-preview-form-bg)}.login-modal-preview-right label{font-size:12px;font-weight:700;margin-bottom:6px}.login-modal-preview-right label span{color:#dc2626}.login-modal-preview-input{min-height:42px;border:1px solid #d7deea;border-radius:9px;padding:11px;margin-bottom:14px;color:#6b7280;background:#fff;font-size:13px}.login-modal-preview-remember{font-size:12px;margin-bottom:16px}.login-modal-preview-button{border-radius:9px;padding:11px;text-align:center;color:#fff;background:var(--lm-preview-button);font-weight:700}.login-modal-preview.is-mobile{grid-template-columns:1fr;width:min(340px,100%)}.login-modal-preview.is-mobile .login-modal-preview-left{padding:24px}.login-modal-preview.is-mobile .login-modal-preview-right{padding:26px}.login-modal-preview.is-mobile .login-modal-preview-left p{display:none}@media(max-width:767.98px){.login-modal-preview{grid-template-columns:1fr}.login-modal-preview-left{padding:24px}.login-modal-preview-right{padding:26px}}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const preview = document.getElementById('loginModalPreview');
    const defaults = @json(collect($defaults)->only(array_keys($textFields)));
    const previewTargets = {
        login_modal_welcome_label: 'previewWelcome',
        login_modal_portal_name: 'previewPortalName',
        login_modal_description: 'previewDescription',
        login_modal_sign_in_heading: 'previewHeading',
        login_modal_email_placeholder: 'previewEmailPlaceholder',
        login_modal_password_placeholder: 'previewPasswordPlaceholder'
    };
    const colorTargets = {
        login_modal_left_background_color: '--lm-preview-left',
        login_modal_welcome_portal_color: '--lm-preview-welcome-portal',
        login_modal_description_color: '--lm-preview-description',
        login_modal_background_color: '--lm-preview-bg',
        login_modal_form_background_color: '--lm-preview-form-bg',
        login_modal_form_border_color: '--lm-preview-form-border',
        login_modal_text_color: '--lm-preview-text',
        login_modal_button_color: '--lm-preview-button'
    };

    document.querySelectorAll('.login-modal-text-input').forEach(function (input) {
        input.addEventListener('input', function () {
            const target = document.getElementById(previewTargets[this.dataset.previewField]);
            if (target) target.textContent = this.value || defaults[this.dataset.previewField] || '';
        });
    });

    document.querySelectorAll('.login-modal-color-picker').forEach(function (picker) {
        picker.addEventListener('input', function () {
            const target = document.getElementById(this.dataset.target);
            if (target) {
                target.value = this.value.toUpperCase();
                target.dispatchEvent(new Event('input'));
            }
        });
    });

    document.querySelectorAll('.login-modal-color-text').forEach(function (input) {
        input.addEventListener('input', function () {
            if (!/^#[0-9A-Fa-f]{6}$/.test(this.value)) return;
            const picker = document.querySelector('[data-target="' + this.id + '"]');
            if (picker) picker.value = this.value;
            preview.style.setProperty(colorTargets[this.dataset.previewField], this.value);
        });
    });

    document.getElementById('login_modal_logo')?.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file) return;
        const url = URL.createObjectURL(file);
        document.getElementById('loginModalLogoPreview').src = url;
        document.getElementById('previewLogo').src = url;
    });

    document.querySelectorAll('[data-preview-size]').forEach(function (button) {
        button.addEventListener('click', function () {
            const mobile = this.dataset.previewSize === 'mobile';
            preview.classList.toggle('is-mobile', mobile);
            document.querySelectorAll('[data-preview-size]').forEach(function (option) {
                const active = option === button;
                option.classList.toggle('btn-primary', active);
                option.classList.toggle('btn-outline-primary', !active);
            });
        });
    });
});
</script>
@endpush
