<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Library Attendance Scanner</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset(config('branding.css_path')) }}">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="mx-auto" style="max-width: 720px;">
            <h1 class="h3 mb-2">Library Attendance Scanner</h1>
            <p class="text-muted mb-4">Scan a registered library patron QR code or ID number.</p>

            <div class="card">
                <div class="card-body">
                    <form id="scan-form" class="d-flex gap-2">
                        @csrf
                        <input class="form-control form-control-lg" name="qrcode" autocomplete="off" autofocus placeholder="Scan code">
                        <button class="btn btn-primary" type="submit">Scan</button>
                    </form>
                    <div id="scan-result" class="alert mt-3 d-none" role="status"></div>
                </div>
            </div>
        </div>
    </main>

    <script>
        (() => {
            const form = document.getElementById('scan-form');
            const result = document.getElementById('scan-result');
            const input = form.qrcode;
            let isBusy = false;
            let cooldown = false;
            let bufferTimer = null;
            const CLIENT_COOLDOWN_MS = 2500;
            const MULTILINE_QUIET_MS = 300;

            async function submitScan(rawValue) {
                const token = String(rawValue || '').trim().replace(/\r/g, '');
                form.reset();
                input.focus();

                if (!token || isBusy || cooldown) {
                    return;
                }

                isBusy = true;
                cooldown = true;

                const data = new FormData();
                data.append('qrcode', token);
                data.append('_token', form.querySelector('[name=_token]').value);

                try {
                    const response = await fetch(@json(route('library.attendance.scan')), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': data.get('_token'),
                        },
                        body: data,
                    });
                    const payload = await response.json();
                    result.className = 'alert mt-3 ' + (response.ok ? 'alert-success' : 'alert-danger');
                    if (response.ok) {
                        const note = payload.duplicate ? ' (already recorded)' : '';
                        result.textContent = `${payload.patron.firstname} ${payload.patron.lastname}: ${payload.status}${note}`;
                    } else {
                        result.textContent = payload.message || 'Scan failed.';
                    }
                } catch (err) {
                    console.error(err);
                    result.className = 'alert mt-3 alert-danger';
                    result.textContent = 'Scan failed.';
                } finally {
                    isBusy = false;
                    setTimeout(() => { cooldown = false; }, CLIENT_COOLDOWN_MS);
                    input.focus();
                }
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                if (isBusy || cooldown) {
                    form.reset();
                    input.focus();
                    return;
                }
                clearTimeout(bufferTimer);
                bufferTimer = setTimeout(() => submitScan(input.value), MULTILINE_QUIET_MS);
            });
        })();
    </script>
</body>
</html>
