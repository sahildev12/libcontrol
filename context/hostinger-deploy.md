# Hostinger SSH deploy (Katsindia)

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
| libcontrol.katsindia.com | Old hub (still used by existing clients' sync + mobile resolver) | `/home/u843330709/domains/katsindia.com/public_html/libcontrol` | — |
| aims.katsindia.com | Client | `/home/u843330709/domains/katsindia.com/public_html/aims` | — |

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

## PhonePe payments (hub only)

- Keys live only in the hub `.env` (`PHONEPE_ENV`, `PHONEPE_CLIENT_ID`, `PHONEPE_CLIENT_VERSION`, `PHONEPE_CLIENT_SECRET`, `PHONEPE_WEBHOOK_USERNAME`, `PHONEPE_WEBHOOK_PASSWORD`). Run `php artisan config:cache` after editing.
- Webhook (PhonePe dashboard → Developer Settings → Webhooks): `https://libcontrol.in/api/webhooks/phonepe`, SHA auth, events `checkout.order.completed` + `checkout.order.failed`. PhonePe limits the password to 20 chars with letters and digits.
- Test mode and live mode have separate keys and webhooks. Going live: switch the dashboard out of Test Mode, copy the live keys, set `PHONEPE_ENV=production`, and recreate the webhook there.
- `payments:reconcile` runs every 10 minutes via the Laravel scheduler, which needs an hPanel cron: `* * * * * cd /home/u843330709/domains/libcontrol.in/public_html && php artisan schedule:run >> /dev/null 2>&1`.
- Payments list: `https://libcontrol.in/developer/payments`. Clients pay from `/billing` (plans) and `/growth` (packages, quoted services).

## Last deploy

- **2026-10-12:** `e1a553d` PhonePe payments (plans, growth packages, quoted services) on hub + demo; payments migration run; hub `.env` has sandbox keys; sandbox webhook created and validated.
- **2026-10-12:** `e465014` pushed to `origin/main` (Katsindia rebrand, plan-limit UX, terms notice). Run `scripts/deploy-libcontrol-in.ps1` on a machine with Hostinger SSH (syncs git + `public/build` to hub and demo).
- **2026-10-09:** `d14811e` first deploy of libcontrol.in (main hub + marketing) and demo.libcontrol.in (demo data); Vite `public/build` synced via SCP.
- **2026-09-22:** `11360a6` on libcontrol + aims; Vite `public/build` synced via SCP.
