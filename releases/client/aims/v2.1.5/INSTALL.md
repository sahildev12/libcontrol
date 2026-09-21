# LibControl Client Release â€” Aims Library

**Slug:** `aims`  
**Version:** `2.1.5`  
**URL:** `https://aims.phenomit.com`

## Package contents

- `LibControl-aims-v2.1.5.zip` â€” upload to hosting (document root = extracted app folder; root `.htaccess` routes to `public/`)
- `LibControl-aims-v2.1.5.sql` â€” phpMyAdmin import
- `.env.example` â€” client environment template (also inside the zip as `.env`)

## Client .env highlights

| Setting | Value |
|---------|-------|
| APP_NAME | Aims Library |
| APP_URL | https://aims.phenomit.com |
| DB_DATABASE | aims_libcontrol |
| LIBCONTROL_CLIENT_NAME | Aims Library |
| LIBCONTROL_DEPLOYMENT_SLUG | aims |
| LIBCONTROL_INSTALL_STUDENT_CODE_PREFIX | AIMS |
| LIBCONTROL_PUBLIC_URL | https://aims.phenomit.com |
| LIBCONTROL_ADMIN_EMAIL | admin@aims.phenomit.com |

## Before go-live

1. Create MySQL database `aims_libcontrol` on Hostinger.
2. Edit `.env` after upload:
   - `DB_USERNAME` / `DB_PASSWORD`
   - `LIBCONTROL_LICENSE_KEY` from Phenomit
   - `MAIL_USERNAME` / `MAIL_PASSWORD` (quote passwords containing `#`)
3. Import `database/LibControl-install.sql` in phpMyAdmin **or** open the browser installer.

## Default admin login (from SQL seed)

- Email: `admin@aims.phenomit.com`
- Password: `ChangeMeAfterLogin123!`

Change the password immediately after first login.

## Browser installer (alternative)

`https://aims.phenomit.com/install?token=ROYCnNu6MhpaAfGxTo89Bm7zs03gcKbV`

Token is also in `LIBCONTROL_SETUP_TOKEN` inside `.env`.

## After install

- Run **Settings â†’ Database â†’ Run migrations** when updating an existing install.
- Sync library to Phenomit: **Settings â†’ Developer â†’ Sync library to Phenomit**
- Do **not** enable `LIBCONTROL_LICENSE_SERVER` on client servers.
