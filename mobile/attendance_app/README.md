# LibControl Staff Attendance (Android)

Flutter app for library staff to mark student attendance with GPS geofence validation.

## Project structure

```
lib/
  core/
    models/          # User, attendance DTOs
    theme/           # LibControl colors + Material theme
    utils/           # API error helpers
    widgets/         # Shared UI (stats, banners, empty states)
  features/
    auth/            # Login screen
    home/            # Dashboard + geofence status
    students/        # Search + mark attendance
  services/          # API, storage, location
  main.dart
```

## Setup

1. `flutter pub get`
2. Start Laravel: `php artisan serve --host=0.0.0.0 --port=8000`
3. Install/enable the Attendance addon on the tenant.
4. Connect your Android phone (USB debugging on) or use an emulator.
5. `flutter run` (or install the built APK below).

### Server URL on a real phone

Use your PC's LAN IP, not `localhost`:

- Example: `http://192.168.1.10:8000`
- Phone and PC must be on the same Wi‑Fi.
- `php artisan serve` must bind to `0.0.0.0`, not `127.0.0.1` only.

Emulator → host machine: `http://10.0.2.2:8000`

### Phone not detected?

1. Enable **Developer options** → **USB debugging** on the phone.
2. Accept the RSA fingerprint prompt on the device.
3. Install OEM USB drivers if needed (Windows).
4. Verify:  
   `C:\Users\acer\AppData\Local\Android\sdk\platform-tools\adb.exe devices`
5. Retry: `flutter devices`

## Demo login

After `php artisan libcontrol:seed-demo`:

- Email: `admin@main.LibControl.test`
- Password: `password`

## API

Sanctum token auth:

- `POST /api/v1/auth/login`
- `GET /api/v1/attendance/context`
- `POST /api/v1/attendance/check-in`
- `POST /api/v1/attendance/check-in/bulk`

## Build APK

Debug (already built for sideloading):

```bash
flutter build apk --debug
```

Release:

```bash
flutter build apk --release
```

Output: `build/app/outputs/flutter-apk/`
