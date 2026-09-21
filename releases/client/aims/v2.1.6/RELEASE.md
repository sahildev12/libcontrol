# Aims Library — LibControl v2.1.6

**Client:** Aims Library  
**Slug:** `aims`  
**URL:** https://aims.phenomit.com  
**Release date:** 2026-09-21  
**Type:** In-place update — upload the built release folder (see below)

## Where is the Aims code?

**Upload this folder to Hostinger:**

```
releases/client/aims/v2.1.6/staging/
```

Rebuild from master repo:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/build-aims-release.ps1
```

---

## What’s new in v2.1.6 (since v2.1.5)

- **Support tickets only save when Phenomit accepts them** — no more “saved locally” fallback; clear error if hub API fails.
- **Better sync errors** — invalid license key, missing hub API, connection failures show specific messages.
- **Help & Support stability** — safer handling when attachment migrations are missing.

Includes everything from v2.1.5: Help & Support UI, website settings, email toggles, attendance addon, settings/UI polish.

---

## Deploy steps (existing Aims install)

1. Upload `staging/` over the Aims folder — **keep live `.env`**
2. `composer install --no-dev --optimize-autoloader`
3. `php artisan migrate --force`
4. `php artisan config:cache && php artisan route:cache && php artisan view:cache`

### Aims `.env` required for support tickets

| Variable | Value |
|----------|--------|
| `LIBCONTROL_LICENSE_KEY` | Real `ls_...` key from libcontrol → Dev & Domains → Edit deployment |
| `LIBCONTROL_SYNC_ENDPOINT` | `https://libcontrol.phenomit.com/api/runtime/sync` |
| `LIBCONTROL_LICENSE_SERVER` | `false` |

### libcontrol.phenomit.com (same deploy)

| Variable | Value |
|----------|--------|
| `LIBCONTROL_LICENSE_SERVER` | `true` |
| Run | `php artisan migrate --force` |

Tickets submit to: `https://libcontrol.phenomit.com/api/support/tickets`

---

## Post-deploy test

Help & Support → submit ticket → should say **“Support ticket submitted…”** and appear in libcontrol **Support Tickets** inbox.
