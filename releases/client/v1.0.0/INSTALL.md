# LibSpace Client Release v1.0.0

## Package contents

Upload and extract `libspace-client-v1.0.0.zip` to your hosting account.
Point your domain document root to the `public` folder inside the extracted app.

The zip also includes `database/libspace-install.sql` for direct phpMyAdmin import.

## Before install

1. Create a MySQL database in your hosting panel.
2. Edit `.env` in the extracted folder (File Manager):
   - `APP_URL` - your live site URL (https)
   - `DB_*` - database name, user, password
   - `LIBSPACE_LICENSE_KEY` - key from Phenomit
   - `MAIL_*` - SMTP settings (optional at first)
   - `LIBSPACE_ADMIN_EMAIL` / `LIBSPACE_ADMIN_PASSWORD` - first admin login

## Option A: Import SQL (recommended on shared hosting)

1. Open phpMyAdmin for the client database.
2. Import `database/libspace-install.sql` (or the standalone `libspace-client-v1.0.0.sql` from this release folder).
3. Create an empty file at `storage/app/install.lock` in File Manager.
4. Open the site and log in with the admin email/password from `.env`.

Default seeded login (change in `.env` before import if you edit the SQL manually):

- Email: `admin@your-domain.com`
- Password: `ChangeMeAfterLogin123!`

## Option B: Browser installer (no SQL import)

Open this URL once in your browser (token is in your `.env` as `LIBSPACE_SETUP_TOKEN`):

    /install?token=zQinMc3w1e4r5OoGEa2Dh8IFNyJdXBbT

Example after you set APP_URL:

    https://your-domain.com/install?token=zQinMc3w1e4r5OoGEa2Dh8IFNyJdXBbT

Click **Run installation**. This creates tables and your admin user.

## After install

- Log in with the admin email/password from `.env`
- Change the admin password immediately
- Remove or keep `LIBSPACE_SETUP_TOKEN` - installer is locked after success
- Do not enable `LIBSPACE_LICENSE_SERVER` on client servers

## Support

Licensed installs phone home to:

    https://libspace.phenomit.com/api/runtime/sync

Contact Phenomit if the site shows an unlicensed error.
