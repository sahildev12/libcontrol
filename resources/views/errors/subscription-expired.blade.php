@php
    $settings = \App\Models\PlatformSetting::current();
    $logoUrl = $settings->logoWithTextUrl();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subscription ended · {{ $settings->displayName() }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; background: #f1f5f9; color: #0f172a; }
        .card { width: 100%; max-width: 460px; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 45px rgba(8, 42, 103, .12); }
        .head { background: #082a67; padding: 28px 28px 24px; color: #fff; }
        .logo { display: block; height: 32px; margin-bottom: 18px; background: #fff; border-radius: 8px; padding: 4px 8px; }
        .badge { display: inline-flex; align-items: center; gap: 6px; background: #FFCC00; color: #082a67; border-radius: 999px; padding: 4px 10px; font-size: 11px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        h1 { margin: 12px 0 6px; font-size: 22px; line-height: 1.25; }
        .head p { margin: 0; color: rgba(255, 255, 255, .82); font-size: 14px; line-height: 1.5; }
        .body { padding: 22px 28px 26px; }
        .body p { margin: 0 0 16px; color: #475569; font-size: 14px; line-height: 1.6; }
        button { width: 100%; height: 42px; border: 0; border-radius: 10px; background: #082a67; color: #fff; font-weight: 700; font-size: 14px; cursor: pointer; }
        button:hover { filter: brightness(1.15); }
    </style>
</head>
<body>
    <div class="card">
        <div class="head">
            @if ($logoUrl)
                <img class="logo" src="{{ $logoUrl }}" alt="{{ $settings->displayName() }}">
            @endif
            <span class="badge">Subscription ended</span>
            <h1>LibControl is paused for this library</h1>
            <p>
                The LibControl subscription
                @if ($paidUntil)
                    ended on {{ $paidUntil->format('d M Y') }}.
                @else
                    has ended.
                @endif
            </p>
        </div>
        <div class="body">
            <p>Your data is safe. Ask the library owner to renew the plan from <strong>Subscription &amp; Billing</strong> in their admin login, and access comes back right after payment.</p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Log out</button>
            </form>
        </div>
    </div>
</body>
</html>
