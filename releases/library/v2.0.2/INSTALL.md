# LibSpace Library Release v2.0.2

## What this is

This zip is for a **single client library** (e.g. dise.phenomit.com).
It does **not** include Dev & Domains or Client Libraries admin tools.

After setup, this domain automatically pings libspace.phenomit.com so Phenomit can see the new installation.

## Upload steps

1. Upload and extract on your hosting (e.g. dise.phenomit.com).
2. Point the domain to the `public` folder.
3. Open `https://dise.phenomit.com/setup`.
4. Set **App name** to your library name (e.g. Dise).
5. Enter database details and click **Prepare database (auto-migrate)**.
6. Enter your **admin email and password** and click **Install LibSpace**.
7. Log in at `/admin/login`.

## Phenomit visibility

When install finishes, a discovery ping is sent to:

`https://libspace.phenomit.com/api/runtime/sync`

You will see the domain under **Dev & Domains -> Live installations** on libspace.phenomit.com (developer login).

## Not included

- Dev & Domains UI
- Client Libraries (multi-tenant landlord tools)
- License server API endpoints
