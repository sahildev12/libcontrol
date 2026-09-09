# LibControl Student App

Flutter student app for LibControl library installations.

## Library connection

One APK works for all client libraries. On first launch, students either:

1. Enter their **library code** (e.g. `DISE`) — resolved via Phenomit at `libcontrol.phenomit.com`
2. **Scan the attendance QR** from their branch — the app uses the QR URL host as the library server

The connected server URL is saved on the device. Students can change it later from **Profile → Settings → Change library**.

## Build for production

```bash
cd mobile/libcontrol_app
flutter pub get
flutter build apk --dart-define=RESOLVER_BASE_URL=https://libcontrol.phenomit.com
```

## Local development (XAMPP / emulator)

```bash
flutter run --dart-define=API_BASE_URL=http://10.0.2.2/libspace/public
```

`API_BASE_URL` skips the library-connect screen and points auth APIs at your local Laravel install.

## Auth API endpoints

After a library is connected, auth calls go to:

- `POST /api/v1/student/auth/check-code`
- `POST /api/v1/student/auth/setup-pin`
- `POST /api/v1/student/auth/login`
- `GET /api/v1/student/auth/me`
- `POST /api/v1/student/auth/logout`

## Phenomit registry

Client libraries register their code prefix when they ping `libcontrol.phenomit.com` (runtime sync). The mobile resolver reads that registry:

`GET https://libcontrol.phenomit.com/api/v1/mobile/libraries/{CODE}`

Ensure each client library has a **student code prefix** set in Settings and has synced at least once with Phenomit.
