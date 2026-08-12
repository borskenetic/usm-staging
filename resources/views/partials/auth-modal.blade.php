@php
    $modalShouldOpen = ($forceAuthModalOpen ?? false) || session('auth_modal') || $errors->any() || session('success') || session('error') || session('status');
    $initialView = old('modal_view', session('auth_modal', $forcedAuthView ?? ($errors->any() ? 'register' : 'login')));
    $initialService = old('service', session('auth_service', $forcedAuthService ?? 'attendance'));
    $initialType = old('registration_type', session('auth_type', $forcedAuthType ?? 'student'));
    $yearOptions = ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'];
@endphp

<div
    class="lm-overlay"
    id="lmOverlay"
    data-open-on-load="{{ $modalShouldOpen ? 'true' : 'false' }}"
    data-initial-view="{{ $initialView }}"
    data-initial-service="{{ $initialService }}"
    data-initial-type="{{ $initialType }}"
    data-login-welcome="{{ $activeBranding['login_modal_welcome_label'] }}"
    data-login-portal-name="{{ $activeBranding['login_modal_portal_name'] }}"
    data-login-description="{{ $activeBranding['login_modal_description'] }}"
    data-register-heading="{{ $activeBranding['register_modal_heading'] }}"
    data-register-login-label="{{ $activeBranding['register_modal_login_label'] }}"
    data-register-attendance-tab="{{ $activeBranding['register_modal_attendance_tab'] }}"
    data-register-library-tab="{{ $activeBranding['register_modal_library_tab'] }}"
    data-register-att-welcome="{{ $activeBranding['register_modal_attendance_welcome_label'] }}"
    data-register-att-name="{{ $activeBranding['register_modal_attendance_portal_name'] }}"
    data-register-att-description="{{ $activeBranding['register_modal_attendance_description'] }}"
    data-register-att-logo-src="{{ $brandingAttendanceRegisterLogoUrl }}"
    data-register-lib-welcome="{{ $activeBranding['register_modal_library_welcome_label'] }}"
    data-register-lib-name="{{ $activeBranding['register_modal_library_portal_name'] }}"
    data-register-lib-description="{{ $activeBranding['register_modal_library_description'] }}"
    data-register-lib-logo-src="{{ $brandingLibraryRegisterLogoUrl }}"
    data-close-url="{{ $authModalCloseUrl ?? '' }}"
    style="--lm-login-left:{{ $activeBranding['login_modal_left_background_color'] }};--lm-login-welcome-portal:{{ $activeBranding['login_modal_welcome_portal_color'] }};--lm-login-description:{{ $activeBranding['login_modal_description_color'] }};--lm-login-bg:{{ $activeBranding['login_modal_background_color'] }};--lm-login-form-bg:{{ $activeBranding['login_modal_form_background_color'] }};--lm-login-form-border:{{ $activeBranding['login_modal_form_border_color'] }};--lm-login-text:{{ $activeBranding['login_modal_text_color'] }};--lm-login-button:{{ $activeBranding['login_modal_button_color'] }};--lm-reg-att-panel:{{ $activeBranding['register_modal_attendance_panel_color'] }};--lm-reg-att-welcome-portal:{{ $activeBranding['register_modal_attendance_welcome_portal_color'] }};--lm-reg-att-description:{{ $activeBranding['register_modal_attendance_description_color'] }};--lm-reg-att-text:{{ $activeBranding['register_modal_attendance_text_color'] }};--lm-reg-att-accent:{{ $activeBranding['register_modal_attendance_accent_color'] }};--lm-reg-att-active:{{ $activeBranding['register_modal_attendance_active_role_color'] }};--lm-reg-att-submit:{{ $activeBranding['register_modal_attendance_submit_color'] }};--lm-reg-lib-panel:{{ $activeBranding['register_modal_library_panel_color'] }};--lm-reg-lib-welcome-portal:{{ $activeBranding['register_modal_library_welcome_portal_color'] }};--lm-reg-lib-description:{{ $activeBranding['register_modal_library_description_color'] }};--lm-reg-lib-text:{{ $activeBranding['register_modal_library_text_color'] }};--lm-reg-lib-accent:{{ $activeBranding['register_modal_library_accent_color'] }};--lm-reg-lib-active:{{ $activeBranding['register_modal_library_active_role_color'] }};--lm-reg-lib-submit:{{ $activeBranding['register_modal_library_submit_color'] }}"
    aria-hidden="true"
