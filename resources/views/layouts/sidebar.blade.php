<!DOCTYPE html>
<html lang="en" data-theme="{{ auth()->user()->theme_preference ?? 'pantas-default' }}" data-saved-theme="{{ auth()->user()->theme_preference ?? 'pantas-default' }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'Pantas Library')</title>

    {{-- Bootstrap 5 CSS --}}
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" />

    {{-- Branding tokens --}}
    <link rel="stylesheet" href="{{ asset(config('branding.css_path')) }}">

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.css') }}">

    {{-- Vite: Tailwind v4 + Flowbite compiled CSS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Sidebar custom styles --}}
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">

    {{-- Theme system --}}
    <link rel="stylesheet" href="{{ asset('css/themes.css') }}">
    @include('components.branding-overrides')

    {{-- Page content dark mode overrides for tables, forms, cards, etc. --}}
    <link rel="stylesheet" href="{{ asset('css/page-content.css') }}">

    {{-- Role dashboard cards/grids (static so they do not depend on Vite) --}}
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=1">

    @stack('styles')
    @yield('styles')
</head>

@php
    $moduleAccess = app(\App\Services\Auth\ModuleAccessService::class);
    $shellUser = Auth::user();
    $shellModules = $shellUser ? $moduleAccess->availableModules($shellUser) : [];
    $shellActiveModule = session('active_module');

    if ($shellUser && (! is_string($shellActiveModule) || ! $moduleAccess->canAccessModule($shellUser, $shellActiveModule))) {
        try {
            $shellActiveModule = $moduleAccess->defaultModule($shellUser);
        } catch (\InvalidArgumentException) {
            $shellActiveModule = null;
        }
    }

    $shellModuleLabel = $shellActiveModule ? str_replace('-', ' ', ucfirst($shellActiveModule)) : 'No module';
    $shellTitle = trim($__env->yieldContent('title', 'Dashboard'));
    $shellBreadcrumbs = collect(['Dashboard', $shellTitle])->unique()->values();
    $shellInitials = collect(explode(' ', trim($shellUser->name ?? 'User')))
        ->filter()
        ->take(2)
        ->map(fn ($part) => substr($part, 0, 1))
        ->implode('');
    $shellNotifications = $shellUser
        ? app(\App\Services\TopbarNotificationService::class)->forUser($shellUser)
        : collect();

    $commandLinks = $shellUser && $moduleAccess->isDeveloper($shellUser)
        ? collect([
            ['label' => 'Developer Dashboard', 'route' => 'dashboard.developer', 'icon' => 'bi-code-slash', 'group' => 'Developer'],
            ['label' => 'Branding Settings', 'route' => 'developer.branding.edit', 'icon' => 'bi-palette', 'group' => 'Developer'],
            ['label' => 'Branding Activity', 'route' => 'developer.branding.activity', 'icon' => 'bi-activity', 'group' => 'Developer'],
            ['label' => 'Version History', 'route' => 'developer.branding.versions', 'icon' => 'bi-clock-history', 'group' => 'Developer'],
            ['label' => 'Login Modal Settings', 'route' => 'developer.login-modal.edit', 'icon' => 'bi-window-stack', 'group' => 'Developer'],
            ['label' => 'Register Modal Settings', 'route' => 'developer.register-modal.edit', 'icon' => 'bi-person-plus', 'group' => 'Developer'],
        ])
        : collect([
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'bi-speedometer2', 'group' => 'Pages'],
        ['label' => 'Super Admin Dashboard', 'route' => 'dashboard.super-admin', 'icon' => 'bi-shield-lock', 'group' => 'Dashboards'],
        ['label' => 'Library Dashboard', 'route' => $shellUser && $moduleAccess->hasLibraryAdminAccess($shellUser) ? 'dashboard.library-admin' : 'dashboard.library-staff', 'icon' => 'bi-book', 'group' => 'Dashboards'],
        ['label' => 'Attendance Dashboard', 'route' => $shellUser && $moduleAccess->hasAttendanceAdminAccess($shellUser) ? 'dashboard.attendance-admin' : 'dashboard.attendance-staff', 'icon' => 'bi-clock-history', 'group' => 'Dashboards'],
        ['label' => 'Staff Accounts', 'route' => 'users.index', 'icon' => 'bi-people', 'group' => 'Staff'],
        ['label' => 'Create Staff', 'route' => 'users.create', 'icon' => 'bi-person-plus', 'group' => 'Quick actions'],
        ['label' => 'Books', 'route' => 'book.index', 'icon' => 'bi-grid', 'group' => 'Library'],
        ['label' => 'Library Scanner', 'route' => 'attendance.scan', 'icon' => 'bi-upc-scan', 'group' => 'Library'],
        ['label' => 'Attendance Scanner', 'route' => 'attendance.scan', 'icon' => 'bi-upc-scan', 'group' => 'Attendance'],
        ['label' => 'Attendance Logs', 'route' => 'attendance_logs.index', 'icon' => 'bi-list-check', 'group' => 'Attendance'],
        ['label' => 'Reports Hub', 'route' => 'attendance_logs.reports.hub', 'icon' => 'bi-bar-chart', 'group' => 'Reports'],
        ['label' => 'Activity log', 'route' => 'library.attendance.activities', 'icon' => 'bi-activity', 'group' => 'Utilities'],
        ['label' => 'Feedback Settings', 'route' => 'attendance.feedback.settings', 'icon' => 'bi-gear', 'group' => 'Settings'],
        ])->filter(fn ($item) => Route::has($item['route']))->values();
