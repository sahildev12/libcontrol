<x-mail::message>
# Plan expiring soon

Hello {{ $booking->student?->name }},

Your seat plan at **{{ $libraryName }}** will expire on **{{ $booking->plan_expiry_date?->format('M d, Y') }}** ({{ $daysRemaining }} day(s) from now).

**Student code:** {{ $booking->student?->student_code }}  
**Hall / seat:** {{ $booking->seat?->hall?->name }} · Seat {{ $booking->seat?->seat_number }}  
**Fee type:** {{ $booking->fee_type }}

Please contact the library desk to renew your plan.

Thanks,<br>
{{ $libraryName }}<br>
<span style="color: #6b7280;">{{ config('libcontrol.product.byline') }}</span>
</x-mail::message>
