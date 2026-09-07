# LibControl Addon Packages

Store distributable addon ZIP files here for client server uploads.

## Build an addon zip

```powershell
powershell -ExecutionPolicy Bypass -File scripts/build-attendance-addon-zip.ps1
```

Output example: `LibControl-addon-attendance-v1.0.0.zip`

## Install on a client library

1. Log in as platform admin on the client site.
2. Open **Settings → Addons** (or the addon install screen).
3. Upload the ZIP from this folder.
