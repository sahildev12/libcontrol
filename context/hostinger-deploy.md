# Hostinger SSH deploy (Phenomit)

**Do not store passwords in this file.** Use Hostinger panel or a password manager.

| Field | Value |
|-------|--------|
| Host | `45.130.228.59` |
| Port | `65002` |
| User | `u843330709` |

## App paths (same git repo: `sahildev12/libcontrol`)

| Site | Role | Path on server | Database |
|------|------|----------------|----------|
| libcontrol.in (+ www) | Main: marketing site + license hub (`LIBCONTROL_LICENSE_SERVER=true`) | `/home/u843330709/domains/libcontrol.in/public_html` | `u843330709_libcontrol_in` |
| demo.libcontrol.in | Public demo client (`LIBCONTROL_DEMO_MODE=true`, logins shown on sign-in) | `/home/u843330709/domains/libcontrol.in/public_html/demo` | `u843330709_lib_demo` |
| libcontrol.phenomit.com | Old hub (still used by existing clients' sync + mobile resolver) | `/home/u843330709/domains/phenomit.com/public_html/libcontrol` | — |
| aims.phenomit.com | Client | `/home/u843330709/domains/phenomit.com/public_html/aims` | — |

Notes for libcontrol.in:

- `public_html` is a git checkout; `/demo/` is listed in `public_html/.git/info/exclude` so the demo app is never touched by the main repo.
- Root `.htaccess` (tracked) rewrites everything into `public/`.
- Demo is licensed on the libcontrol.in hub (Dev & Domains → "LibControl Demo") and syncs to `https://libcontrol.in/api/runtime/sync`.
- Demo data: `php artisan db:seed --class=DemoSiteSeeder --force` (no developer account on the demo).
- Both sites use `MAIL_MAILER=log` until a libcontrol.in mailbox is configured.

## Typical update (after `git push` to `main`)

```bash
APP=/home/u843330709/domains/libcontrol.in/public_html   # or .../public_html/demo
cd "$APP"
git fetch origin && git reset --hard origin/main
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize
```

Upload `public/build/` from local (`npm run build`) — npm is not available on this host.

## Last deploy

- **2026-10-09:** `d14811e` first deploy of libcontrol.in (main hub + marketing) and demo.libcontrol.in (demo data); Vite `public/build` synced via SCP.
- **2026-09-22:** `11360a6` on libcontrol + aims; Vite `public/build` synced via SCP.
