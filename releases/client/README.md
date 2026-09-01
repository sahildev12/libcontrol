# LibSpace client releases

Versioned client deployment packages for shared hosting (no SSH).

## Folder layout

```
releases/client/
  v1.0.0/
    libspace-client-v1.0.0.zip   # Upload this to the client server
    INSTALL.md                   # Install steps for this version
    VERSION.txt                  # Version label
    SETUP-TOKEN.txt              # One-time browser install token (generated per build)
```

## Build a new client zip

From the project root:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/build-client-release.ps1
```

This will:

1. Run `composer install --no-dev`
2. Run `npm run build`
3. Export `libspace-client-v1.0.0.sql` (full schema + seed data for phpMyAdmin)
4. Stage the app with a **client** `.env` (production, license client mode)
5. Include `vendor/` and `public/build/` (no Node/npm needed on server)
6. Write `releases/client/v1.0.0/libspace-client-v1.0.0.zip`

Bump the `$Version` variable in `scripts/build-client-release.ps1` when shipping a new release.

## Client install flow

1. Upload and extract the zip
2. Point the domain document root to the `public` folder
3. Edit `.env` (database, APP_URL, license key, admin login)
4. **Option A:** Import `database/libspace-install.sql` in phpMyAdmin, then create `storage/app/install.lock`
5. **Option B:** Open `/install?token=YOUR_SETUP_TOKEN` in the browser
6. Log in and change the admin password

Each zip gets a unique `LIBSPACE_SETUP_TOKEN` and `APP_KEY`. The token is in `.env`, `SETUP-TOKEN.txt`, and `INSTALL.md` after each build.
