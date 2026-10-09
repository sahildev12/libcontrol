<x-mail::message>
# New growth request

**{{ $order->library_name ?: 'A library' }}** has requested **{{ $order->item_name }}** from Grow My Library.

<x-mail::table>
| Request | |
|:--|:--|
| {{ $typeLabel }} | {{ $order->item_name }} |
| Price | {{ $catalog['price_label'] ?? '—' }} |
| Requested on | {{ $order->created_at?->format('d M Y, h:i A') }} |
| Order ID | {{ $order->uuid }} |
</x-mail::table>

<x-mail::table>
| Client | |
|:--|:--|
| Name | {{ $order->contact_name ?: '—' }} |
| Email | {{ $order->contact_email ?: '—' }} |
| Phone | {{ $phone ? '+91 '.$phone : '—' }} |
| Library | {{ $order->library_name ?: '—' }} |
| Library code | {{ $order->library_code ?: '—' }} |
| Branch | {{ $branch?->name ?: '—' }} |
| Branch address | {{ $branch?->address ?: '—' }} |
| Domain | {{ $order->deployment_domain ?: '—' }} |
</x-mail::table>

@if (filled($order->message))
**Message from the client**

> {{ $order->message }}
@endif

@if ($whatsappUrl)
<x-mail::button :url="$whatsappUrl" color="success">
WhatsApp the client
</x-mail::button>
@endif

Reply to this email to contact {{ $order->contact_name ?: 'the client' }} directly.

{{ config('libcontrol.product.byline') }}
</x-mail::message>
