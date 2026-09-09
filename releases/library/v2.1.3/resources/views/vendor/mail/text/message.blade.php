@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('libcontrol.product.company_url')])
{{ config('libcontrol.product.name') }}
@endcomponent
@endslot

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
@slot('subcopy')
@component('mail::subcopy')
{{ $subcopy }}
@endcomponent
@endslot
@endisset

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
© {{ date('Y') }} {{ config('libcontrol.product.company') }}.com · {{ config('libcontrol.product.byline') }}
@endcomponent
@endslot
@endcomponent
