<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="robots" content="noindex, nofollow">
    <title>Secure payment · LibControl</title>
    <link rel="icon" href="{{ asset(config('libcontrol.brand.default_favicon')) }}">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: #f1f5fb;
            color: #0f172a;
        }
        .shell {
            max-width: 480px;
            margin: 0 auto;
            padding: 24px 16px;
        }
        .head {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }
        .head img { height: 28px; }
        .head span { font-weight: 700; color: #082D70; font-size: 18px; }
        .card {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 16px 40px -24px rgba(8, 45, 112, .35);
        }
        .muted { color: #64748b; font-size: 14px; line-height: 1.5; margin: 0 0 12px; }
        #phonepe-checkout { min-height: 420px; }
        .fallback { margin-top: 16px; }
        .btn {
            display: inline-block;
            padding: 12px 16px;
            border-radius: 10px;
            background: #082D70;
            color: #fff;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="head">
            <img src="{{ asset('logo/png-background/white-icon-only.png') }}" alt="">
            <span>LibControl</span>
        </div>
        <div class="card">
            <p class="muted">
                Complete payment on <strong>libcontrol.in</strong> (PhonePe secure checkout).
                Amount: <strong>{{ $payment->amountLabel() }}</strong> — {{ $payment->item_name }}.
            </p>
            <div id="phonepe-checkout" aria-live="polite"></div>
            <div class="fallback" id="fallback" hidden>
                <p class="muted">If the payment form does not load, continue on PhonePe.</p>
                <button type="button" class="btn" id="open-phonepe">Continue to PhonePe</button>
            </div>
        </div>
    </div>

    <script src="{{ $checkoutScript }}"></script>
    <script>
        (function () {
            const tokenUrl = @json($phonePeUrl);
            const returnUrl = @json(route('payments.return', $payment->uuid));
            const fallback = document.getElementById('fallback');
            const openBtn = document.getElementById('open-phonepe');

            function goFullPage() {
                window.location.replace(tokenUrl);
            }

            openBtn?.addEventListener('click', goFullPage);

            function afterClose(response) {
                if (response === 'USER_CANCEL' || response === 'CONCLUDED') {
                    window.location.replace(returnUrl);
                }
            }

            function startCheckout() {
                if (window.PhonePeCheckout && typeof window.PhonePeCheckout.transact === 'function') {
                    try {
                        window.PhonePeCheckout.transact({
                            tokenUrl,
                            type: 'IFRAME',
                            callback: afterClose,
                        });
                        return;
                    } catch (e) {
                        /* fall through */
                    }
                }
                fallback.hidden = false;
                goFullPage();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', startCheckout);
            } else {
                startCheckout();
            }
        })();
    </script>
</body>
</html>
