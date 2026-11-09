@extends('layouts.sidebar')

@section('title', 'Circulation Policy')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/admin/circulation.css') }}?v={{ filemtime(public_path('css/admin/circulation.css')) }}">
<style>
    #holiday-calendar { min-height: 300px; }
    #calendarWrapper.is-open { display: block !important; }
    #calendarError { display: none; }
    #calendarError.is-visible { display: block; }
    .policy-shell { max-width: 920px; }
    .policy-sublocation {
        background: #fff;
        border: 1px solid #c9dfc4;
        border-radius: 10px;
        padding: 0.7rem 0.85rem 0.75rem;
    }
    .policy-sublocation .form-select {
        border-color: #1b5e20;
        font-weight: 600;
    }
    .policy-sublocation .form-select:focus {
        border-color: #1b5e20;
        box-shadow: 0 0 0 0.2rem rgba(27, 94, 32, 0.15);
    }
</style>
@endsection

@section('content')
<div class="container py-2 policy-shell circ-admin circ-policy-compact">
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2 circ-policy-page-head">
        <div>
            <h4 class="mb-0">Circulation Policy</h4>
            <p class="text-muted mb-0 small">Choose a sublocation. Borrow limits, renewals, and fines follow that shelf.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('logs.index') }}" class="btn btn-outline-secondary btn-sm">Circulation desk</a>
            <a href="{{ route('fines.outstanding') }}" class="btn btn-outline-secondary btn-sm">Outstanding fines</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
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

    <form method="POST" action="{{ route('circulation.policy.update') }}" id="circulationPolicyForm">
        @csrf

        <div class="policy-sublocation mb-2">
            <label for="policySublocation" class="form-label mb-1">Sublocation / shelving</label>
            <select name="sublocation" id="policySublocation" class="form-select form-select-sm" data-current="{{ $selectedSublocation }}" required>
                @foreach($sublocations as $name)
                    <option value="{{ $name }}" @selected(old('sublocation', $selectedSublocation) === $name)>{{ $name }}</option>
                @endforeach
            </select>
            <div class="form-text">
                @if($hasSavedPolicy)
                    Showing the saved policy for <strong>{{ $selectedSublocation }}</strong>.
                @else
                    <strong>{{ $selectedSublocation }}</strong> still uses the shared defaults. Save to give it its own policy.
                @endif
                Loan limits count only books in this sublocation. Holidays stay the same for every shelf.
            </div>
        </div>

        <ul class="nav nav-pills circ-policy-pills flex-wrap mb-2" id="policyTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="borrow-tab" data-bs-toggle="tab" data-bs-target="#borrow-pane" type="button" role="tab" aria-controls="borrow-pane" aria-selected="true">Borrow Limits</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="renewals-tab" data-bs-toggle="tab" data-bs-target="#renewals-pane" type="button" role="tab" aria-controls="renewals-pane" aria-selected="false">Renewals</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="fines-tab" data-bs-toggle="tab" data-bs-target="#fines-pane" type="button" role="tab" aria-controls="fines-pane" aria-selected="false">Fines &amp; Loans</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="holidays-tab" data-bs-toggle="tab" data-bs-target="#holidays-pane" type="button" role="tab" aria-controls="holidays-pane" aria-selected="false">Holidays</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary-pane" type="button" role="tab" aria-controls="summary-pane" aria-selected="false">Summary</button>
            </li>
        </ul>

        <div class="circ-policy-tab-card">
            <div class="tab-content" id="policyTabsContent">
                <div class="tab-pane fade show active" id="borrow-pane" role="tabpanel" aria-labelledby="borrow-tab" tabindex="0">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="circ-form-section circ-form-section--student mb-0 h-100">
                                <div class="circ-section-heading">
                                    <span class="circ-section-badge circ-section-badge--student">Students</span>
                                    <h6 class="circ-section-title">Borrow limit</h6>
                                </div>
                                <div class="circ-form-field">
                                    <label for="studentMax" class="form-label">Max books on loan</label>
                                    <input type="number" name="student_max" id="studentMax" class="form-control form-control-sm"
                                           min="1" max="100" value="{{ old('student_max', $studentMax) }}" required>
                                    <div class="form-text">Recommended: 5.</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="circ-form-section circ-form-section--staff mb-0 h-100">
                                <div class="circ-section-heading">
                                    <span class="circ-section-badge circ-section-badge--staff">Faculty &amp; staff</span>
                                    <h6 class="circ-section-title">Borrow limit</h6>
                                </div>
                                <input type="hidden" name="employee_unlimited" value="0">
                                <div class="circ-form-toggle-box">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="employeeUnlimited"
                                               name="employee_unlimited" value="1"
                                               {{ old('employee_unlimited', $employeeUnlimited ? '1' : '0') === '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="employeeUnlimited">Unlimited</label>
                                    </div>
                                </div>
                                <div id="employeeMaxField" class="circ-form-field">
                                    <label for="employeeMax" class="form-label">Max books on loan</label>
                                    <input type="number" name="employee_max" id="employeeMax" class="form-control form-control-sm"
                                           min="1" max="100" value="{{ old('employee_max', $employeeMax) }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="renewals-pane" role="tabpanel" aria-labelledby="renewals-tab" tabindex="0">
                    <div class="circ-form-section circ-form-section--renewal mb-0">
                        <div class="circ-section-heading">
                            <span class="circ-section-badge circ-section-badge--renewal">Rules</span>
                            <h6 class="circ-section-title">Renewals &amp; reservations</h6>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="circ-form-field">
                                    <label for="maxRenewals" class="form-label">Max renewals / loan</label>
                                    <input type="number" name="max_renewals" id="maxRenewals" class="form-control form-control-sm"
                                           min="0" max="50" value="{{ old('max_renewals', $maxRenewals) }}" required>
                                    <div class="form-text">0 = disabled.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="circ-form-field">
                                    <label for="reborrowCooldownDays" class="form-label">Re-borrow cooldown (days)</label>
                                    <input type="number" name="reborrow_cooldown_days" id="reborrowCooldownDays" class="form-control form-control-sm"
                                           min="0" max="365" value="{{ old('reborrow_cooldown_days', $reborrowCooldownDays) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="circ-form-field">
                                    <label for="reservationHoldDays" class="form-label">OPAC hold period (days)</label>
                                    <input type="number" name="reservation_hold_days" id="reservationHoldDays" class="form-control form-control-sm"
                                           min="1" max="365" value="{{ old('reservation_hold_days', $reservationHoldDays) }}" required>
                                    <div class="form-text">Auto-release when expired.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="fines-pane" role="tabpanel" aria-labelledby="fines-tab" tabindex="0">
                    <div class="row g-2">
                        <div class="col-lg-6">
                            <div class="circ-form-section circ-form-section--student h-100 mb-0">
                                <div class="circ-section-heading">
                                    <span class="circ-section-badge circ-section-badge--student">Students</span>
                                    <h6 class="circ-section-title">Fines &amp; loan terms</h6>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="circ-form-field">
                                            <label class="form-label">Fine / day (₱)</label>
                                            <input type="number" step="0.01" name="student_fine_per_day" class="form-control form-control-sm"
                                                   value="{{ old('student_fine_per_day', $studentTerms->fine_per_day ?? '') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="circ-form-field">
                                            <label class="form-label">Max fine (₱)</label>
                                            <input type="number" step="0.01" name="student_max_fine" class="form-control form-control-sm"
                                                   value="{{ old('student_max_fine', $studentTerms->max_fine ?? '') }}">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="circ-form-field">
                                            <label class="form-label">Loan duration (days)</label>
                                            <input type="number" name="student_loan_duration_days" class="form-control form-control-sm" min="1" max="365"
                                                   value="{{ old('student_loan_duration_days', $studentTerms->loan_duration_days ?? 7) }}" required>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="circ-form-field">
                                            <label class="form-label">Grace period (days)</label>
                                            <input type="number" name="student_grace_period_days" class="form-control form-control-sm" min="0"
                                                   value="{{ old('student_grace_period_days', $studentTerms->grace_period_days ?? 0) }}" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="circ-form-section circ-form-section--staff h-100 mb-0">
                                <div class="circ-section-heading">
                                    <span class="circ-section-badge circ-section-badge--staff">Faculty &amp; staff</span>
                                    <h6 class="circ-section-title">Fines &amp; loan terms</h6>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="circ-form-field">
                                            <label class="form-label">Fine / day (₱)</label>
                                            <input type="number" step="0.01" name="employee_fine_per_day" class="form-control form-control-sm"
                                                   value="{{ old('employee_fine_per_day', $employeeTerms->fine_per_day ?? '') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="circ-form-field">
                                            <label class="form-label">Max fine (₱)</label>
                                            <input type="number" step="0.01" name="employee_max_fine" class="form-control form-control-sm"
                                                   value="{{ old('employee_max_fine', $employeeTerms->max_fine ?? '') }}">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="circ-form-field">
                                            <label class="form-label">Loan duration (days)</label>
                                            <input type="number" name="employee_loan_duration_days" class="form-control form-control-sm" min="1" max="365"
                                                   value="{{ old('employee_loan_duration_days', $employeeTerms->loan_duration_days ?? 7) }}" required>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="circ-form-field">
                                            <label class="form-label">Grace period (days)</label>
                                            <input type="number" name="employee_grace_period_days" class="form-control form-control-sm" min="0"
                                                   value="{{ old('employee_grace_period_days', $employeeTerms->grace_period_days ?? 0) }}" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($fineSettings)
                        <p class="text-muted small mt-2 mb-0">
                            Effective since {{ $fineSettings->effective_from }}
                        </p>
                    @endif
                </div>

                <div class="tab-pane fade" id="holidays-pane" role="tabpanel" aria-labelledby="holidays-tab" tabindex="0">
                    <div class="circ-form-section circ-form-section--holiday mb-0">
                        <div class="circ-section-heading">
                            <span class="circ-section-badge" style="color:#dc3545;background:#fff1f2;border:1px solid #f5c2c7;">Calendar</span>
                            <h6 class="circ-section-title">Holidays</h6>
                        </div>
                        <p class="text-muted small">Closed days apply to every sublocation. Switching the shelf above does not change this calendar.</p>
                        @include('admin.partials.holiday_calendar_panel')
                    </div>
                </div>

                <div class="tab-pane fade" id="summary-pane" role="tabpanel" aria-labelledby="summary-tab" tabindex="0">
                    <div class="row g-2">
                        <div class="col-md-6 col-lg-3">
                            <div class="circ-summary-tile">
                                <h6 class="mb-1">Borrow</h6>
                                <ul class="small mb-0">
                                    <li>Students: <strong>{{ $studentMax }}</strong></li>
                                    <li>Staff: @if($employeeUnlimited)<strong>Unlimited</strong>@else<strong>{{ $employeeMax }}</strong>@endif</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="circ-summary-tile">
                                <h6 class="mb-1">Renewals</h6>
                                <ul class="small mb-0">
                                    <li>Max: <strong>{{ $maxRenewals }}</strong></li>
                                    <li>Cooldown: <strong>{{ $reborrowCooldownDays }}</strong>d</li>
                                    <li>OPAC hold: <strong>{{ $reservationHoldDays }}</strong>d</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="circ-summary-tile circ-summary-tile--student">
                                <h6 class="mb-1">Students</h6>
                                <ul class="small mb-0">
                                    <li>₱{{ number_format($studentTerms->fine_per_day ?? 0, 2) }}/day</li>
                                    <li><strong>{{ $studentTerms->loan_duration_days ?? '—' }}</strong>d loan</li>
                                    <li><strong>{{ $studentTerms->grace_period_days ?? 0 }}</strong>d grace</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="circ-summary-tile circ-summary-tile--staff">
                                <h6 class="mb-1">Faculty &amp; staff</h6>
                                <ul class="small mb-0">
                                    <li>₱{{ number_format($employeeTerms->fine_per_day ?? 0, 2) }}/day</li>
                                    <li><strong>{{ $employeeTerms->loan_duration_days ?? '—' }}</strong>d loan</li>
                                    <li><strong>{{ $employeeTerms->grace_period_days ?? 0 }}</strong>d grace</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="circ-policy-save-bar d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="text-muted small mb-0">Saves the <strong>{{ $selectedSublocation }}</strong> policy for the desk, self-checkout, and renewals.</span>
            <button type="submit" class="btn btn-primary btn-sm px-3">Save policy</button>
        </div>
    </form>
</div>

@include('admin.partials.holiday_calendar_modals')
@endsection

@section('scripts')
@include('admin.partials.holiday_calendar_scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const unlimited = document.getElementById('employeeUnlimited');
    const maxField = document.getElementById('employeeMaxField');
    const maxInput = document.getElementById('employeeMax');
    const tabButtons = document.querySelectorAll('#policyTabs button[data-bs-toggle="tab"]');
    const sublocation = document.getElementById('policySublocation');
    const policyForm = document.getElementById('circulationPolicyForm');
    let policyDirty = false;

    policyForm.addEventListener('input', function (event) {
        if (event.target === sublocation) {
            return;
        }
        policyDirty = true;
    });

    sublocation.addEventListener('change', function () {
        if (policyDirty && !window.confirm('Switch sublocation? Unsaved policy changes on this page will be discarded.')) {
            sublocation.value = sublocation.dataset.current;
            return;
        }

        const url = new URL(window.location.href);
        url.searchParams.set('sublocation', sublocation.value);
        window.location.assign(url.toString());
    });

    function syncEmployeeMaxField() {
        const isUnlimited = unlimited.checked;
        maxField.style.display = isUnlimited ? 'none' : 'block';
        maxInput.required = !isUnlimited;
    }

    unlimited.addEventListener('change', syncEmployeeMaxField);
    syncEmployeeMaxField();

    function activateTabFromHash() {
        const hash = window.location.hash.replace('#', '');
        if (!hash) return;

        const target = document.querySelector('#policyTabs button[data-bs-target="#' + hash + '"]');
        if (!target || !window.bootstrap) return;

        bootstrap.Tab.getOrCreateInstance(target).show();
    }

    tabButtons.forEach(function (button) {
        button.addEventListener('shown.bs.tab', function (event) {
            const paneId = event.target.getAttribute('data-bs-target').replace('#', '');
            history.replaceState(null, '', '#' + paneId);

            if (paneId === 'holidays-pane' && window.__policyHolidayCalendar) {
                requestAnimationFrame(function () {
                    window.__policyHolidayCalendar.updateSize();
                });
            }
        });
    });

    activateTabFromHash();

    @if($errors->any())
        const firstInvalid = document.querySelector('#circulationPolicyForm :invalid');
        if (firstInvalid) {
            const pane = firstInvalid.closest('.tab-pane');
            if (pane && pane.id) {
                const tab = document.querySelector('#policyTabs button[data-bs-target="#' + pane.id + '"]');
                if (tab && window.bootstrap) {
                    bootstrap.Tab.getOrCreateInstance(tab).show();
                }
            }
        }
    @endif
});
</script>
@endsection
