<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install LibSpace</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; margin: 0; padding: 2rem; color: #111827; }
        .card { max-width: 560px; margin: 0 auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 2px rgb(0 0 0 / 0.05); }
        h1 { margin: 0 0 0.5rem; font-size: 1.5rem; }
        p { margin: 0 0 1rem; color: #4b5563; line-height: 1.5; }
        code { background: #f3f4f6; padding: 0.1rem 0.35rem; border-radius: 4px; font-size: 0.9em; }
        button { background: #4f46e5; color: #fff; border: 0; border-radius: 8px; padding: 0.65rem 1rem; font-weight: 600; cursor: pointer; }
        button:disabled { opacity: 0.6; cursor: not-allowed; }
        .status { margin-top: 1rem; padding: 0.75rem 1rem; border-radius: 8px; display: none; }
        .status.ok { display: block; background: #ecfdf5; color: #065f46; }
        .status.err { display: block; background: #fef2f2; color: #991b1b; }
        ul { margin: 0.5rem 0 0; padding-left: 1.25rem; color: #374151; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Install LibSpace</h1>
        <p>Before continuing, confirm your <code>.env</code> file has the correct database credentials, <code>APP_URL</code>, and <code>LIBSPACE_LICENSE_KEY</code>.</p>
        <ul>
            <li>App URL: <code>{{ $appUrl }}</code></li>
            <li>Database: <code>{{ $dbDatabase ?: 'not set' }}</code></li>
        </ul>
        <p style="margin-top: 1rem;">This runs database migrations once. After success, the installer is locked.</p>
        <button type="button" id="run-install">Run installation</button>
        <div id="status" class="status"></div>
    </div>
    <script>
        const token = new URLSearchParams(window.location.search).get('token') || '';
        const statusEl = document.getElementById('status');
        const button = document.getElementById('run-install');

        button.addEventListener('click', async () => {
            button.disabled = true;
            statusEl.className = 'status';
            statusEl.textContent = 'Installing...';

            try {
                const response = await fetch(@json(route('install.run')).replace(/\/$/, '') + '?token=' + encodeURIComponent(token), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': @json(csrf_token()),
                        'Accept': 'application/json',
                    },
                });
                const data = await response.json();
                if (! response.ok) {
                    throw new Error(data.message || 'Installation failed.');
                }
                statusEl.className = 'status ok';
                statusEl.innerHTML = data.message + (data.login_url ? ' <a href="' + data.login_url + '">Go to login</a>' : '');
            } catch (error) {
                statusEl.className = 'status err';
                statusEl.textContent = error.message || 'Installation failed.';
                button.disabled = false;
            }
        });
    </script>
</body>
</html>