@endphp

<body class="sidebar-layout sidebar-hydrating">

    {{-- ========================================================
         MOBILE TOP BAR (visible only on small screens)
    ========================================================= --}}
    <header class="sidebar-topbar d-flex align-items-center d-md-none px-3">
        <button id="sidebarToggle" class="sidebar-hamburger me-3" aria-label="Open menu" aria-expanded="false" aria-controls="sidebar">
            <i class="bi bi-list fs-4"></i>
        </button>
        <img src="{{ $brandingSidebarLogoUrl }}" alt="Pantas Logo" class="sidebar-topbar-logo me-2">
        <span class="sidebar-topbar-title fw-semibold">@yield('title', 'Pantas Library')</span>
    </header>

    {{-- Mobile overlay backdrop --}}
    <div id="sidebarOverlay" class="sidebar-overlay" aria-hidden="true"></div>

    {{-- ========================================================
         SIDEBAR WRAPPER
    ========================================================= --}}
    <div class="sidebar-wrapper">

        {{-- LEFT SIDEBAR --}}
        <aside id="sidebar" class="sidebar" role="navigation" aria-label="Dashboard navigation">
            @include('components.sidebar-nav')
        </aside>

        <script>
            (function () {
                const groupsKey = 'pantas_sidebar_open_groups';
                const scrollKey = 'pantas_sidebar_scroll_top';

                function readJson(key, fallback) {
                    try {
                        return JSON.parse(localStorage.getItem(key) || JSON.stringify(fallback));
                    } catch (_) {
                        return fallback;
                    }
                }

                function readNumber(key, fallback) {
                    try {
                        const value = Number(localStorage.getItem(key) || fallback);
                        return Number.isFinite(value) ? value : fallback;
                    } catch (_) {
                        return fallback;
                    }
                }

                const savedOpen = readJson(groupsKey, []);

                document.querySelectorAll('.sidebar-group-label').forEach(function (label) {
                    const groupId = label.dataset.group;
                    const items = document.getElementById('sidebar-group-' + groupId);
                    if (!items) return;

                    const hasActive = items.querySelector('.sidebar-link.active') !== null;
                    if (hasActive || savedOpen.includes(groupId)) {
                        label.classList.add('open');
                        items.classList.add('open');
                        label.setAttribute('aria-expanded', 'true');
                    }
                });

                requestAnimationFrame(function () {
                    const scroller = document.querySelector('#sidebar .sidebar-nav') || document.getElementById('sidebar');
                    if (scroller) {
                        scroller.scrollTop = readNumber(scrollKey, 0);
                    }
                    document.body.classList.remove('sidebar-hydrating');
                });
            }());
        </script>

        {{-- MAIN CONTENT AREA --}}
        <main class="sidebar-content">
            <header class="app-topbar">
                <button type="button" id="desktopSidebarToggle" class="topbar-icon-btn d-none d-md-inline-flex" aria-label="Collapse sidebar" aria-expanded="true" aria-controls="sidebar">
                    <i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i>
                </button>

                <div class="app-breadcrumbs" aria-label="Breadcrumb">
                    @foreach ($shellBreadcrumbs as $breadcrumb)
                        <span>{{ $breadcrumb }}</span>
                        @if (! $loop->last)
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        @endif
                    @endforeach
                </div>

                <button type="button" class="topbar-search" data-command-open aria-label="Open global search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <span>Search staff, books, attendance...</span>
                    <kbd>Ctrl K</kbd>
                </button>

                <div class="topbar-actions">
                    @if (count($shellModules) > 1)
                        <div class="dropdown">
                            <button class="topbar-module-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span>Module</span>
                                <strong>{{ $shellModuleLabel }}</strong>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end topbar-dropdown">
                                @foreach ($shellModules as $availableModule)
                                    <form method="POST" action="{{ route('module.switch') }}" class="m-0">
                                        @csrf
                                        <input type="hidden" name="module" value="{{ $availableModule }}">
                                        <button type="submit" class="dropdown-item topbar-dropdown-item {{ $shellActiveModule === $availableModule ? 'active' : '' }}">
                                            <i class="bi {{ $shellActiveModule === $availableModule ? 'bi-check-circle-fill' : 'bi-circle' }}" aria-hidden="true"></i>
                                            {{ str_replace('-', ' ', ucfirst($availableModule)) }}
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="dropdown">
                        <button class="topbar-icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                            <i class="bi bi-bell" aria-hidden="true"></i>
                            @if ($shellNotifications->isNotEmpty())
                                <span class="topbar-badge">{{ $shellNotifications->count() }}</span>
                            @endif
                        </button>
                        <div class="dropdown-menu dropdown-menu-end topbar-dropdown topbar-notifications">
                            <div class="topbar-dropdown-heading">Notifications</div>
                            @forelse ($shellNotifications as $notification)
                                <a class="topbar-notification-item text-decoration-none" href="{{ $notification['url'] }}">
                                    <span class="notification-dot"></span>
                                    <div>
                                        <strong>{{ $notification['title'] }}</strong>
                                        <small>{{ $notification['message'] }}</small>
                                    </div>
                                </a>
                            @empty
                                <div class="topbar-notification-item">
                                    <div>
                                        <strong>You're all caught up</strong>
                                        <small>There are no items that need your review.</small>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="dropdown">
                        <button class="topbar-user-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="topbar-avatar">{{ $shellInitials ?: 'U' }}</span>
                            <span class="topbar-user-copy">
                                <strong>{{ $shellUser->name ?? 'User' }}</strong>
                                <span>{{ str_replace('_', ' ', ucfirst($shellUser->role ?? 'staff')) }}</span>
                            </span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end topbar-dropdown">
                            @if (Route::has('profile.edit'))
                                <a class="dropdown-item topbar-dropdown-item" href="{{ route('profile.edit') }}">
                                    <i class="bi bi-person" aria-hidden="true"></i> Profile
                                </a>
                            @endif
                            <button class="dropdown-item topbar-dropdown-item" type="button" data-command-open>
                                <i class="bi bi-command" aria-hidden="true"></i> Command palette
                            </button>
                            <button class="dropdown-item topbar-dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#themePickerModal">
                                <i class="bi bi-palette" aria-hidden="true"></i> Theme
                            </button>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button class="dropdown-item topbar-dropdown-item text-danger" type="submit">
                                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i> Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page-level header / breadcrumb (optional) --}}
            @hasSection('header')
                <div class="sidebar-page-header">
                    @yield('header')
                </div>
            @endif

            <div class="sidebar-page-body">
                @yield('content')
            </div>

            {{-- Footer — sidebar layout provides default; pages can override via @section('footer') --}}
            @hasSection('footer')
                @yield('footer')
            @endif
        </main>

    </div>{{-- /.sidebar-wrapper --}}

    <div class="command-palette-backdrop" id="commandPalette" aria-hidden="true">
        <div class="command-palette" role="dialog" aria-modal="true" aria-labelledby="commandPaletteTitle">
            <div class="command-search-row">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="commandSearchInput" placeholder="Search pages, staff, reports, settings..." autocomplete="off">
                <kbd>Esc</kbd>
            </div>
            <div class="command-results" id="commandResults">
                <div class="command-section-title" id="commandPaletteTitle">Quick navigation</div>
                @foreach ($commandLinks as $command)
                    <a href="{{ route($command['route']) }}"
                       class="command-result"
                       data-command-item
                       data-search="{{ strtolower($command['label'].' '.$command['group']) }}">
                        <span class="command-icon"><i class="bi {{ $command['icon'] }}" aria-hidden="true"></i></span>
                        <span>
                            <strong>{{ $command['label'] }}</strong>
                            <small>{{ $command['group'] }}</small>
                        </span>
                        <i class="bi bi-arrow-return-left ms-auto" aria-hidden="true"></i>
                    </a>
                @endforeach
                <div class="command-empty" id="commandEmpty">No matching command found.</div>
            </div>
        </div>
    </div>

    {{-- Bootstrap 5 JS (needed for any Bootstrap components on content pages) --}}
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    {{-- jQuery (available globally for existing page scripts) --}}
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>

    {{-- SweetAlert2 (used for shared confirmation dialogs) --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logoutForms = document.querySelectorAll('form[action*="logout"]');

            logoutForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    if (typeof Swal === 'undefined') {
                        if (window.confirm('Are you sure you want to log out?')) {
                            form.submit();
                        }

                        return;
                    }

                    Swal.fire({
                        title: 'Are you sure you want to log out?',
                        text: 'You will need to sign in again to continue.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, logout',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>

    {{-- Sidebar behaviour: collapse toggle, localStorage state, mobile overlay --}}
    <script src="{{ asset('js/sidebar.js') }}"></script>

    {{-- Theme Picker Modal --}}
    @include('components.theme-picker')

    @stack('scripts')
    @yield('scripts')

</body>
</html>
