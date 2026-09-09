# LibControl Library Release v2.1.3

Ready-to-upload package for library.dise.org.in (or any single client library).

## Included in this folder

- Full Laravel application code
- Production `vendor/` (no composer needed on server)
- Compiled frontend in `public/build/` (no npm needed on server)
- Database migrations in `database/migrations/`

## Update existing client (no terminal needed)

1. **Back up** the live database and download the client's `.env` file.
2. Upload **all files** from this folder over the existing install (FTP / File Manager).
3. **Do NOT overwrite** the client's `.env` — keep their DB credentials and APP_URL.
4. Log in to admin → **Settings → Database** → click **Run migrations**.
5. Hard refresh browser (Ctrl+Shift+R).

## First-time install

1. Upload folder to hosting.
2. Point domain document root to `public`.
3. Open `/setup` and complete the wizard.

## v2.1.3 fix

This release fixes the UTF-8 BOM bug from v2.1.2 that broke student AJAX (registration link, view student).