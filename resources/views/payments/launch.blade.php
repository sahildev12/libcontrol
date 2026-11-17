<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="origin">
    <meta name="robots" content="noindex, nofollow">
    <title>Redirecting to PhonePe…</title>
    <style>
        html, body { margin: 0; min-height: 100%; background: #fff; }
    </style>
</head>
<body>
    {{-- Blank hop on the onboarded hub host so PhonePe sees libcontrol.in as referrer. --}}
    <script>
        window.location.replace(@json($phonePeUrl));
    </script>
    <noscript>
        <p style="font-family: system-ui, sans-serif; padding: 24px;">
            <a href="{{ $phonePeUrl }}">Continue to PhonePe</a>
        </p>
    </noscript>
</body>
</html>
