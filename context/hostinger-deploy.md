# Hostinger SSH deploy (Phenomit)

**Do not store passwords in this file.** Use Hostinger panel or a password manager.

| Field | Value |
|-------|--------|
| Host | `45.130.228.59` |
| Port | `65002` |
| User | `u843330709` |

## App paths (same git repo: `sahildev12/libcontrol`)

| Site | Path on server |
|------|----------------|
| libcontrol.phenomit.com | `/home/u843330709/domains/phenomit.com/public_html/libcontrol` |
| aims.phenomit.com | `/home/u843330709/domains/phenomit.com/public_html/aims` |

## Typical update (after `git push` to `main`)

```bash
APP=/home/u843330709/domains/phenomit.com/public_html/libcontrol   # or .../aims
cd "$APP"
git fetch origin && git reset --hard origin/main
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan optimize
```

Upload `public/build/` from local (`npm run build`) — npm is not available on this host.

## Last deploy

- **2026-09-22:** `11360a6` on libcontrol + aims; Vite `public/build` synced via SCP.
