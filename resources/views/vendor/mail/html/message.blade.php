<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('libcontrol.product.company_url')">
{{ config('libcontrol.product.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} {{ config('libcontrol.product.company') }}.com · {{ config('libcontrol.product.byline') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
