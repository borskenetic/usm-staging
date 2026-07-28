@php
    $moduleAccess = app(\App\Services\Auth\ModuleAccessService::class);
    $user = Auth::user();
    $availableModules = $user ? $moduleAccess->availableModules($user) : [];
    $activeModule = session('active_module');
    $canLibrary = $user && $moduleAccess->hasLibraryAccess($user);
    $canLibraryAdmin = $user && $moduleAccess->hasLibraryAdminAccess($user);
    $canAttendance = $user && $moduleAccess->hasAttendanceAccess($user);
    $canAttendanceAdmin = $user && $moduleAccess->hasAttendanceAdminAccess($user);
    $canSuperAdmin = $user && $moduleAccess->isSuperAdmin($user);
    $canDeveloper = $user && $moduleAccess->isDeveloper($user);

    if ($user && (! is_string($activeModule) || ! $moduleAccess->canAccessModule($user, $activeModule))) {
        try {
            $activeModule = $moduleAccess->defaultModule($user);
        } catch (\InvalidArgumentException) {
            $activeModule = null;
        }
    }

    $showSystemNav = $canSuperAdmin && $activeModule === \App\Services\Auth\ModuleAccessService::SUPER_ADMIN;
    $showDeveloperNav = $canDeveloper && $activeModule === \App\Services\Auth\ModuleAccessService::DEVELOPER;
    $showLibraryNav = $canLibrary && $activeModule === \App\Services\Auth\ModuleAccessService::LIBRARY;
    $showAttendanceNav = $canAttendance && $activeModule === \App\Services\Auth\ModuleAccessService::ATTENDANCE;
    $unreadFeedbackCount = $canLibraryAdmin
        ? \App\Models\Feedback::query()->unread()->count()
        : 0;

    $activeModuleLabel = $activeModule ? str_replace('-', ' ', ucfirst($activeModule)) : 'No module';
@endphp

<div class="sidebar-header">
    <a href="{{ route('dashboard') }}" class="sidebar-logo-wrap">
        <span class="sidebar-logo-mark">
            <img src="{{ $brandingSidebarLogoUrl }}" alt="Pantas Logo" class="sidebar-logo-img">
        </span>
        <span class="sidebar-brand-copy">
            <span class="sidebar-app-name">{{ $activeBranding['sidebar_brand_name'] }}</span>
            <span class="sidebar-app-subtitle">{{ $activeBranding['sidebar_brand_subtitle'] }}</span>
        </span>
    </a>
    <div class="sidebar-user-info">
        <span class="sidebar-user-name">{{ $user->name ?? 'User' }}</span>
        <span class="sidebar-role-badge">{{ str_replace('_', ' ', ucfirst($user->role ?? 'staff')) }}</span>
        <span class="sidebar-module-pill">Active: {{ $activeModuleLabel }}</span>
    </div>
</div>

