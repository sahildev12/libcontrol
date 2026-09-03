<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup LibControl</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f8fafc; color: #0f172a; margin: 0; }
        .wrap { max-width: 760px; margin: 0 auto; padding: 2rem 1rem 4rem; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.5rem; margin-bottom: 1rem; }
        h1 { margin: 0 0 0.5rem; font-size: 1.75rem; }
        h2 { margin: 0 0 1rem; font-size: 1rem; }
        p, label { font-size: 0.95rem; }
        .grid { display: grid; gap: 1rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .full { grid-column: 1 / -1; }
        input, select { width: 100%; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0.65rem 0.75rem; }
        button { background: #4f46e5; color: #fff; border: 0; border-radius: 8px; padding: 0.75rem 1rem; font-weight: 600; cursor: pointer; }
        button:disabled { opacity: 0.6; cursor: not-allowed; }
        .ok { color: #047857; } .bad { color: #b91c1c; }
        .status { margin-top: 1rem; padding: 0.75rem 1rem; border-radius: 8px; display: none; }
        .status.ok { display: block; background: #ecfdf5; color: #065f46; }
        .status.err { display: block; background: #fef2f2; color: #991b1b; }
        ul { margin: 0; padding-left: 1.2rem; }
    </style>
</head>
<body>
    <div class="wrap" x-data="setupWizard({
        detectedUrl: @js($detectedUrl),
        defaultBaseDomain: @js($defaultBaseDomain),
        requirements: @js($requirements),
        installUrl: @js(route('setup.install')),
        testDatabaseUrl: @js(route('setup.test-database')),
        csrf: @js(csrf_token()),
    })" x-init="init()">
        <div class="card">
            <h1>Setup LibControl</h1>
            <p>Use this wizard when copying LibControl to a new server. It writes your <code>.env</code>, creates tables, switches sessions to the database, and creates your admin account.</p>
        </div>

        <div class="card">
            <h2>Server checks</h2>
            <ul>
                <li :class="requirements.php_ok ? 'ok' : 'bad'">PHP <span x-text="requirements.php_version"></span></li>
                <li :class="requirements.writable_paths ? 'ok' : 'bad'">Storage folders writable</li>
                <li :class="requirements.env_writable ? 'ok' : 'bad'"><code>.env</code> writable</li>
            </ul>
        </div>

        <form class="card" @submit.prevent="runSetup()">
            <h2>Application</h2>
            <div class="grid">
                <div class="full">
                    <label>App name</label>
                    <input type="text" x-model="form.app_name" required>
                </div>
                <div class="full">
                    <label>App URL</label>
                    <input type="url" x-model="form.app_url" required>
                </div>
                <div>
                    <label>Timezone</label>
                    <input type="text" x-model="form.timezone" required>
                </div>
                <div>
                    <label>Base domain for client subdomains</label>
                    <input type="text" x-model="form.tenant_base_domain" required>
                </div>
                <div class="full">
                    <label>Landlord hostnames (comma separated)</label>
                    <input type="text" x-model="form.tenant_landlord_hosts" required>
                </div>
            </div>

            <h2 style="margin-top: 1.5rem;">Database</h2>
            <div class="grid">
                <div>
                    <label>Host</label>
                    <input type="text" x-model="form.db_host" required>
                </div>
                <div>
                    <label>Port</label>
                    <input type="text" x-model="form.db_port" required>
                </div>
                <div>
                    <label>Database</label>
                    <input type="text" x-model="form.db_database" required>
                </div>
                <div>
                    <label>Username</label>
                    <input type="text" x-model="form.db_username" required>
                </div>
                <div class="full">
                    <label>Password</label>
                    <input type="password" x-model="form.db_password">
                </div>
                <div class="full" style="margin-top: 0.75rem;">
                    <button type="button" @click="prepareDatabase()" :disabled="preparingDb" class="inline-flex h-10 items-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700">
                        <span x-text="preparingDb ? 'Migrating database...' : 'Prepare database (auto-migrate)'"></span>
                    </button>
                    <p class="status" :class="dbStatusType" x-text="dbStatusMessage" x-show="dbStatusMessage" style="margin-top: 0.75rem;"></p>
                </div>
            </div>

            <h2 style="margin-top: 1.5rem;">Admin profile</h2>
            <p style="margin: 0 0 1rem; color: #475569;">Your client admin login. A separate developer account is created automatically in the background.</p>
            <div class="grid">
                <div class="full">
                    <label>Email</label>
                    <input type="email" x-model="form.admin_email" required>
                </div>
                <div class="full">
                    <label>Password</label>
                    <input type="password" x-model="form.admin_password" minlength="8" required>
                </div>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" :disabled="saving" x-text="saving ? 'Installing...' : 'Install LibControl'"></button>
            </div>
            <div class="status" :class="statusType" x-text="statusMessage" x-show="statusMessage"></div>
        </form>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        function setupWizard(config) {
            return {
                requirements: config.requirements,
                saving: false,
                preparingDb: false,
                statusMessage: '',
                statusType: '',
                dbStatusMessage: '',
                dbStatusType: '',
                form: {
                    app_name: 'LibControl',
                    app_url: config.detectedUrl,
                    timezone: 'Asia/Kolkata',
                    tenant_base_domain: config.defaultBaseDomain,
                    tenant_landlord_hosts: new URL(config.detectedUrl).hostname,
                    tenancy_enabled: true,
                    db_host: '127.0.0.1',
                    db_port: '3306',
                    db_database: '',
                    db_username: '',
                    db_password: '',
                    admin_email: '',
                    admin_password: '',
                },
                init() {},
                async prepareDatabase() {
                    this.preparingDb = true;
                    this.dbStatusMessage = '';
                    try {
                        const response = await fetch(config.testDatabaseUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': config.csrf,
                            },
                            body: JSON.stringify({
                                db_host: this.form.db_host,
                                db_port: this.form.db_port,
                                db_database: this.form.db_database,
                                db_username: this.form.db_username,
                                db_password: this.form.db_password,
                            }),
                        });
                        const data = await response.json();
                        if (!response.ok) throw new Error(data.message || 'Database preparation failed.');
                        this.dbStatusType = 'ok';
                        this.dbStatusMessage = data.message;
                    } catch (error) {
                        this.dbStatusType = 'err';
                        this.dbStatusMessage = error.message || 'Database preparation failed.';
                    } finally {
                        this.preparingDb = false;
                    }
                },
                async runSetup() {
                    this.saving = true;
                    this.statusMessage = '';
                    try {
                        const response = await fetch(config.installUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': config.csrf,
                            },
                            body: JSON.stringify(this.form),
                        });
                        const data = await response.json();
                        if (!response.ok) throw new Error(data.message || 'Setup failed.');
                        this.statusType = 'ok';
                        this.statusMessage = data.message + ' Redirecting to login...';
                        window.location.href = data.login_url;
                    } catch (error) {
                        this.statusType = 'err';
                        this.statusMessage = error.message || 'Setup failed.';
                        this.saving = false;
                    }
                },
            };
        }
    </script>
</body>
</html>
