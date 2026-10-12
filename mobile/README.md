# LibControl Student App

Flutter student app for LibControl library installations.

## Library connection

One APK works for all client libraries. On first launch, students either:

1. Enter their **6-digit library code** — resolved via **Katsindia** at `libcontrol.katsindia.com`
2. **Scan the attendance QR** from their branch — the app uses the QR URL host as the library server

The connected server URL is saved on the device. Students can change it later from **Profile → Settings → Change library**.

## Run / test the app

The app uses Katsindia by default. No extra flags needed:

```bash
cd mobile
flutter pub get
flutter run
```

Production build:

```bash
flutter build apk --dart-define=RESOLVER_BASE_URL=https://libcontrol.katsindia.com
```

## Register your library on Katsindia (required for code lookup)

Before students can enter a library code, your **local admin** must sync once with Katsindia:

1. Start Laravel: `php artisan serve --host=0.0.0.0 --port=8000`
2. In `.env`, set the URL your **phone** can reach (from `ipconfig`):

```env
LIBCONTROL_PUBLIC_URL=http://192.168.1.50:8000
```

Do **not** use `127.0.0.1` — Katsindia will return that to the app and the phone cannot connect.

3. Sync to Katsindia:

```bash
php artisan app:sync-runtime-metrics
```

4. Copy the **6-digit library code** from **Admin → Branches → Library code** tab
5. In the app, enter that code — Katsindia returns your `APP_URL` as the library server

Lookup endpoint:

`GET https://libcontrol.katsindia.com/api/v1/mobile/libraries/{CODE}`

## Auth API endpoints

After a library is connected, auth calls go to **your library server** (not Katsindia):

- `POST /api/v1/student/auth/check-code`
- `POST /api/v1/student/auth/setup-pin`
- `POST /api/v1/student/auth/login`
- `GET /api/v1/student/auth/me`
- `POST /api/v1/student/auth/logout`
