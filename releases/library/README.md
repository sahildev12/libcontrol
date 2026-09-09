# LibControl Library Releases

Client library packages for a **single domain** (e.g. `dise.phenomit.com`).

These releases **do not** include Dev & Domains or Client Libraries admin tools.

## Latest

| Version | Folder | Notes |
|---------|--------|-------|
| 2.1.3 | `v2.1.3/` | Fix UTF-8 BOM in release PHP files (fixes client AJAX/HTML errors) |
| 2.1.2 | `v2.1.2/` | Student app PIN setup API, persistent expired seat status, seat map edit student, fee list seat column |
| 2.1.1 | `v2.1.1/` | Fee renew deployment packager bump |
| 2.1.0 | `v2.1.0/` | Attendance addon, shared family contacts, database backup/migrate tools |
| 2.0.2 | `v2.0.2/` | Previous stable library release |

## Addon ZIPs

Distributable addon packages live in [`addon/`](addon/).

```powershell
powershell -ExecutionPolicy Bypass -File scripts/build-attendance-addon-zip.ps1
```

## Build locally

Unzipped release folder (default — upload `vX.Y.Z/` to the client server):

```powershell
powershell -ExecutionPolicy Bypass -File scripts/build-library-release.ps1
```

Optional zip archive:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/build-library-release.ps1 -Zip
```

## Landlord server

Use `releases/platform/` for `libcontrol.phenomit.com` (manages all clients).
