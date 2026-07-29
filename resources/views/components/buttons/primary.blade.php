@props([
    'type' => 'submit',
    'href' => null,
    'tag' => null,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 px-4 py-2 rounded-md text-sm font-medium transition shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none no-underline';
    $variant = 'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500 border border-transparent';
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
