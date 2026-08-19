@extends('layouts.sidebar')

@section('title', 'Register Modal Settings')

@section('content')
<div class="container-fluid py-3" id="register-modal-settings">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-uppercase small fw-bold text-muted mb-1">Developer workspace</p>
            <h1 class="h3 mb-1">Register Modal Settings</h1>
            <p class="text-muted mb-0">Customize Attendance and Library registration independently from login mode branding.</p>
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
        $sharedTextFields = [
            'register_modal_heading' => ['Register heading', 80],
            'register_modal_login_label' => ['Login/back label', 80],
            'register_modal_attendance_tab' => ['Attendance tab label', 80],
            'register_modal_library_tab' => ['Library tab label', 80],
        ];
    @endphp

    <form method="POST" action="{{ route('developer.register-modal.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-xl-6">
                {{-- Shared Settings --}}
                <section class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h5">Shared registration labels</h2>
                        <p class="small text-muted">These appear in both Attendance and Library registration modes.</p>
                        <div class="row g-3">
                            @foreach ($sharedTextFields as $field => [$label, $limit])
                                <div class="col-md-6">
                                    <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control register-modal-text-input" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $branding[$field]) }}" maxlength="{{ $limit }}" data-preview-group="shared" data-preview-field="{{ $field }}">
                                        <button class="btn btn-outline-secondary" form="restore-{{ $field }}" type="submit">Reset</button>
                                    </div>
                                    <small class="text-muted">Default: {{ $defaults[$field] }}</small>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                {{-- Attendance Registration Settings --}}
                <section class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h5">Attendance registration</h2>
                        <p class="small text-muted">PNG, JPG, or WebP; 64–1000 px per side; maximum 2 MB.</p>

                        <div class="d-flex flex-wrap align-items-center gap-4 mb-3">
                            <img id="attendanceRegisterLogoPreview" src="{{ $attendanceLogoUrl }}" alt="Current Attendance register logo" class="rounded border p-2 bg-white" style="width:80px;height:80px;object-fit:contain">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <img src="{{ $originalAttendanceLogoUrl }}" alt="Original Attendance register logo" class="rounded border p-1 bg-white" style="width:48px;height:48px;object-fit:contain">
                                    <span class="small fw-semibold">Original Pantas preview</span>
                                </div>
                                <label for="register_modal_attendance_logo" class="form-label">Upload Attendance logo</label>
                                <input class="form-control" type="file" id="register_modal_attendance_logo" name="register_modal_attendance_logo" accept="image/png,image/jpeg,image/webp">
                                <small class="text-muted d-block mt-2">Default: {{ $defaults['register_modal_attendance_logo_path'] }}</small>
                                <button class="btn btn-sm btn-outline-secondary mt-2" form="restore-register_modal_attendance_logo_path" type="submit">Restore logo</button>
                            </div>
                        </div>

                        @php
                            $attendanceTextFields = [
                                'register_modal_attendance_welcome_label' => ['Welcome label', 80],
                                'register_modal_attendance_portal_name' => ['Portal name', 100],
                                'register_modal_attendance_description' => ['Description', 255],
                                'register_modal_attendance_heading' => ['Service heading', 120],
                            ];
                            $attendanceRoleFields = [
                                'register_modal_attendance_student_label' => ['Student label', 80],
                                'register_modal_attendance_employee_label' => ['Employee label', 80],
                                'register_modal_attendance_student_submit' => ['Student submit label', 120],
                                'register_modal_attendance_employee_submit' => ['Employee submit label', 120],
                            ];
                            $attendanceColorFields = [
                                'register_modal_attendance_panel_color' => 'Panel background',
                                'register_modal_attendance_welcome_portal_color' => 'Welcome and portal text',
                                'register_modal_attendance_description_color' => 'Description text',
                                'register_modal_attendance_text_color' => 'Modal text',
                                'register_modal_attendance_accent_color' => 'Accent',
                                'register_modal_attendance_active_role_color' => 'Active role',
                                'register_modal_attendance_submit_color' => 'Submit button',
                            ];
                        @endphp

                        <div class="row g-3 mb-3">
                            @foreach ($attendanceTextFields as $field => [$label, $limit])
                                <div class="col-md-6">
                                    <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control register-modal-text-input" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $branding[$field]) }}" maxlength="{{ $limit }}" data-preview-group="attendance" data-preview-field="{{ $field }}">
                                        <button class="btn btn-outline-secondary" form="restore-{{ $field }}" type="submit">Reset</button>
                                    </div>
                                    <small class="text-muted">Default: {{ $defaults[$field] }}</small>
                                </div>
                            @endforeach
                        </div>

                        <div class="row g-3 mb-3">
                            @foreach ($attendanceRoleFields as $field => [$label, $limit])
                                <div class="col-md-6">
                                    <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control register-modal-text-input" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $branding[$field]) }}" maxlength="{{ $limit }}" data-preview-group="attendance" data-preview-field="{{ $field }}">
                                        <button class="btn btn-outline-secondary" form="restore-{{ $field }}" type="submit">Reset</button>
                                    </div>
                                    <small class="text-muted">Default: {{ $defaults[$field] }}</small>
                                </div>
                            @endforeach
                        </div>

                        <h3 class="h6 mt-3 mb-2">Attendance colors</h3>
                        @foreach ($attendanceColorFields as $field => $label)
                            <div class="mb-3">
                                <label for="{{ $field }}_text" class="form-label d-flex justify-content-between gap-2">
                                    <span>{{ $label }}</span>
                                    <small class="text-muted">Default {{ $defaults[$field] }}</small>
                                </label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color register-modal-color-picker" value="{{ old($field, $branding[$field]) }}" data-target="{{ $field }}_text" data-preview-group="attendance" data-preview-target="{{ $field }}" aria-label="Choose {{ strtolower($label) }}">
                                    <input type="text" class="form-control register-modal-color-text" id="{{ $field }}_text" name="{{ $field }}" value="{{ old($field, $branding[$field]) }}" pattern="#[0-9A-Fa-f]{6}" required data-preview-group="attendance" data-preview-field="{{ $field }}">
                                    <button class="btn btn-outline-secondary" form="restore-{{ $field }}" type="submit">Reset</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="col-xl-6">
                {{-- Library Registration Settings --}}
                <section class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h5">Library registration</h2>
                        <p class="small text-muted">PNG, JPG, or WebP; 64–1000 px per side; maximum 2 MB.</p>

                        <div class="d-flex flex-wrap align-items-center gap-4 mb-3">
                            <img id="libraryRegisterLogoPreview" src="{{ $libraryLogoUrl }}" alt="Current Library register logo" class="rounded border p-2 bg-white" style="width:80px;height:80px;object-fit:contain">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <img src="{{ $originalLibraryLogoUrl }}" alt="Original Library register logo" class="rounded border p-1 bg-white" style="width:48px;height:48px;object-fit:contain">
                                    <span class="small fw-semibold">Original Pantas preview</span>
                                </div>
                                <label for="register_modal_library_logo" class="form-label">Upload Library logo</label>
                                <input class="form-control" type="file" id="register_modal_library_logo" name="register_modal_library_logo" accept="image/png,image/jpeg,image/webp">
                                <small class="text-muted d-block mt-2">Default: {{ $defaults['register_modal_library_logo_path'] }}</small>
                                <button class="btn btn-sm btn-outline-secondary mt-2" form="restore-register_modal_library_logo_path" type="submit">Restore logo</button>
                            </div>
                        </div>

                        @php
                            $libraryTextFields = [
                                'register_modal_library_welcome_label' => ['Welcome label', 80],
                                'register_modal_library_portal_name' => ['Portal name', 100],
                                'register_modal_library_description' => ['Description', 255],
                                'register_modal_library_heading' => ['Service heading', 120],
                            ];
                            $libraryRoleFields = [
                                'register_modal_library_student_label' => ['Student label', 80],
                                'register_modal_library_employee_label' => ['Faculty & Staff label', 80],
                                'register_modal_library_student_submit' => ['Student submit label', 120],
                                'register_modal_library_employee_submit' => ['Faculty & Staff submit label', 120],
                            ];
                            $libraryColorFields = [
                                'register_modal_library_panel_color' => 'Panel background',
                                'register_modal_library_welcome_portal_color' => 'Welcome and portal text',
                                'register_modal_library_description_color' => 'Description text',
                                'register_modal_library_text_color' => 'Modal text',
                                'register_modal_library_accent_color' => 'Accent',
                                'register_modal_library_active_role_color' => 'Active role',
                                'register_modal_library_submit_color' => 'Submit button',
                            ];
                        @endphp

                        <div class="row g-3 mb-3">
                            @foreach ($libraryTextFields as $field => [$label, $limit])
                                <div class="col-md-6">
                                    <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control register-modal-text-input" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $branding[$field]) }}" maxlength="{{ $limit }}" data-preview-group="library" data-preview-field="{{ $field }}">
                                        <button class="btn btn-outline-secondary" form="restore-{{ $field }}" type="submit">Reset</button>
                                    </div>
                                    <small class="text-muted">Default: {{ $defaults[$field] }}</small>
                                </div>
                            @endforeach
                        </div>

                        <div class="row g-3 mb-3">
                            @foreach ($libraryRoleFields as $field => [$label, $limit])
                                <div class="col-md-6">
                                    <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control register-modal-text-input" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $branding[$field]) }}" maxlength="{{ $limit }}" data-preview-group="library" data-preview-field="{{ $field }}">
                                        <button class="btn btn-outline-secondary" form="restore-{{ $field }}" type="submit">Reset</button>
                                    </div>
                                    <small class="text-muted">Default: {{ $defaults[$field] }}</small>
                                </div>
                            @endforeach
                        </div>

                        <h3 class="h6 mt-3 mb-2">Library colors</h3>
                        @foreach ($libraryColorFields as $field => $label)
                            <div class="mb-3">
                                <label for="{{ $field }}_text" class="form-label d-flex justify-content-between gap-2">
                                    <span>{{ $label }}</span>
                                    <small class="text-muted">Default {{ $defaults[$field] }}</small>
                                </label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color register-modal-color-picker" value="{{ old($field, $branding[$field]) }}" data-target="{{ $field }}_text" data-preview-group="library" data-preview-target="{{ $field }}" aria-label="Choose {{ strtolower($label) }}">
                                    <input type="text" class="form-control register-modal-color-text" id="{{ $field }}_text" name="{{ $field }}" value="{{ old($field, $branding[$field]) }}" pattern="#[0-9A-Fa-f]{6}" required data-preview-group="library" data-preview-field="{{ $field }}">
                                    <button class="btn btn-outline-secondary" form="restore-{{ $field }}" type="submit">Reset</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                {{-- Live Preview --}}
                <section class="card shadow-sm sticky-xl-top" style="top:1rem">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <h2 class="h5 mb-1">Live preview</h2>
                                <p class="small text-muted mb-0">Preview Attendance or Library registration branding.</p>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <div class="btn-group btn-group-sm" role="group" aria-label="Preview service">
                                    <button type="button" class="btn btn-primary" data-preview-service="attendance">Attendance</button>
                                    <button type="button" class="btn btn-outline-primary" data-preview-service="library">Library</button>
                                </div>
                                <div class="btn-group btn-group-sm" role="group" aria-label="Preview size">
                                    <button type="button" class="btn btn-primary" data-preview-size="desktop">Desktop</button>
                                    <button type="button" class="btn btn-outline-primary" data-preview-size="mobile">Mobile</button>
                                </div>
                            </div>
                        </div>

                        <div class="register-modal-preview-stage rounded border p-3">
                            {{-- Attendance Preview --}}
                            <div id="registerPreviewAttendance" class="register-modal-preview" style="--rm-panel:{{ old('register_modal_attendance_panel_color', $branding['register_modal_attendance_panel_color']) }};--rm-welcome-portal:{{ old('register_modal_attendance_welcome_portal_color', $branding['register_modal_attendance_welcome_portal_color']) }};--rm-description:{{ old('register_modal_attendance_description_color', $branding['register_modal_attendance_description_color']) }};--rm-text:{{ old('register_modal_attendance_text_color', $branding['register_modal_attendance_text_color']) }};--rm-accent:{{ old('register_modal_attendance_accent_color', $branding['register_modal_attendance_accent_color']) }};--rm-active-role:{{ old('register_modal_attendance_active_role_color', $branding['register_modal_attendance_active_role_color']) }};--rm-submit:{{ old('register_modal_attendance_submit_color', $branding['register_modal_attendance_submit_color']) }}">
                                <div class="rm-preview-panel">
                                    <small id="previewAttWelcome">{{ old('register_modal_attendance_welcome_label', $branding['register_modal_attendance_welcome_label']) }}</small>
                                    <span class="rm-preview-logo"><img src="{{ $attendanceLogoUrl }}" alt="Preview Attendance logo"></span>
                                    <strong id="previewAttPortalName">{{ old('register_modal_attendance_portal_name', $branding['register_modal_attendance_portal_name']) }}</strong>
                                    <p id="previewAttDescription">{{ old('register_modal_attendance_description', $branding['register_modal_attendance_description']) }}</p>
                                </div>
                                <div class="rm-preview-form">
                                    <h3 id="previewAttHeading">{{ old('register_modal_attendance_heading', $branding['register_modal_attendance_heading']) }}</h3>
                                    <div class="rm-roles">
                                        <span class="rm-role rm-role-active" id="previewAttStudentLabel">{{ old('register_modal_attendance_student_label', $branding['register_modal_attendance_student_label']) }}</span>
                                        <span class="rm-role" id="previewAttEmployeeLabel">{{ old('register_modal_attendance_employee_label', $branding['register_modal_attendance_employee_label']) }}</span>
                                    </div>
                                    <div class="rm-submit" id="previewAttSubmit">{{ old('register_modal_attendance_student_submit', $branding['register_modal_attendance_student_submit']) }}</div>
                                </div>
                            </div>

                            {{-- Library Preview --}}
                            <div id="registerPreviewLibrary" class="register-modal-preview d-none" style="--rm-panel:{{ old('register_modal_library_panel_color', $branding['register_modal_library_panel_color']) }};--rm-welcome-portal:{{ old('register_modal_library_welcome_portal_color', $branding['register_modal_library_welcome_portal_color']) }};--rm-description:{{ old('register_modal_library_description_color', $branding['register_modal_library_description_color']) }};--rm-text:{{ old('register_modal_library_text_color', $branding['register_modal_library_text_color']) }};--rm-accent:{{ old('register_modal_library_accent_color', $branding['register_modal_library_accent_color']) }};--rm-active-role:{{ old('register_modal_library_active_role_color', $branding['register_modal_library_active_role_color']) }};--rm-submit:{{ old('register_modal_library_submit_color', $branding['register_modal_library_submit_color']) }}">
                                <div class="rm-preview-panel">
                                    <small id="previewLibWelcome">{{ old('register_modal_library_welcome_label', $branding['register_modal_library_welcome_label']) }}</small>
                                    <span class="rm-preview-logo"><img src="{{ $libraryLogoUrl }}" alt="Preview Library logo"></span>
                                    <strong id="previewLibPortalName">{{ old('register_modal_library_portal_name', $branding['register_modal_library_portal_name']) }}</strong>
                                    <p id="previewLibDescription">{{ old('register_modal_library_description', $branding['register_modal_library_description']) }}</p>
                                </div>
                                <div class="rm-preview-form">
                                    <h3 id="previewLibHeading">{{ old('register_modal_library_heading', $branding['register_modal_library_heading']) }}</h3>
                                    <div class="rm-roles">
                                        <span class="rm-role rm-role-active" id="previewLibStudentLabel">{{ old('register_modal_library_student_label', $branding['register_modal_library_student_label']) }}</span>
                                        <span class="rm-role" id="previewLibEmployeeLabel">{{ old('register_modal_library_employee_label', $branding['register_modal_library_employee_label']) }}</span>
                                    </div>
                                    <div class="rm-submit" id="previewLibSubmit">{{ old('register_modal_library_student_submit', $branding['register_modal_library_student_submit']) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-4">
            <button type="submit" class="btn btn-primary">Save Register Modal</button>
            <a href="{{ route('dashboard.developer') }}" class="btn btn-outline-secondary">Cancel</a>
            <a href="{{ route('developer.branding.versions') }}" class="btn btn-outline-info">Version History</a>
            <button type="submit" form="restore-register-modal-all" class="btn btn-outline-danger ms-auto">Restore Register Modal Defaults</button>
        </div>
    </form>

    {{-- Individual restore forms --}}
    @php
        $allRegisterFields = array_keys($sharedTextFields + $attendanceTextFields + $attendanceRoleFields + $attendanceColorFields + $libraryTextFields + $libraryRoleFields + $libraryColorFields);
    @endphp
    <form id="restore-register_modal_attendance_logo_path" method="POST" action="{{ route('developer.register-modal.restore') }}" class="d-none">@csrf<input type="hidden" name="field" value="register_modal_attendance_logo_path"></form>
    <form id="restore-register_modal_library_logo_path" method="POST" action="{{ route('developer.register-modal.restore') }}" class="d-none">@csrf<input type="hidden" name="field" value="register_modal_library_logo_path"></form>
    @foreach ($allRegisterFields as $field)
        <form id="restore-{{ $field }}" method="POST" action="{{ route('developer.register-modal.restore') }}" class="d-none">@csrf<input type="hidden" name="field" value="{{ $field }}"></form>
    @endforeach
    <form id="restore-register-modal-all" method="POST" action="{{ route('developer.register-modal.restore') }}" class="d-none" onsubmit="return confirm('Restore all register modal settings to the original Pantas defaults?')">@csrf</form>
</div>

@endsection

@push('styles')
<style>
.register-modal-preview-stage{background:#e5e7eb;min-height:400px;display:grid;place-items:center;overflow:auto}.register-modal-preview{display:grid;grid-template-columns:1fr 1.15fr;width:min(680px,100%);min-height:360px;overflow:hidden;border-radius:22px;background:#fff;color:var(--rm-text);box-shadow:0 22px 60px rgba(15,23,42,.22);transition:width .2s ease}.rm-preview-panel,.rm-preview-form{padding:32px;display:flex;flex-direction:column;justify-content:center}.rm-preview-panel{align-items:center;text-align:center;background:var(--rm-panel);color:var(--rm-welcome-portal)}.rm-preview-panel small{text-transform:uppercase;letter-spacing:.12em;font-weight:700}.rm-preview-logo{display:grid;place-items:center;width:72px;height:72px;margin:16px;border-radius:20px;background:#fff}.rm-preview-logo img{width:56px;height:56px;object-fit:contain}.rm-preview-panel strong{font-size:26px}.rm-preview-panel p{font-size:13px;margin:12px 0 0;color:var(--rm-description)}.rm-preview-form h3{font-size:22px;margin-bottom:18px}.rm-roles{display:flex;gap:10px;margin-bottom:18px}.rm-role{border:1px solid #d3dbea;border-radius:999px;padding:8px 14px;font-size:13px;font-weight:900;background:#fff}.rm-role-active{color:#fff;background:var(--rm-active-role);border-color:var(--rm-active-role)}.rm-preview-form .rm-submit{border-radius:9px;padding:11px;text-align:center;color:#fff;background:var(--rm-submit);font-weight:700}.register-modal-preview.is-mobile{grid-template-columns:1fr;width:min(340px,100%)}.register-modal-preview.is-mobile .rm-preview-panel{padding:22px}.register-modal-preview.is-mobile .rm-preview-form{padding:24px}.register-modal-preview.is-mobile .rm-preview-panel p{display:none}@media(max-width:767.98px){.register-modal-preview{grid-template-columns:1fr}.rm-preview-panel{padding:22px}.rm-preview-form{padding:24px}}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const previewAtt = document.getElementById('registerPreviewAttendance');
    const previewLib = document.getElementById('registerPreviewLibrary');
    let activeService = 'attendance';
    let activeSize = 'desktop';

    // Shared field preview targets
    const sharedTargets = {};
    const attTargets = {};
    const libTargets = {};

    // Color CSS variable targets
    const attColorTargets = {
        register_modal_attendance_panel_color: '--rm-panel',
        register_modal_attendance_welcome_portal_color: '--rm-welcome-portal',
        register_modal_attendance_description_color: '--rm-description',
        register_modal_attendance_text_color: '--rm-text',
        register_modal_attendance_accent_color: '--rm-accent',
        register_modal_attendance_active_role_color: '--rm-active-role',
        register_modal_attendance_submit_color: '--rm-submit',
    };
    const libColorTargets = {
        register_modal_library_panel_color: '--rm-panel',
        register_modal_library_welcome_portal_color: '--rm-welcome-portal',
        register_modal_library_description_color: '--rm-description',
        register_modal_library_text_color: '--rm-text',
        register_modal_library_accent_color: '--rm-accent',
        register_modal_library_active_role_color: '--rm-active-role',
        register_modal_library_submit_color: '--rm-submit',
    };

    // Build preview element ID map from data attributes
    document.querySelectorAll('.register-modal-text-input').forEach(function (input) {
        const group = input.dataset.previewGroup;
        const field = input.dataset.previewField;
        if (group === 'shared') {
            if (field === 'register_modal_heading') sharedTargets[field] = 'registerHeading';
            else if (field === 'register_modal_login_label') sharedTargets[field] = 'registerLoginLabel';
            else if (field === 'register_modal_attendance_tab') sharedTargets[field] = 'attendanceTab';
            else if (field === 'register_modal_library_tab') sharedTargets[field] = 'libraryTab';
        } else if (group === 'attendance') {
            const map = {
                register_modal_attendance_welcome_label: 'previewAttWelcome',
                register_modal_attendance_portal_name: 'previewAttPortalName',
                register_modal_attendance_description: 'previewAttDescription',
                register_modal_attendance_heading: 'previewAttHeading',
                register_modal_attendance_student_label: 'previewAttStudentLabel',
                register_modal_attendance_employee_label: 'previewAttEmployeeLabel',
                register_modal_attendance_student_submit: 'previewAttSubmit',
                register_modal_attendance_employee_submit: 'previewAttSubmit',
            };
            attTargets[field] = map[field] || null;
        } else if (group === 'library') {
            const map = {
                register_modal_library_welcome_label: 'previewLibWelcome',
                register_modal_library_portal_name: 'previewLibPortalName',
                register_modal_library_description: 'previewLibDescription',
                register_modal_library_heading: 'previewLibHeading',
                register_modal_library_student_label: 'previewLibStudentLabel',
                register_modal_library_employee_label: 'previewLibEmployeeLabel',
                register_modal_library_student_submit: 'previewLibSubmit',
                register_modal_library_employee_submit: 'previewLibSubmit',
            };
            libTargets[field] = map[field] || null;
        }
    });

    function getActivePreview() {
        return activeService === 'attendance' ? previewAtt : previewLib;
    }

    function updatePreview() {
        previewAtt.classList.toggle('d-none', activeService !== 'attendance');
        previewLib.classList.toggle('d-none', activeService !== 'library');
        const active = getActivePreview();
        active.classList.toggle('is-mobile', activeSize === 'mobile');
    }

    function switchService(service) {
        activeService = service;
        document.querySelectorAll('[data-preview-service]').forEach(function (btn) {
            const isActive = btn.dataset.previewService === service;
            btn.classList.toggle('btn-primary', isActive);
            btn.classList.toggle('btn-outline-primary', !isActive);
        });
        updatePreview();
    }

    function switchSize(size) {
        activeSize = size;
        document.querySelectorAll('[data-preview-size]').forEach(function (btn) {
            const isActive = btn.dataset.previewSize === size;
            btn.classList.toggle('btn-primary', isActive);
            btn.classList.toggle('btn-outline-primary', !isActive);
        });
        updatePreview();
    }

    // Text input live updates
    document.querySelectorAll('.register-modal-text-input').forEach(function (input) {
        input.addEventListener('input', function () {
            const group = this.dataset.previewGroup;
            const field = this.dataset.previewField;
            let el = null;
            if (group === 'attendance') {
                el = attTargets[field] ? document.getElementById(attTargets[field]) : null;
            } else if (group === 'library') {
                el = libTargets[field] ? document.getElementById(libTargets[field]) : null;
            }
            if (el) el.textContent = this.value || '';
        });
    });

    // Color picker sync
    document.querySelectorAll('.register-modal-color-picker').forEach(function (picker) {
        picker.addEventListener('input', function () {
            const target = document.getElementById(this.dataset.target);
            if (target) {
                target.value = this.value.toUpperCase();
                target.dispatchEvent(new Event('input'));
            }
        });
    });

    // Color text input updates
    document.querySelectorAll('.register-modal-color-text').forEach(function (input) {
        input.addEventListener('input', function () {
            if (!/^#[0-9A-Fa-f]{6}$/.test(this.value)) return;
            const picker = document.querySelector('[data-target="' + this.id + '"]');
            if (picker) picker.value = this.value;
            const group = this.dataset.previewGroup;
            const field = this.dataset.previewField;
            const targets = group === 'attendance' ? attColorTargets : libColorTargets;
            const cssVar = targets[field];
            if (cssVar) {
                const active = getActivePreview();
                active.style.setProperty(cssVar, this.value);
            }
        });
    });

    // Logo upload previews
    document.getElementById('register_modal_attendance_logo')?.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file) return;
        const url = URL.createObjectURL(file);
        document.getElementById('attendanceRegisterLogoPreview').src = url;
        previewAtt.querySelector('.rm-preview-logo img').src = url;
    });

    document.getElementById('register_modal_library_logo')?.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file) return;
        const url = URL.createObjectURL(file);
        document.getElementById('libraryRegisterLogoPreview').src = url;
        previewLib.querySelector('.rm-preview-logo img').src = url;
    });

    // Service toggle
    document.querySelectorAll('[data-preview-service]').forEach(function (button) {
        button.addEventListener('click', function () {
            switchService(this.dataset.previewService);
        });
    });

    // Size toggle
    document.querySelectorAll('[data-preview-size]').forEach(function (button) {
        button.addEventListener('click', function () {
            switchSize(this.dataset.previewSize);
        });
    });
});
</script>
@endpush
