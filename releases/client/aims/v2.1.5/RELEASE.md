# Aims Library — LibControl v2.1.5

**Client:** Aims Library  
**Slug:** `aims`  
**URL:** https://aims.phenomit.com  
**Release date:** 2026-09-21  
**Type:** In-place update — upload the built release folder (see below)

## Where is the Aims code?

Aims does **not** have a separate git repo. It is the same LibControl codebase, packaged for the Aims client with Aims `.env` baked in.

**Upload this folder to Hostinger:**

```
releases/client/aims/v2.1.5/staging/
```

Rebuild anytime from the master repo:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/build-aims-release.ps1
```

Optional fresh-install package (SQL + zip):

```powershell
powershell -ExecutionPolicy Bypass -File scripts/build-aims-release.ps1 -WithZip -WithSql
```

---

## What’s in this release

Client-facing and shared product updates going live on the Aims installation:

- **Help & Support** — in-app tab to open support tickets; links to Phenomit support articles and documentation.
- **Public library website** — Settings → Website tab (hero, amenities, social links, WhatsApp).
- **Email notification toggles** — welcome, birthday, offers, recovery emails in Settings.
- **Attendance addon** — QR / staff GPS / manual check-in (enable addon after deploy if not already active).
- **Settings improvements** — branch hours, ID cards, student ID prefix, branding uploads.
- **Fee / P&L / UI polish** — datatable and layout consistency across admin pages.
- **Student contact rules** — Aims customization (mandatory phone + email where configured).

**Not included on client servers** (hub-only — lives on libcontrol.phenomit.com):

- Developer Portal Settings  
- Developer support ticket inbox  
- Client Libraries / licensed deployment remote manage  

---

## Pre-deploy checklist

- [ ] Full database backup (phpMyAdmin export or Hostinger backup)
- [ ] Download current `storage/app/public` uploads if you replace the whole tree
- [ ] Note current `.env` — **do not overwrite** on upload
- [ ] Confirm `LIBCONTROL_LICENSE_KEY` and `LIBCONTROL_SYNC_ENDPOINT` stay pointed at Phenomit

---

## Deploy steps (existing Aims install)

### 1. Upload code

Upload/sync the LibControl codebase to the Aims hosting folder. **Keep the live `.env` file.**

Typical approach:

- Git pull on server, **or**
- FTP/rsync changed folders: `app/`, `bootstrap/`, `config/`, `database/`, `public/build/`, `resources/`, `routes/`, `composer.json`, `composer.lock`

**Do not upload:** `.env` from your dev machine, `node_modules/`, `.git/`, `tests/`, `releases/`

Ensure root `.htaccess` exists (routes requests to `public/`). Template: `deploy/client-root.htaccess`

### 2. Dependencies & assets (on server or build locally first)

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

If you build assets locally, upload `public/build/` after `npm run build`.

### 3. Run migrations (required for Help & Support)

```bash
php artisan migrate --force
```

Support tickets need **both** migrations:

- `2026_09_18_120000_create_support_tickets_table`
- `2026_09_19_160000_create_support_ticket_attachments_table`
- `2026_09_21_120000_add_read_at_to_support_tickets_table` (hub only; safe on client)

On **libcontrol.phenomit.com** run the same `migrate --force` so `/api/support/tickets` can receive tickets from client sites.

### 4. Attendance addon (if using attendance)

```bash
php artisan LibControl:addon-install attendance
php artisan LibControl:addon-enable attendance
```

Or use **Settings → Addons** in the admin UI after login.

Addon migrations live under `database/migrations/addons/attendance/`.

### 5. Cache & optimize

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 6. Post-deploy smoke tests

| Area | What to verify |
|------|----------------|
| Login | `/admin/login` works |
| Dashboard | Loads without 500 |
| Settings | General, Website, Email tabs save |
| Help & Support | New ticket submits; article/doc links open |
| Attendance | Menu visible (if addon enabled); QR settings page loads |
| Students / Fees | List and create still work |
| Sync | Settings → sync to Phenomit (if configured) |

---

## Environment reminders (Aims `.env`)

| Variable | Expected |
|----------|----------|
| `APP_URL` | `https://aims.phenomit.com` |
| `LIBCONTROL_LICENSE_SERVER` | `false` |
| `LIBCONTROL_TENANCY_ENABLED` | `false` |
| `LIBCONTROL_CLIENT_NAME` | Aims Library |
| `LIBCONTROL_DEPLOYMENT_SLUG` | `aims` |
| `LIBCONTROL_SYNC_ENDPOINT` | `https://libcontrol.phenomit.com/api/runtime/sync` |
| `LIBCONTROL_SUPPORT_ARTICLES_URL` | `https://phenomit.com/libcontrol/support-articles.html` (optional override) |
| `LIBCONTROL_SUPPORT_DOCUMENTATION_URL` | `https://phenomit.com/libcontrol/documentation.html` (optional override) |

---

## Rollback

1. Restore previous code snapshot from backup  
2. Restore database backup if migrations caused issues  
3. `php artisan config:clear && php artisan cache:clear`

---

## Support

Issues during deploy: note the URL, screenshot, and `storage/logs/laravel.log` tail — open via Help & Support or Phenomit developer panel.
