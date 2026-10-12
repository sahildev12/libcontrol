@php
    $state = $payment->status;
    $pending = in_array($state, [\App\Models\Payment::STATUS_CREATED, \App\Models\Payment::STATUS_PENDING], true);
    $stillWaiting = $pending && $payment->created_at?->gt(now()->subMinutes(10));
    $title = match (true) {
        $state === \App\Models\Payment::STATUS_COMPLETED => 'Payment successful',
        $state === \App\Models\Payment::STATUS_FAILED => 'Payment failed',
        $stillWaiting => 'Confirming your payment…',
        default => 'Payment not completed yet',
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    @if ($stillWaiting)
        <meta http-equiv="refresh" content="5">
    @elseif ($state === \App\Models\Payment::STATUS_COMPLETED && $backUrl)
        <meta http-equiv="refresh" content="6;url={{ $backUrl }}">
    @endif
    <title>{{ $title }} · LibControl</title>
    <link rel="icon" href="{{ asset(config('libcontrol.brand.default_favicon')) }}">
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;background:#f1f5fb;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:#0f172a}
        .card{width:100%;max-width:440px;background:#fff;border-radius:18px;box-shadow:0 20px 50px -20px rgba(8,45,112,.35);overflow:hidden}
        .head{background:#082D70;padding:18px 24px;display:flex;align-items:center;gap:10px}
        .head img{height:28px}
        .body{padding:28px 24px;text-align:center}
        .icon{width:64px;height:64px;border-radius:50%;margin:0 auto 14px;display:flex;align-items:center;justify-content:center}
        .ok{background:#dcfce7;color:#15803d}.fail{background:#fee2e2;color:#b91c1c}.wait{background:#fef3c7;color:#b45309}
        h1{font-size:22px;margin:0 0 6px;color:#082D70}
        p{margin:0;color:#475569;font-size:14px;line-height:1.55}
        dl{margin:20px 0 0;padding:14px 16px;background:#f8fafc;border-radius:12px;text-align:left;font-size:13px}
        dl div{display:flex;justify-content:space-between;gap:12px;padding:5px 0}
        dt{color:#64748b}dd{margin:0;font-weight:600;color:#0f172a;text-align:right;word-break:break-all}
        .btn{display:block;margin-top:22px;padding:12px 16px;border-radius:10px;background:#ffc800;color:#082D70;font-weight:700;text-decoration:none}
        .btn:hover{filter:brightness(.96)}
        .muted{margin-top:14px;font-size:12px;color:#94a3b8}
        .spin{width:28px;height:28px;border:3px solid currentColor;border-right-color:transparent;border-radius:50%;animation:s 1s linear infinite}
        @keyframes s{to{transform:rotate(360deg)}}
    </style>
</head>
<body>
    <main class="card">
        <div class="head">
            <img src="{{ asset('logo/png-background/white-icon-only.png') }}" alt="">
            <span style="color:#fff;font-weight:700;font-size:18px">LibControl</span>
        </div>
        <div class="body">
            @if ($state === \App\Models\Payment::STATUS_COMPLETED)
                <div class="icon ok"><svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
            @elseif ($state === \App\Models\Payment::STATUS_FAILED)
                <div class="icon fail"><svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></div>
            @elseif ($stillWaiting)
                <div class="icon wait"><span class="spin"></span></div>
            @else
                <div class="icon wait"><svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v5m0 3h.01"/></svg></div>
            @endif

            <h1>{{ $title }}</h1>
            <p>
                @if ($state === \App\Models\Payment::STATUS_COMPLETED)
                    Thank you! Your payment is confirmed and has been applied to your library.
                @elseif ($state === \App\Models\Payment::STATUS_FAILED)
                    No money was taken, or any amount debited will be refunded automatically by your bank. You can try again from LibControl.
                @elseif ($stillWaiting)
                    We are waiting for PhonePe to confirm. This page refreshes automatically — please don't pay again.
                @else
                    PhonePe has not confirmed this payment. If you left without paying, nothing was charged and you can start again from LibControl. If money was debited, it will be applied automatically once PhonePe confirms — please don't pay twice.
                @endif
            </p>

            <dl>
                <div><dt>Item</dt><dd>{{ $payment->item_name }}</dd></div>
                <div><dt>Amount</dt><dd>{{ $payment->amountLabel() }}</dd></div>
                <div><dt>Reference</dt><dd>{{ $payment->merchant_order_id }}</dd></div>
                @if ($payment->gateway_transaction_id)
                    <div><dt>PhonePe transaction</dt><dd>{{ $payment->gateway_transaction_id }}</dd></div>
                @endif
            </dl>

            @if ($pending && ! $stillWaiting)
                <a class="btn" style="background:#fff;border:1px solid #cbd5e1" href="{{ url()->current() }}">Check again</a>
            @endif
            @if ($backUrl)
                <a class="btn" href="{{ $backUrl }}">Back to LibControl</a>
                @if ($state === \App\Models\Payment::STATUS_COMPLETED)
                    <p class="muted">Taking you back automatically…</p>
                @endif
            @else
                <p class="muted">You can close this tab and return to LibControl.</p>
            @endif
            <p class="muted">Need help? WhatsApp {{ config('libcontrol.support.whatsapp') }} or email {{ config('libcontrol.support.email') }}</p>
        </div>
    </main>
</body>
</html>
