<x-mail::message>
# Password reset

@if ($recipientName)
Hello {{ $recipientName }},
@else
Hello,
@endif

We received a request to reset the password for your {{ config('libcontrol.product.name') }} account (a product by {{ config('libcontrol.product.company') }}.com). Use the button below to choose a new password.

<x-mail::button :url="$resetUrl">
Reset password
</x-mail::button>

This link expires in {{ $expireMinutes }} minutes. If you did not request a password reset, you can ignore this email — your password will stay the same.

If the button does not work, copy and paste this link into your browser:

{{ $resetUrl }}

Thanks,<br>
The {{ config('libcontrol.product.name') }} team<br>
<span style="color: #6b7280;">{{ config('libcontrol.product.byline') }}</span>
</x-mail::message>
