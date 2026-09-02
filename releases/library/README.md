# LibSpace Library Releases

Client library zips for a **single domain** (e.g. `dise.phenomit.com`).

These releases **do not** include Dev & Domains or Client Libraries admin tools.

## Latest

| Version | File | Notes |
|---------|------|-------|
| 2.0.0 | `v2.0.0/libspace-library-v2.0.0.zip` | Setup wizard, auto-migrate, discovery ping to libspace |

## Build locally

```powershell
powershell -ExecutionPolicy Bypass -File scripts/build-library-release.ps1
```

## Landlord server

Use `releases/platform/` for `libspace.phenomit.com` (manages all clients).
