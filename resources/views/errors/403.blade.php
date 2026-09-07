<!DOCTYPE html>
<html lang="en" data-theme="{{ auth()->user()->theme_preference ?? 'pantas-default' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden | {{ $activeBranding['sidebar_brand_name'] ?? config('app.name', 'Pantas') }}</title>
    <link href="{{ asset('vendor/fontsource/poppins/latin-400.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fontsource/poppins/latin-600.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fontsource/poppins/latin-700.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset(config('branding.css_path')) }}">
    @include('components.branding-overrides')
    <style>
        html,
        body {
            min-height: 100%;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', Tahoma, Geneva, Verdana, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        *,
        *::before,
        *::after {
            box-sizing: inherit;
        }

        .forbidden-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.25rem;
            color: #ffffff;
            background:
                radial-gradient(circle at 18% 18%, color-mix(in srgb, var(--brand-primary) 35%, transparent), transparent 32%),
                radial-gradient(circle at 82% 78%, color-mix(in srgb, var(--brand-accent) 18%, transparent), transparent 30%),
                linear-gradient(
                    145deg,
                    color-mix(in srgb, var(--brand-primary) 55%, #071653),
                    var(--brand-primary) 58%,
                    color-mix(in srgb, var(--brand-primary) 70%, #0b1f62)
                );
        }

        .forbidden-panel {
            width: min(100%, 28rem);
            text-align: center;
        }

        .forbidden-logo {
            width: 4.5rem;
            height: 4.5rem;
            object-fit: contain;
            margin: 0 auto 1rem;
            display: block;
            filter: drop-shadow(0 8px 18px rgba(7, 22, 83, 0.35));
        }

        .forbidden-brand {
            margin: 0 0 0.25rem;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: color-mix(in srgb, #ffffff 82%, var(--brand-accent));
        }

        .forbidden-subtitle {
            margin: 0 0 1.75rem;
            font-size: 0.95rem;
            color: color-mix(in srgb, #ffffff 78%, transparent);
        }

        .forbidden-code {
            margin: 0 0 0.85rem;
            font-size: clamp(1.85rem, 4vw, 2.35rem);
            font-weight: 700;
            letter-spacing: 0.04em;
            line-height: 1.15;
        }

        .forbidden-code span {
            opacity: 0.55;
            font-weight: 400;
            margin: 0 0.55rem;
        }

        .forbidden-message {
            margin: 0 auto 1.75rem;
            max-width: 22rem;
            font-size: 0.98rem;
            line-height: 1.55;
            color: color-mix(in srgb, #ffffff 86%, transparent);
        }

        .forbidden-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: center;
        }

        .forbidden-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.75rem;
            padding: 0.7rem 1.35rem;
            border-radius: 0.65rem;
            border: 1px solid transparent;
            font: inherit;
            font-size: 0.92rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
        }

        .forbidden-btn:hover {
            transform: translateY(-1px);
        }

        .forbidden-btn-primary {
            color: #ffffff;
            background: var(--brand-button-bg, var(--branding-button, var(--brand-primary)));
            box-shadow: 0 10px 24px rgba(7, 22, 83, 0.28);
        }

        .forbidden-btn-primary:hover {
            box-shadow: 0 14px 28px rgba(7, 22, 83, 0.34);
            filter: brightness(1.05);
        }
    </style>
</head>
<body class="forbidden-page">
    <main class="forbidden-panel" role="main">
        <img
            src="{{ $brandingSidebarLogoUrl }}"
            alt="{{ $activeBranding['sidebar_brand_name'] ?? 'Pantas' }} logo"
            class="forbidden-logo"
        >

        <p class="forbidden-brand">{{ $activeBranding['sidebar_brand_name'] ?? 'Pantas' }}</p>
        @if (! empty($activeBranding['sidebar_brand_subtitle']))
            <p class="forbidden-subtitle">{{ $activeBranding['sidebar_brand_subtitle'] }}</p>
        @endif

        <h1 class="forbidden-code">403<span>|</span>FORBIDDEN</h1>
        <p class="forbidden-message">
            You do not have permission to access this page. If you believe this is a mistake, contact a system administrator.
        </p>

        <div class="forbidden-actions">
            @auth
                <a href="{{ route('dashboard') }}" class="forbidden-btn forbidden-btn-primary">
                    Back to dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="forbidden-btn forbidden-btn-primary">
                    Sign in
                </a>
            @endauth
        </div>
    </main>
</body>
</html>
