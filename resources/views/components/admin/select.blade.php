@props([
    'wrapperClass' => 'relative w-full',
])

<div @class([$wrapperClass])>
    <select {{ $attributes->merge(['class' => 'admin-select']) }}>
        {{ $slot }}
    </select>
</div>
