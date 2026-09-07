<style id="pantas-branding-overrides">
    :root:not([data-theme]),
    [data-theme="pantas-default"] {
        --brand-primary: {{ $activeBranding['primary_color'] }};
        --brand-accent: {{ $activeBranding['accent_color'] }};
        --brand-button-bg: {{ $activeBranding['button_color'] }};
        --brand-nav-link: {{ $activeBranding['primary_color'] }};
        --brand-nav-link-active: {{ $activeBranding['sidebar_text_color'] }};
        --shell-primary: {{ $activeBranding['primary_color'] }};
        --shell-primary-dark: color-mix(in srgb, {{ $activeBranding['primary_color'] }} 82%, #000000);
        --shell-primary-soft: color-mix(in srgb, {{ $activeBranding['primary_color'] }} 12%, #ffffff);
        --shell-action: {{ $activeBranding['sidebar_active_color'] }};
        --shell-chart-palette: {{ $activeBranding['primary_color'] }}, {{ $activeBranding['secondary_color'] }}, {{ $activeBranding['accent_color'] }}, #6D28D9, #B91C1C, #047857;
        --shell-chart-bg: color-mix(in srgb, {{ $activeBranding['primary_color'] }} 12%, transparent);
        --branding-sidebar-background: {{ $activeBranding['sidebar_background_color'] }};
        --branding-sidebar-text: {{ $activeBranding['sidebar_text_color'] }};
        --branding-sidebar-brand-text: {{ $activeBranding['sidebar_brand_text_color'] }};
        --branding-sidebar-active: {{ $activeBranding['sidebar_active_color'] }};
        --branding-sidebar-hover-background: {{ $activeBranding['sidebar_hover_background_color'] }};
        --branding-sidebar-hover-text: {{ $activeBranding['sidebar_hover_text_color'] }};
        --branding-button: {{ $activeBranding['button_color'] }};
        --branding-sidebar-footer-background: {{ $activeBranding['sidebar_footer_background_color'] }};
        --branding-table-header: {{ $activeBranding['table_header_color'] }};
        --branding-table-header-text: {{ $activeBranding['table_header_text_color'] }};
        --branding-table-border: {{ $activeBranding['table_border_color'] }};
        --branding-table-hover: {{ $activeBranding['table_hover_color'] }};
    }

    :root:not([data-theme]) #sidebar,
    [data-theme="pantas-default"] #sidebar {
        background: var(--branding-sidebar-background);
        color: var(--branding-sidebar-text);
    }

    :root:not([data-theme]) #sidebar .sidebar-app-name,
    :root:not([data-theme]) #sidebar .sidebar-app-subtitle,
    [data-theme="pantas-default"] #sidebar .sidebar-app-name,
    [data-theme="pantas-default"] #sidebar .sidebar-app-subtitle {
        color: var(--branding-sidebar-brand-text);
    }

    :root:not([data-theme]) #sidebar .sidebar-link.active,
    [data-theme="pantas-default"] #sidebar .sidebar-link.active {
        background: var(--branding-sidebar-active);
        color: #FFFFFF;
    }

    :root:not([data-theme]) #sidebar .sidebar-link:not(.active):hover,
    :root:not([data-theme]) #sidebar .sidebar-direct-link:not(.active):hover,
    [data-theme="pantas-default"] #sidebar .sidebar-link:not(.active):hover,
    [data-theme="pantas-default"] #sidebar .sidebar-direct-link:not(.active):hover {
        background: var(--branding-sidebar-hover-background);
        color: var(--branding-sidebar-hover-text);
        transform: translateX(2px);
    }

    :root:not([data-theme]) .btn-primary,
    [data-theme="pantas-default"] .btn-primary {
        --bs-btn-bg: var(--branding-button);
        --bs-btn-border-color: var(--branding-button);
        --bs-btn-hover-bg: var(--shell-primary-dark);
        --bs-btn-hover-border-color: var(--shell-primary-dark);
        --bs-btn-active-bg: var(--shell-primary-dark);
        --bs-btn-active-border-color: var(--shell-primary-dark);
        border-color: var(--branding-button);
        background-color: var(--branding-button);
    }

    :root:not([data-theme]) .btn-primary:hover,
    :root:not([data-theme]) .btn-primary:focus,
    :root:not([data-theme]) .btn-primary:active,
    [data-theme="pantas-default"] .btn-primary:hover,
    [data-theme="pantas-default"] .btn-primary:focus,
    [data-theme="pantas-default"] .btn-primary:active {
        border-color: var(--shell-primary-dark) !important;
        background-color: var(--shell-primary-dark) !important;
    }

    :root:not([data-theme]) .btn-outline-primary,
    [data-theme="pantas-default"] .btn-outline-primary {
        --bs-btn-color: var(--branding-button);
        --bs-btn-border-color: var(--branding-button);
        --bs-btn-hover-bg: var(--branding-button);
        --bs-btn-hover-border-color: var(--branding-button);
        --bs-btn-active-bg: var(--branding-button);
        --bs-btn-active-border-color: var(--branding-button);
        color: var(--branding-button);
        border-color: var(--branding-button);
    }

    :root:not([data-theme]) #sidebar .sidebar-footer-actions,
    [data-theme="pantas-default"] #sidebar .sidebar-footer-actions {
        background: var(--branding-sidebar-footer-background);
    }

    :root:not([data-theme]) .sidebar-page-body .table,
    [data-theme="pantas-default"] .sidebar-page-body .table {
        --bs-table-border-color: var(--branding-table-border);
        --bs-table-hover-bg: var(--branding-table-hover);
        border-color: var(--branding-table-border);
    }

    :root:not([data-theme]) .sidebar-page-body .table thead th,
    [data-theme="pantas-default"] .sidebar-page-body .table thead th {
        border-color: var(--branding-table-border);
        background: var(--branding-table-header);
        color: var(--branding-table-header-text);
    }

    :root:not([data-theme]) .sidebar-page-body .table tbody tr,
    [data-theme="pantas-default"] .sidebar-page-body .table tbody tr {
        border-color: var(--branding-table-border);
    }

    :root:not([data-theme]) .sidebar-page-body .table tbody tr:hover,
    [data-theme="pantas-default"] .sidebar-page-body .table tbody tr:hover {
        background: var(--branding-table-hover);
    }
</style>