<nav class="sidebar-nav" aria-label="Sidebar navigation">
    @if ($showDeveloperNav)
        <button class="sidebar-group-label open" data-group="developer-overview" aria-expanded="true" aria-controls="sidebar-group-developer-overview">
            <span><i class="bi bi-code-slash sidebar-group-icon"></i>Developer</span>
            <i class="bi bi-chevron-down sidebar-chevron"></i>
        </button>
        <ul class="sidebar-group-items open" id="sidebar-group-developer-overview" role="list">
            <li>
                <a href="{{ route('dashboard.developer') }}" class="sidebar-link {{ request()->routeIs('dashboard.developer') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> Developer Dashboard
                </a>
            </li>
            @if (Route::has('developer.branding.edit'))
                <li>
                    <a href="{{ route('developer.branding.edit') }}" class="sidebar-link {{ request()->routeIs('developer.branding.edit', 'developer.branding.update', 'developer.branding.restore') ? 'active' : '' }}">
                        <i class="bi bi-palette"></i> Branding Settings
                    </a>
                </li>
                <li>
                    <a href="{{ route('developer.branding.activity') }}" class="sidebar-link {{ request()->routeIs('developer.branding.activity') ? 'active' : '' }}">
                        <i class="bi bi-activity"></i> Branding Activity
                    </a>
                </li>
                <li>
                    <a href="{{ route('developer.branding.versions') }}" class="sidebar-link {{ request()->routeIs('developer.branding.versions', 'developer.branding.restore-version') ? 'active' : '' }}">
                        <i class="bi bi-clock-history"></i> Version History
                    </a>
                </li>
                @if (Route::has('developer.login-modal.edit'))
                    <li>
                        <a href="{{ route('developer.login-modal.edit') }}" class="sidebar-link {{ request()->routeIs('developer.login-modal.*') ? 'active' : '' }}">
                            <i class="bi bi-window-stack"></i> Login Modal Settings
                        </a>
                    </li>
                @endif
                @if (Route::has('developer.register-modal.edit'))
                    <li>
                        <a href="{{ route('developer.register-modal.edit') }}" class="sidebar-link {{ request()->routeIs('developer.register-modal.*') ? 'active' : '' }}">
                            <i class="bi bi-person-plus"></i> Register Modal Settings
                        </a>
                    </li>
                @endif
            @else
                <li>
                    <a href="{{ route('dashboard.developer') }}#branding-settings" class="sidebar-link">
                        <i class="bi bi-palette"></i> Branding Settings
                    </a>
                </li>
            @endif
        </ul>
    @endif

    @if ($showSystemNav)
        <button class="sidebar-group-label" data-group="system-overview" aria-expanded="false" aria-controls="sidebar-group-system-overview">
            <span><i class="bi bi-shield-lock sidebar-group-icon"></i>System</span>
            <i class="bi bi-chevron-down sidebar-chevron"></i>
        </button>
        <ul class="sidebar-group-items" id="sidebar-group-system-overview" role="list">
            <li>
                <a href="{{ route('dashboard.super-admin') }}" class="sidebar-link {{ request()->routeIs('dashboard.super-admin') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> Super Admin Dashboard
                </a>
            </li>
        </ul>

        <button class="sidebar-group-label" data-group="system-staff" aria-expanded="false" aria-controls="sidebar-group-system-staff">
            <span><i class="bi bi-person-gear sidebar-group-icon"></i>Staff</span>
            <i class="bi bi-chevron-down sidebar-chevron"></i>
        </button>
        <ul class="sidebar-group-items" id="sidebar-group-system-staff" role="list">
            <li>
                <a href="{{ route('users.index') }}" class="sidebar-link {{ request()->routeIs('users.index', 'users.edit', 'users.update', 'users.destroy') ? 'active' : '' }}">
                    <i class="bi bi-people"></i> Staff Accounts
                </a>
            </li>
            <li>
                <a href="{{ route('users.create') }}" class="sidebar-link {{ request()->routeIs('users.create', 'users.store') ? 'active' : '' }}">
                    <i class="bi bi-person-plus"></i> Create Staff
                </a>
            </li>
        </ul>

        <button class="sidebar-group-label" data-group="system-activity" aria-expanded="false" aria-controls="sidebar-group-system-activity">
            <span><i class="bi bi-activity sidebar-group-icon"></i>Activity</span>
            <i class="bi bi-chevron-down sidebar-chevron"></i>
        </button>
        <ul class="sidebar-group-items" id="sidebar-group-system-activity" role="list">
            <li>
                <a href="{{ route('admin.activities.index') }}" class="sidebar-link {{ request()->routeIs('admin.activities.*') ? 'active' : '' }}">
                    <i class="bi bi-list-check"></i> Admin Activity
                </a>
            </li>
        </ul>
    @endif

    @if ($showLibraryNav)
        <button class="sidebar-group-label" data-group="library-overview" aria-expanded="false" aria-controls="sidebar-group-library-overview">
            <span><i class="bi bi-speedometer2 sidebar-group-icon"></i>Overview</span>
            <i class="bi bi-chevron-down sidebar-chevron"></i>
        </button>
        <ul class="sidebar-group-items" id="sidebar-group-library-overview" role="list">
            <li>
                <a href="{{ route($canLibraryAdmin ? 'dashboard.library-admin' : 'dashboard.library-staff') }}"
                   class="sidebar-link {{ request()->routeIs('dashboard.library-*') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> Library Dashboard
                </a>
            </li>
        </ul>

        <button class="sidebar-group-label" data-group="library-catalog" aria-expanded="false" aria-controls="sidebar-group-library-catalog">
            <span><i class="bi bi-book sidebar-group-icon"></i>Catalog</span>
            <i class="bi bi-chevron-down sidebar-chevron"></i>
        </button>
        <ul class="sidebar-group-items" id="sidebar-group-library-catalog" role="list">
            <li>
                <a href="{{ route('book.index') }}" class="sidebar-link {{ request()->routeIs('book.*', 'books.*') ? 'active' : '' }}">
                    <i class="bi bi-grid"></i> Books
                </a>
            </li>
            <li>
                <a href="{{ route('ebooks.index') }}" class="sidebar-link {{ request()->routeIs('ebooks.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-text"></i> eBooks
                </a>
            </li>
            @if ($canLibraryAdmin)
                <li>
                    <a href="{{ route('catalog.copy.openlibrary.form') }}" class="sidebar-link {{ request()->routeIs('catalog.copy.*') ? 'active' : '' }}">
                        <i class="bi bi-copy"></i> Copy Cataloging
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.catalog_frameworks.index') }}" class="sidebar-link {{ request()->routeIs('admin.catalog_frameworks.*') ? 'active' : '' }}">
                        <i class="bi bi-diagram-3"></i> MARC Frameworks
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.catalog_select_options.index') }}" class="sidebar-link {{ request()->routeIs('admin.catalog_select_options.*') ? 'active' : '' }}">
                        <i class="bi bi-list-ul"></i> Catalog Options
                    </a>
                </li>
            @endif
        </ul>

        @if ($canLibraryAdmin)
            <button class="sidebar-group-label" data-group="library-circulation" aria-expanded="false" aria-controls="sidebar-group-library-circulation">
                <span><i class="bi bi-arrow-left-right sidebar-group-icon"></i>Circulation</span>
                <i class="bi bi-chevron-down sidebar-chevron"></i>
            </button>
            <ul class="sidebar-group-items" id="sidebar-group-library-circulation" role="list">
                <li>
                    <a href="{{ route('logs.index') }}" class="sidebar-link {{ request()->routeIs('logs.*') ? 'active' : '' }}">
                        <i class="bi bi-journal-text"></i> Circulation Logs
                    </a>
                </li>
                <li>
                    <a href="{{ route('fines.outstanding') }}" class="sidebar-link {{ request()->routeIs('fines.*') ? 'active' : '' }}">
                        <i class="bi bi-exclamation-circle"></i> Fines
                    </a>
                </li>
            </ul>

            <button class="sidebar-group-label" data-group="library-patrons" aria-expanded="false" aria-controls="sidebar-group-library-patrons">
                <span><i class="bi bi-people sidebar-group-icon"></i>Patrons</span>
                <i class="bi bi-chevron-down sidebar-chevron"></i>
            </button>
            <ul class="sidebar-group-items" id="sidebar-group-library-patrons" role="list">
                <li>
                    <a href="{{ route('students.index') }}" class="sidebar-link {{ request()->routeIs('students.*') && ! request()->routeIs('students.pending.requests') ? 'active' : '' }}">
                        <i class="bi bi-person-badge"></i> Library Students
                    </a>
                </li>
                <li>
                    <a href="{{ route('employees.index') }}" class="sidebar-link {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                        <i class="bi bi-person-workspace"></i> Library Employees
                    </a>
                </li>
                <li>
                    <a href="{{ route('pending.index', ['tab' => 'students']) }}" class="sidebar-link {{ request()->routeIs('pending.*', 'students.pending') ? 'active' : '' }}">
                        <i class="bi bi-person-plus"></i> Pending Patrons
                    </a>
                </li>
                <li>
                    <a href="{{ route('students.pending.requests') }}" class="sidebar-link {{ request()->routeIs('students.pending.requests') ? 'active' : '' }}">
                        <i class="bi bi-pencil-square"></i> Profile Requests
                    </a>
                </li>
            </ul>
        @else
            <button class="sidebar-group-label" data-group="library-services" aria-expanded="false" aria-controls="sidebar-group-library-services">
                <span><i class="bi bi-person-check sidebar-group-icon"></i>Patron Services</span>
                <i class="bi bi-chevron-down sidebar-chevron"></i>
            </button>
            <ul class="sidebar-group-items" id="sidebar-group-library-services" role="list">
                <li>
                    <a href="{{ route('landing') }}" class="sidebar-link {{ request()->routeIs('landing') ? 'active' : '' }}">
                        <i class="bi bi-search"></i> OPAC
                    </a>
                </li>
                <li>
                    <a href="{{ route('kiosk.scan') }}" class="sidebar-link {{ request()->routeIs('kiosk.scan') ? 'active' : '' }}">
                        <i class="bi bi-qr-code-scan"></i> Kiosk Lookup
                    </a>
                </li>
            </ul>
        @endif

        <button class="sidebar-group-label" data-group="library-rooms" aria-expanded="false" aria-controls="sidebar-group-library-rooms">
            <span><i class="bi bi-building sidebar-group-icon"></i>Rooms</span>
            <i class="bi bi-chevron-down sidebar-chevron"></i>
        </button>
        <ul class="sidebar-group-items" id="sidebar-group-library-rooms" role="list">
            @if ($canLibraryAdmin)
                <li>
                    <a href="{{ route('rooms.index') }}" class="sidebar-link {{ request()->routeIs('rooms.index', 'rooms.create', 'rooms.edit') ? 'active' : '' }}">
                        <i class="bi bi-building"></i> Rooms
                    </a>
                </li>
                <li>
                    <a href="{{ route('rooms.pending') }}" class="sidebar-link {{ request()->routeIs('rooms.pending') ? 'active' : '' }}">
                        <i class="bi bi-hourglass-split"></i> Pending Room Reservations
                    </a>
                </li>
                <li>
                    <a href="{{ route('rooms.logs') }}" class="sidebar-link {{ request()->routeIs('rooms.logs') ? 'active' : '' }}">
                        <i class="bi bi-journal-text"></i> Room Logs
                    </a>
                </li>
            @else
                <li>
                    <a href="{{ route('rooms.schedule') }}" class="sidebar-link {{ request()->routeIs('rooms.schedule') ? 'active' : '' }}">
                        <i class="bi bi-calendar-week"></i> Room Schedule
                    </a>
                </li>
                <li>
                    <a href="{{ route('rooms.book') }}" class="sidebar-link {{ request()->routeIs('rooms.book') ? 'active' : '' }}">
                        <i class="bi bi-calendar-plus"></i> Room Booking
                    </a>
                </li>
            @endif
        </ul>

        <button class="sidebar-group-label" data-group="library-attendance" aria-expanded="false" aria-controls="sidebar-group-library-attendance">
            <span><i class="bi bi-door-open sidebar-group-icon"></i>Library Attendance</span>
            <i class="bi bi-chevron-down sidebar-chevron"></i>
        </button>
        <ul class="sidebar-group-items" id="sidebar-group-library-attendance" role="list">
            <li>
                <a href="{{ route('library.attendance.scanner') }}" class="sidebar-link {{ request()->routeIs('library.attendance.scanner') ? 'active' : '' }}">
                    <i class="bi bi-upc-scan"></i> Library Scanner
                </a>
            </li>
            @if ($canLibraryAdmin)
                <li>
                    <a href="{{ route('library.attendance.logs') }}" class="sidebar-link {{ request()->routeIs('library.attendance.logs') ? 'active' : '' }}">
                        <i class="bi bi-list-check"></i> Visit Logs
                    </a>
                </li>
                <li>
                    <a href="{{ route('library.attendance.reports') }}" class="sidebar-link {{ request()->routeIs('library.attendance.reports') ? 'active' : '' }}">
                        <i class="bi bi-bar-chart"></i> Visit Reports
                    </a>
                </li>
                <li>
                    <a href="{{ route('library.attendance.feedback.settings') }}" class="sidebar-link {{ request()->routeIs('library.attendance.feedback.settings') ? 'active' : '' }}">
                        <i class="bi bi-gear"></i> Feedback Settings
                    </a>
                </li>
            @endif
        </ul>

        @if ($canLibraryAdmin)
            <button class="sidebar-group-label" data-group="library-utilities" aria-expanded="false" aria-controls="sidebar-group-library-utilities">
                <span><i class="bi bi-tools sidebar-group-icon"></i>Utilities</span>
                <i class="bi bi-chevron-down sidebar-chevron"></i>
            </button>
            <ul class="sidebar-group-items" id="sidebar-group-library-utilities" role="list">
                <li>
                    <a href="{{ route('files.index') }}" class="sidebar-link {{ request()->routeIs('files.*') ? 'active' : '' }}">
                        <i class="bi bi-folder2-open"></i> Repository
                    </a>
                </li>
                <li>
                    <a href="{{ route('sms.page') }}" class="sidebar-link {{ request()->routeIs('sms.*', 'sms.page') ? 'active' : '' }}">
                        <i class="bi bi-chat-dots"></i> SMS
                    </a>
                </li>
                <li>
                    <a href="{{ route('feedback.index') }}" class="sidebar-link {{ request()->routeIs('feedback.index') ? 'active' : '' }}">
                        <i class="bi bi-star"></i> Library Feedback
                        @if ($unreadFeedbackCount > 0)
                            <span class="badge bg-danger ms-1">{{ $unreadFeedbackCount }}</span>
                        @endif
                    </a>
                </li>
                <li>
                    <a href="{{ route('landing') }}" class="sidebar-link {{ request()->routeIs('landing') ? 'active' : '' }}">
                        <i class="bi bi-search"></i> OPAC
                    </a>
                </li>
            </ul>
        @endif
    @endif

    @if ($showAttendanceNav)
        <button class="sidebar-group-label" data-group="attendance-overview" aria-expanded="false" aria-controls="sidebar-group-attendance-overview">
            <span><i class="bi bi-speedometer2 sidebar-group-icon"></i>Overview</span>
            <i class="bi bi-chevron-down sidebar-chevron"></i>
        </button>
        <ul class="sidebar-group-items" id="sidebar-group-attendance-overview" role="list">
            <li>
                <a href="{{ route($canAttendanceAdmin ? 'dashboard.attendance-admin' : 'dashboard.attendance-staff') }}"
                   class="sidebar-link {{ request()->routeIs('dashboard.attendance-*') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> Attendance Dashboard
                </a>
            </li>
        </ul>

        <button class="sidebar-group-label" data-group="attendance-scanner" aria-expanded="false" aria-controls="sidebar-group-attendance-scanner">
            <span><i class="bi bi-upc-scan sidebar-group-icon"></i>{{ $canAttendanceAdmin ? 'Scanner' : 'Daily Attendance' }}</span>
            <i class="bi bi-chevron-down sidebar-chevron"></i>
        </button>
        <ul class="sidebar-group-items" id="sidebar-group-attendance-scanner" role="list">
            <li>
                <a href="{{ route('attendance.scan') }}" class="sidebar-link {{ request()->routeIs('attendance.scan') ? 'active' : '' }}">
                    <i class="bi bi-upc-scan"></i> Attendance Scanner
                </a>
            </li>
            <li>
                <a href="{{ route('attendance.changeVideo') }}" class="sidebar-link {{ request()->routeIs('attendance.changeVideo') ? 'active' : '' }}">
                    <i class="bi bi-camera-video"></i> Change Video
                </a>
            </li>
            <li>
                <a href="{{ route('attendance.feedback.settings') }}" class="sidebar-link {{ request()->routeIs('attendance.feedback.settings') ? 'active' : '' }}">
                    <i class="bi bi-gear"></i> Logout Feedback Settings
                </a>
            </li>
        </ul>

        @if ($canAttendanceAdmin)
            <button class="sidebar-group-label" data-group="attendance-patrons" aria-expanded="false" aria-controls="sidebar-group-attendance-patrons">
                <span><i class="bi bi-people sidebar-group-icon"></i>Patrons</span>
                <i class="bi bi-chevron-down sidebar-chevron"></i>
            </button>
            <ul class="sidebar-group-items" id="sidebar-group-attendance-patrons" role="list">
                <li>
                    <a href="{{ route('attendance.pending.index') }}" class="sidebar-link {{ request()->routeIs('attendance.pending.index', 'attendance.patrons.*') ? 'active' : '' }}">
                        <i class="bi bi-people"></i> Attendance Patrons
                    </a>
                </li>
                <li>
                    <a href="{{ route('attendance.pending.students') }}" class="sidebar-link {{ request()->routeIs('attendance.pending.students') ? 'active' : '' }}">
                        <i class="bi bi-person-plus"></i> Pending Attendance Registrations - Students
                    </a>
                </li>
                <li>
                    <a href="{{ route('attendance.pending.employees') }}" class="sidebar-link {{ request()->routeIs('attendance.pending.employees') ? 'active' : '' }}">
                        <i class="bi bi-person-workspace"></i> Pending Attendance Registrations - Employees
                    </a>
                </li>
            </ul>

            <button class="sidebar-group-label" data-group="attendance-reports" aria-expanded="false" aria-controls="sidebar-group-attendance-reports">
                <span><i class="bi bi-bar-chart sidebar-group-icon"></i>Logs & Reports</span>
                <i class="bi bi-chevron-down sidebar-chevron"></i>
            </button>
            <ul class="sidebar-group-items" id="sidebar-group-attendance-reports" role="list">
                <li>
                    <a href="{{ route('attendance_logs.index') }}" class="sidebar-link {{ request()->routeIs('attendance_logs.index') ? 'active' : '' }}">
                        <i class="bi bi-list-check"></i> Attendance Logs
                    </a>
                </li>
                <li>
                    <a href="{{ route('attendance_logs.absences') }}" class="sidebar-link {{ request()->routeIs('attendance_logs.absences*') ? 'active' : '' }}">
                        <i class="bi bi-person-x"></i> Absences
                    </a>
                </li>
                <li>
                    <a href="{{ route('attendance_logs.reports.hub') }}" class="sidebar-link {{ request()->routeIs('attendance_logs.reports.hub') ? 'active' : '' }}">
                        <i class="bi bi-bar-chart"></i> Reports Hub
                    </a>
                </li>
                <li>
                    <a href="{{ route('attendance_logs.reports.dashboard') }}" class="sidebar-link {{ request()->routeIs('attendance_logs.reports.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-graph-up"></i> Report Dashboard
                    </a>
                </li>
                <li>
                    <a href="{{ route('attendance_logs.reports.export') }}" class="sidebar-link {{ request()->routeIs('attendance_logs.reports.export') ? 'active' : '' }}">
                        <i class="bi bi-download"></i> Export Report
                    </a>
                </li>
            </ul>

            <button class="sidebar-group-label" data-group="attendance-feedback" aria-expanded="false" aria-controls="sidebar-group-attendance-feedback">
                <span><i class="bi bi-chat-square-text sidebar-group-icon"></i>Feedback</span>
                <i class="bi bi-chevron-down sidebar-chevron"></i>
            </button>
            <ul class="sidebar-group-items" id="sidebar-group-attendance-feedback" role="list">
                <li>
                    <a href="{{ route('admin.attendance.feedbacks') }}" class="sidebar-link {{ request()->routeIs('admin.attendance.feedbacks') ? 'active' : '' }}">
                        <i class="bi bi-chat-square-text"></i> Feedback Responses
                    </a>
                </li>
            </ul>
        @endif
    @endif
</nav>

<div class="sidebar-footer-actions">
    @if (Route::has('profile.edit'))
        <a href="{{ route('profile.edit') }}" class="btn-profile">
            <i class="bi bi-person"></i> <span>Profile</span>
        </a>
    @endif
    <form method="POST" action="{{ route('logout') }}" class="flex-1 m-0">
        @csrf
        <button type="submit" class="btn-logout w-100">
            <i class="bi bi-box-arrow-right"></i> <span>Logout</span>
        </button>
    </form>
</div>
