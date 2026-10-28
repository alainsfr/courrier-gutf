@props([
    'type' => 'submit',
    'href' => null,
    'tag' => null,
])

@php
    $base = 'ui-button';
    $variant = 'ui-button-primary';
    $classes = trim($base.' '.$variant);
    $el = $tag ?? ($href ? 'a' : 'button');
@endphp

@if($el === 'a')
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
