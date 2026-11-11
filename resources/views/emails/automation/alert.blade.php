<x-mail::message>
# {{ $headline }}

{!! nl2br(e($bodyText)) !!}

@if (! empty($context))
<x-mail::panel>
@foreach ($context as $label => $value)
**{{ $label }}:** {{ $value }}  
@endforeach
</x-mail::panel>
@endif

@if ($ctaLabel && $ctaUrl)
<x-mail::button :url="$ctaUrl">
{{ $ctaLabel }}
</x-mail::button>
@endif

Thanks,  
{{ $libraryName }}

{{ config('libcontrol.product.byline') }}
</x-mail::message>
