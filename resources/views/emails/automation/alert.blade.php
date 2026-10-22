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

Thanks,<br>
{{ $libraryName }}<br>
<span style="color: #6b7280;">{{ config('libcontrol.product.byline') }}</span>
</x-mail::message>
