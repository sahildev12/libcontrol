@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('libspace.product.company_url')])
{{ config('libspace.product.name') }}
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
© {{ date('Y') }} {{ config('libspace.product.company') }}.com · {{ config('libspace.product.byline') }}
@endcomponent
@endslot
@endcomponent
