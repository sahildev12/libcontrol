# LibControl hub — deploy guide (libcontrol.phenomit.com)

**No release zip required.** Deploy directly from the master LibControl codebase (git pull, FTP, or rsync).

This server is the **developer / license hub** — not a client library installation.

---

## What you’re deploying

Recent hub-focused changes in this codebase:

- **Developer Support Tickets** — inbox with search, filters, unread indicators
- **Portal Settings** — manage hosted client library settings from the hub (tenancy mode)
- **Client Libraries** — hosted client registry + remote manage
- **Dev & Domains** — self-hosted licensed deployments + queued remote commands
- **Runtime sync API** — client installations (e.g. Aims) push metrics and support tickets here
- **Static support site** — `libcontrol-website/` (articles, documentation) — host under phenomit.com if not already

---

## Pre-deploy

- [ ] Backup landlord MySQL database
- [ ] Backup `storage/` and `.env`
- [ ] Confirm DNS: `libcontrol.phenomit.com` → this host

---

## Deploy steps

### 1. Upload code

Sync repo to server. **Keep production `.env`.**

Include: `app/`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/`, `libcontrol-website/`, `composer.json`, `composer.lock`

Exclude: `node_modules/`, `tests/`, client `releases/` zips, dev `.env`

### 2. Build & dependencies

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

### 3. Migrations (landlord DB)

```bash
php artisan migrate --force
```

Recent hub migrations (if not already applied):

- `2026_09_18_*` — support tickets  
- `2026_09_19_*` — support ticket attachments, remote manage, admin impact settings  
- `2026_09_21_*` — `read_at` on support tickets  

### 4. Cache

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 5. Static website (optional, for Help links)

Upload or sync `libcontrol-website/` to your Phenomit web root, e.g.:

- `https://phenomit.com/libcontrol/support-articles.html`
- `https://phenomit.com/libcontrol/documentation.html`

Match URLs in `config/libcontrol.php` or `.env`:

- `LIBCONTROL_SUPPORT_ARTICLES_URL`
- `LIBCONTROL_SUPPORT_DOCUMENTATION_URL`

---

## Production `.env` highlights

```env
APP_URL=https://libcontrol.phenomit.com

LIBCONTROL_LICENSE_SERVER=true
LIBCONTROL_TENANCY_ENABLED=true
LIBCONTROL_TENANT_BASE_DOMAIN=phenomit.com
LIBCONTROL_TENANT_LANDLORD_HOSTS=libcontrol.phenomit.com
```

Use a dedicated **landlord** MySQL database (not a client library DB).

---

## Post-deploy smoke tests

| Test | URL / action |
|------|----------------|
| Developer login | `/admin/login` as developer admin |
| Support Tickets | Sidebar → list, search, open ticket (unread dot clears) |
| Portal Settings | Sidebar → pick hosted library → settings load |
| Client Libraries | List tenants, remote manage |
| Dev & Domains | Licensed deployments list |
| API | Client sync from Aims still succeeds |

---

## Do you need a “platform release zip”?

**No** — for libcontrol.phenomit.com you deploy the same repo you develop in. Use this `DEPLOY.md` as the checklist.

Use `scripts/build-platform-release.ps1` only when you need a **portable zip** for a fresh host (not required for your current workflow).

---

## Client releases (e.g. Aims)

Client installations are separate deploys. See:

`releases/client/aims/v2.1.5/RELEASE.md`
