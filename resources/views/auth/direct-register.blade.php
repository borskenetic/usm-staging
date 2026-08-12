<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Pantas</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('img/pantas-10.png') }}">
    <link rel="stylesheet" href="{{ asset('css/auth-modal.css') }}?v=5">
    <style>
        html,
        body {
            min-height: 100%;
            margin: 0;
        }

        .direct-auth-page {
            min-height: 100vh;
            background:
                radial-gradient(circle at 20% 20%, rgba(37, 99, 235, 0.22), transparent 28%),
                linear-gradient(145deg, #071653, #123c8c 58%, #0b1f62);
        }
    </style>
</head>
<body class="direct-auth-page">
    @include('partials.auth-modal', [
        'forceAuthModalOpen' => true,
        'authModalCloseUrl' => route('landing'),
        'forcedAuthView' => 'register',
        'forcedAuthService' => $initialService ?? 'attendance',
        'forcedAuthType' => $initialType ?? 'student',
    ])

    <script src="{{ asset('js/auth-modal.js') }}?v=4"></script>
</body>
</html>
