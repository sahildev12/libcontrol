# Client release packaging

## 2026-09-19 — Root `.htaccess` required in every client zip

All client release zips **must** include a root `.htaccess` so shared hosting can use the project folder as document root (not only `public/`):

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

- Template: `deploy/client-root.htaccess`
- `scripts/build-client-release.ps1` copies it into every staging zip as `.htaccess`
- Do not remove this step when building new client packages (Aims, Dise, etc.)

## 2026-09-21 — Aims v2.1.5 (in-place deploy, no zip)

- Release notes: `releases/client/aims/v2.1.5/RELEASE.md`
- Hub deploy checklist: `releases/platform/DEPLOY.md`
- Bump `scripts/build-aims-release.ps1` to `2.1.5` for future zip builds
- Upload master codebase to aims.phenomit.com; keep live `.env`; run `migrate --force` and enable attendance addon if needed

## 2026-09-19 — Fresh install must open `/setup`

- Without `storage/app/install.lock`, visiting any URL should redirect to `/setup`
- Client `.env` uses `SESSION_DRIVER=file` and `CACHE_STORE=file` until install completes (avoid DB 500)
- Write client `.env` files **without UTF-8 BOM** (Linux hosting breaks on BOM)
