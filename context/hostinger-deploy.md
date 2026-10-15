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
| demo.libcontrol.in | Public demo client (`LIBCONTROL_DEMO_MODE=true`, `LIBCONTROL_DEMO_PAYMENTS=true` for ₹1 PhonePe test pack) | `/home/u843330709/domains/libcontrol.in/public_html/demo` | `u843330709_lib_demo` |
| libcontrol.katsindia.com | Old hub (still used by existing clients' sync + mobile resolver) | `/home/u843330709/domains/katsindia.com/public_html/libcontrol` | — |
| aims.katsindia.com | Client | `/home/u843330709/domains/katsindia.com/public_html/aims` | — |

Notes for libcontrol.in:

- `public_html` is a git checkout; `/demo/` is listed in `public_html/.git/info/exclude` so the demo app is never touched by the main repo.
- Root `.htaccess` (tracked) rewrites everything into `public/`.
- After deploy, run `bash scripts/ensure-marketing-public-assets-link.sh` on the hub so `public/assets` → `libcontrol-website/assets`. Marketing images must be served as static files; serving them through Laravel/PHP triggers Hostinger CDN `422 Invalid source image` for larger PNGs/JPEGs.
- Demo is licensed on the libcontrol.in hub (Dev & Domains → "LibControl Demo") and syncs to `https://libcontrol.in/api/runtime/sync`.
- Demo data: `php artisan db:seed --class=DemoSiteSeeder --force` (no developer account on the demo).
- SMTP: copy `deploy/mail.env.example` to `deploy/mail.env` (gitignored) with the Hostinger mailbox password, then run `scripts/deploy-libcontrol-in.ps1` — it uploads `storage/app/private/mail-settings.php` to hub and demo. Or create that file manually on the server from `storage/app/private/mail-settings.example.php`.

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
- Test mode and live mode have **separate** Client ID / Client Secret. Turning off Test Mode in [business.phonepe.com](https://business.phonepe.com/dashboard) does **not** auto-update the server.
- **Go live on libcontrol.in hub** (`public_html/.env`):
  1. [developer.phonepe.com](https://developer.phonepe.com) → **Developer Settings** (production / live, not UAT) → copy **Production** `Client ID` and `Client Secret`.
  2. Set `PHONEPE_ENV=production`, `PHONEPE_CLIENT_ID=…`, `PHONEPE_CLIENT_SECRET=…`, `PHONEPE_CLIENT_VERSION=1`.
  3. Create a **production** webhook → `https://libcontrol.in/api/webhooks/phonepe` (SHA auth; events `checkout.order.completed`, `checkout.order.failed`) → set `PHONEPE_WEBHOOK_USERNAME` / `PHONEPE_WEBHOOK_PASSWORD` (password ≤20 chars, letters+digits).
  4. `php artisan config:clear && php artisan config:cache`
  5. `php scripts/phonepe-connectivity-test.php` — expect redirect host **without** `uat` (e.g. `mercury-t2.phonepe.com` or `mercury.phonepe.com`). That is **live** checkout, not sandbox (`mercury-uat`).
- PhonePe onboarding only allows **`https://libcontrol.in`** as the transacting URL. Client installs (e.g. **demo.libcontrol.in**) must not send the browser straight to PhonePe — checkout returns a signed **`https://libcontrol.in/payments/{uuid}/launch`** link that redirects to PhonePe on the approved host.
- **QR missing on PhonePe pay page:** Turn **Test Mode OFF** in [business.phonepe.com](https://business.phonepe.com). Ensure hub `APP_URL` is `https://libcontrol.in`. The app sends `Referrer-Policy: strict-origin-when-cross-origin`.
- If auth returns `CLIENT_NOT_FOUND`, the hub still has **UAT** keys while `PHONEPE_ENV=production`.
- `payments:reconcile` runs every 10 minutes via the Laravel scheduler, which needs an hPanel cron: `* * * * * cd /home/u843330709/domains/libcontrol.in/public_html && php artisan schedule:run >> /dev/null 2>&1`.
- Payments list: `https://libcontrol.in/developer/payments`. Clients pay from `/billing` (plans) and `/growth` (packages, quoted services).

## Last deploy

- **2026-10-12:** `e1a553d` PhonePe payments (plans, growth packages, quoted services) on hub + demo; payments migration run; hub `.env` has sandbox keys; sandbox webhook created and validated.
- **2026-10-12:** `e465014` pushed to `origin/main` (Katsindia rebrand, plan-limit UX, terms notice). Run `scripts/deploy-libcontrol-in.ps1` on a machine with Hostinger SSH (syncs git + `public/build` to hub and demo).
- **2026-10-09:** `d14811e` first deploy of libcontrol.in (main hub + marketing) and demo.libcontrol.in (demo data); Vite `public/build` synced via SCP.
- **2026-09-22:** `11360a6` on libcontrol + aims; Vite `public/build` synced via SCP.