>
    <div class="lm-card-wrap">
        <button class="lm-close" type="button" data-lm-close aria-label="Close login modal">
            <span aria-hidden="true">&times;</span>
        </button>

        <div class="lm-card">
            <aside class="lm-left login-mode" id="lmLeft">
                <div class="lm-waves"><span></span><span></span><span></span></div>
                <div class="lm-left-top" id="lmLeftTop">{{ $activeBranding['login_modal_welcome_label'] }}</div>
                <div class="lm-badge lib" id="lmBadge">
                    <img
                        id="lmBadgeImage"
                        src="{{ $brandingLoginModalLogoUrl }}"
                        data-login-src="{{ $brandingLoginModalLogoUrl }}"
                        data-registration-att-src="{{ $brandingAttendanceRegisterLogoUrl }}"
                        data-registration-lib-src="{{ $brandingLibraryRegisterLogoUrl }}"
                        data-default-registration-src="{{ asset('img/pantas-10.png') }}"
                        alt="PANTAS mark"
                    >
                </div>
                <div class="lm-brandname" id="lmBrandName">{{ $activeBranding['login_modal_portal_name'] }}</div>
                <p class="lm-blurb" id="lmBlurb">{{ $activeBranding['login_modal_description'] }}</p>
                <div class="lm-left-links">
                    <button type="button" data-lm-go="register">Register</button>
                    <span class="lm-dot">|</span>
                    <a href="{{ route('landing') }}">DISCOVER MORE</a>
                </div>
            </aside>

            <div class="lm-right">
                <div class="lm-views" id="lmViews">
                    <section class="lm-view" data-lm-view-panel="login">
                        <h2>{{ $activeBranding['login_modal_sign_in_heading'] }}</h2>

                        @if (session('status') || session('success'))
                            <div class="lm-flash-success">{{ session('status') ?? session('success') }}</div>
                        @endif

                        @if (session('error'))
                            <div class="lm-flash-error">{{ session('error') }}</div>
                        @endif

                        @if ($errors->any())
                            <div class="lm-flash-error">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="lm-form">

                            <form method="POST" action="{{ route('login') }}">
                                @csrf
                                <input type="hidden" name="modal_view" value="login">

                                <label for="lm_email">Email <span class="lm-req">*</span></label>
                                <input
                                    class="lm-input lm-mb"
                                    type="email"
                                    id="lm_email"
                                    name="email"
                                    placeholder="{{ $activeBranding['login_modal_email_placeholder'] }}"
                                    value="{{ old('email') }}"
                                    autocomplete="username"
                                    required
                                >

                                <label for="lm_password">Password <span class="lm-req">*</span></label>
                                <input
                                    class="lm-input"
                                    type="password"
                                    id="lm_password"
                                    name="password"
                                    placeholder="{{ $activeBranding['login_modal_password_placeholder'] }}"
                                    autocomplete="current-password"
                                    required
                                >

                                <label class="lm-remember">
                                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                                    Remember me
                                </label>

                                <button type="submit" class="lm-btn lib">Sign In</button>
                            </form>

                        </div>
                        <button class="lm-swap" type="button" data-lm-go="register">Register</button>
                    </section>

                    <section class="lm-view" data-lm-view-panel="register">
                        <div class="lm-reg-head">
                            <h2>Register</h2>
                            <button class="lm-back" type="button" data-lm-go="login">Login</button>
                        </div>

                        <div class="lm-service" id="lmService">
                            <span class="lm-pill"></span>
                            <button id="lmSv0" type="button" data-lm-service="attendance">Attendance</button>
                            <button id="lmSv1" type="button" data-lm-service="library">Library</button>
                        </div>

                        <div class="lm-svc-window" id="lmSvcWindow">
                            <div class="lm-svc-track" id="lmSvcTrack">
                                <div class="lm-svc lm-att-form">
                                    <h3 class="lm-service-title">Attendance Registration</h3>
                                    <div class="lm-roles">
                                        <button class="lm-role" type="button" data-lm-role="attendance-student">Student</button>
                                        <button class="lm-role" type="button" data-lm-role="attendance-employee">Employee</button>
                                    </div>

                                    <div class="lm-role-panel" data-lm-role-panel="attendance-student">
                                        <form method="POST" action="{{ route('attendance.pending.store') }}" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="modal_view" value="register">
                                            <input type="hidden" name="service" value="attendance">
                                            <input type="hidden" name="registration_type" value="student">
                                            <input type="hidden" name="student_signature" id="lmSigAttStudent">

                                            <div class="lm-section-title att">Student Information</div>
                                            <div class="lm-grid2">
                                                <div><label>First name <span class="lm-req">*</span></label><input class="lm-input" name="firstname" value="{{ old('firstname') }}" required></div>
                                                <div><label>Middle initial</label><input class="lm-input" name="middle_initial" value="{{ old('middle_initial') }}"></div>
                                                <div><label>Last name <span class="lm-req">*</span></label><input class="lm-input" name="lastname" value="{{ old('lastname') }}" required></div>
                                                <div><label>Student ID <span class="lm-req">*</span></label><input class="lm-input" name="student_id" value="{{ old('student_id') }}" required></div>
                                                <div><label>Birth date</label><input class="lm-input" type="date" name="birth_date" value="{{ old('birth_date') }}"></div>
                                                <div><label>Educational level</label><input class="lm-input" name="educational_level" value="{{ old('educational_level') }}" placeholder="College"></div>
                                                <div><label>Mobile number</label><input class="lm-input" name="mobile_number" value="{{ old('mobile_number') }}"></div>
                                                <div>
                                                    <label>Course</label>
                                                    <select class="lm-input" name="course">
                                                        <option value="">Course</option>
                                                        @foreach ($attendancePrograms as $program)
                                                            <option value="{{ $program->program_name }}" @selected(old('course') === $program->program_name)>{{ $program->program_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label>Year / Section</label>
                                                    <select class="lm-input" name="year">
                                                        <option value="">Year / Section</option>
                                                        @foreach ($yearOptions as $year)
                                                            <option value="{{ $year }}" @selected(old('year') === $year)>{{ $year }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div><label>Emergency contact name</label><input class="lm-input" name="emergency_person" value="{{ old('emergency_person') }}"></div>
                                                <div><label>Emergency relationship</label><input class="lm-input" name="emergency_relationship" value="{{ old('emergency_relationship') }}"></div>
                                                <div><label>Emergency contact number</label><input class="lm-input" name="emergency_number" value="{{ old('emergency_number') }}"></div>
                                                <div><label>Emergency address</label><input class="lm-input" name="emergency_address" value="{{ old('emergency_address') }}"></div>
                                                <div class="lm-full"><label>Address</label><input class="lm-input" name="address" value="{{ old('address') }}"></div>
                                            </div>

                                            <div class="lm-sub-divider">Photo &amp; signature</div>
                                            <label>Profile Picture</label>
                                            <div class="lm-note">Please upload a clear 1x1 ID picture with a plain background.</div>
                                            <div class="lm-file">
                                                <label>Choose File<input type="file" name="profile_picture" accept="image/*"></label>
                                                <span>No file chosen</span>
                                            </div>
                                            <label class="lm-sig-label">Signature (draw below)</label>
                                            <canvas class="lm-sig-pad" id="lmCanvasAttStudent"></canvas>
                                            <button type="button" class="lm-sig-clear" data-lm-clear-sig="lmCanvasAttStudent:lmSigAttStudent">Clear signature</button>
                                            <div class="lm-submit-wrap">
                                                <button type="submit" class="lm-btn att">Submit Student Registration</button>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="lm-role-panel" data-lm-role-panel="attendance-employee">
                                        <form method="POST" action="{{ route('attendance.pendingEmployee.store') }}" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="modal_view" value="register">
                                            <input type="hidden" name="service" value="attendance">
                                            <input type="hidden" name="registration_type" value="employee">
                                            <input type="hidden" name="employee_signature" id="lmSigAttEmployee">

                                            <div class="lm-section-title att">Employee Information</div>
                                            <div class="lm-grid2">
                                                <div><label>First name <span class="lm-req">*</span></label><input class="lm-input" name="firstname" value="{{ old('firstname') }}" required></div>
                                                <div><label>Last name <span class="lm-req">*</span></label><input class="lm-input" name="lastname" value="{{ old('lastname') }}" required></div>
                                                <div><label>Middle initial</label><input class="lm-input" name="middle_initial" value="{{ old('middle_initial') }}"></div>
                                                <div><label>Employee ID <span class="lm-req">*</span></label><input class="lm-input" name="employee_id" value="{{ old('employee_id') }}" required></div>
                                                <div><label>Employee number</label><input class="lm-input" name="employee_number" value="{{ old('employee_number') }}"></div>
                                                <div><label>Birth date</label><input class="lm-input" type="date" name="birth_date" value="{{ old('birth_date') }}"></div>
                                                <div><label>Department</label><input class="lm-input" name="department" value="{{ old('department') }}"></div>
                                                <div><label>Position</label><input class="lm-input" name="position" value="{{ old('position') }}"></div>
                                                <div><label>Mobile number</label><input class="lm-input" name="mobile_number" value="{{ old('mobile_number') }}"></div>
                                                <div><label>Blood type</label><input class="lm-input" name="blood_type" value="{{ old('blood_type') }}"></div>
                                                <div><label>Emergency contact name</label><input class="lm-input" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}"></div>
                                                <div><label>Emergency contact number</label><input class="lm-input" name="emergency_contact_number" value="{{ old('emergency_contact_number') }}"></div>
                                                <div class="lm-full"><label>Address</label><input class="lm-input" name="address" value="{{ old('address') }}"></div>
                                                <div class="lm-full"><label>Emergency address</label><input class="lm-input" name="emergency_address" value="{{ old('emergency_address') }}"></div>
                                            </div>

                                            <div class="lm-sub-divider">Photo &amp; signature</div>
                                            <label>Formal/Profile Picture</label>
                                            <div class="lm-file">
                                                <label>Choose File<input type="file" name="formal_picture" accept="image/*"></label>
                                                <span>No file chosen</span>
                                            </div>
                                            <label class="lm-sig-label">Signature</label>
                                            <canvas class="lm-sig-pad" id="lmCanvasAttEmployee"></canvas>
                                            <button type="button" class="lm-sig-clear" data-lm-clear-sig="lmCanvasAttEmployee:lmSigAttEmployee">Clear signature</button>
                                            <div class="lm-submit-wrap">
                                                <button type="submit" class="lm-btn att">Submit Employee Registration</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <div class="lm-svc lm-lib-form">
                                    <h3 class="lm-service-title">Library Registration</h3>
                                    <div class="lm-roles">
                                        <button class="lm-role" type="button" data-lm-role="library-student">Student</button>
                                        <button class="lm-role" type="button" data-lm-role="library-employee">Faculty &amp; Staff</button>
                                    </div>

                                    <div class="lm-role-panel" data-lm-role-panel="library-student">
                                        <form method="POST" action="{{ route('library.pending.store') }}" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="modal_view" value="register">
                                            <input type="hidden" name="service" value="library">
                                            <input type="hidden" name="registration_type" value="student">
                                            <input type="hidden" name="student_signature" id="lmSigLibStudent">

                                            <div class="lm-section-title lib">Student Information</div>
                                            <div class="lm-grid2">
                                                <div><label>ID number <span class="lm-req">*</span></label><input class="lm-input" name="id_number" value="{{ old('id_number') }}" required></div>
                                                <div><label>Birthday</label><input class="lm-input" type="date" name="birthday" value="{{ old('birthday') }}"></div>
                                                <div><label>First name <span class="lm-req">*</span></label><input class="lm-input" name="firstname" value="{{ old('firstname') }}" required></div>
                                                <div><label>Last name <span class="lm-req">*</span></label><input class="lm-input" name="lastname" value="{{ old('lastname') }}" required></div>
                                                <div><label>Middle initial</label><input class="lm-input" name="middle_initial" value="{{ old('middle_initial') }}"></div>
                                                <div>
                                                    <label>Course <span class="lm-req">*</span></label>
                                                    <select class="lm-input" name="course" required>
                                                        <option value="">Course</option>
                                                        @foreach ($libraryPrograms as $program)
                                                            <option value="{{ $program->program_code }}" @selected(old('course') === $program->program_code)>{{ $program->program_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label>Year <span class="lm-req">*</span></label>
                                                    <select class="lm-input" name="year" required>
                                                        <option value="">Year</option>
                                                        @foreach ($yearOptions as $year)
                                                            <option value="{{ $year }}" @selected(old('year') === $year)>{{ $year }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div><label>Mobile number</label><input class="lm-input" name="mobile_number" value="{{ old('mobile_number') }}"></div>
                                                <div class="lm-full"><label>Address</label><input class="lm-input" name="address" value="{{ old('address') }}"></div>
                                            </div>

                                            <div class="lm-sub-divider">Emergency contact</div>
                                            <div class="lm-grid2">
                                                <div><label>Contact person</label><input class="lm-input" name="emergency_person" value="{{ old('emergency_person') }}"></div>
                                                <div><label>Relationship</label><input class="lm-input" name="emergency_relationship" value="{{ old('emergency_relationship') }}"></div>
                                                <div><label>Contact number</label><input class="lm-input" name="emergency_number" value="{{ old('emergency_number') }}"></div>
                                                <div><label>Address</label><input class="lm-input" name="emergency_address" value="{{ old('emergency_address') }}"></div>
                                            </div>

                                            <div class="lm-sub-divider">Photo &amp; signature</div>
                                            <label>Profile photo</label>
                                            <div class="lm-file">
                                                <label>Choose File<input type="file" name="profile_picture" accept="image/*"></label>
                                                <span>No file chosen</span>
                                            </div>
                                            <label class="lm-sig-label">Signature</label>
                                            <canvas class="lm-sig-pad" id="lmCanvasLibStudent"></canvas>
                                            <button type="button" class="lm-sig-clear" data-lm-clear-sig="lmCanvasLibStudent:lmSigLibStudent">Clear signature</button>
                                            <div class="lm-submit-wrap">
                                                <button type="submit" class="lm-btn lib">Submit Student Registration</button>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="lm-role-panel" data-lm-role-panel="library-employee">
                                        <form method="POST" action="{{ route('library.pendingEmployee.store') }}" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="modal_view" value="register">
                                            <input type="hidden" name="service" value="library">
                                            <input type="hidden" name="registration_type" value="employee">
                                            <input type="hidden" name="employee_signature" id="lmSigLibFaculty">

                                            <div class="lm-section-title lib">Faculty &amp; Staff Information</div>
                                            <div class="lm-grid2">
                                                <div><label>First name <span class="lm-req">*</span></label><input class="lm-input" name="firstname" value="{{ old('firstname') }}" required></div>
                                                <div><label>Last name <span class="lm-req">*</span></label><input class="lm-input" name="lastname" value="{{ old('lastname') }}" required></div>
                                                <div><label>Middle initial</label><input class="lm-input" name="middle_initial" value="{{ old('middle_initial') }}"></div>
                                                <div><label>Employee ID <span class="lm-req">*</span></label><input class="lm-input" name="employee_id" value="{{ old('employee_id') }}" required></div>
                                                <div><label>Designation <span class="lm-req">*</span></label><input class="lm-input" name="designation" value="{{ old('designation') }}" required></div>
                                                <div>
                                                    <label>Program <span class="lm-req">*</span></label>
                                                    <select class="lm-input" name="program" required>
                                                        <option value="">Program</option>
                                                        @foreach ($libraryPrograms as $program)
                                                            <option value="{{ $program->program_code }}" @selected(old('program') === $program->program_code)>{{ $program->program_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label>Year started <span class="lm-req">*</span></label>
                                                    <select class="lm-input" name="year_start_work" required>
                                                        <option value="">Year</option>
                                                        @foreach ($workStartYears as $year)
                                                            <option value="{{ $year }}" @selected(old('year_start_work') == (string) $year)>{{ $year }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div><label>Birthday</label><input class="lm-input" type="date" name="birth_date" value="{{ old('birth_date') }}"></div>
                                                <div><label>Mobile number</label><input class="lm-input" name="mobile_number" value="{{ old('mobile_number') }}"></div>
                                                <div class="lm-full"><label>Address</label><input class="lm-input" name="address" value="{{ old('address') }}"></div>
                                            </div>

                                            <div class="lm-sub-divider">Emergency contact</div>
                                            <div class="lm-grid2">
                                                <div><label>Contact person</label><input class="lm-input" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}"></div>
                                                <div><label>Relationship</label><input class="lm-input" name="emergency_contact_relationship" value="{{ old('emergency_contact_relationship') }}"></div>
                                                <div><label>Contact number</label><input class="lm-input" name="emergency_contact_number" value="{{ old('emergency_contact_number') }}"></div>
                                                <div><label>Address</label><input class="lm-input" name="emergency_address" value="{{ old('emergency_address') }}"></div>
                                            </div>

                                            <div class="lm-sub-divider">Photo &amp; signature</div>
                                            <label>Formal photo</label>
                                            <div class="lm-file">
                                                <label>Choose File<input type="file" name="formal_picture" accept="image/*"></label>
                                                <span>No file chosen</span>
                                            </div>
                                            <label class="lm-sig-label">Signature</label>
                                            <canvas class="lm-sig-pad" id="lmCanvasLibFaculty"></canvas>
                                            <button type="button" class="lm-sig-clear" data-lm-clear-sig="lmCanvasLibFaculty:lmSigLibFaculty">Clear signature</button>
                                            <div class="lm-submit-wrap">
                                                <button type="submit" class="lm-btn lib">Submit Faculty &amp; Staff Registration</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>
