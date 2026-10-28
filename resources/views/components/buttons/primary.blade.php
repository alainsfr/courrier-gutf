@props([
    'type' => 'submit',
    'href' => null,
    'tag' => null,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none no-underline';
    $variant = 'bg-[var(--gutf-primary)] hover:brightness-90 text-white focus:ring-[var(--gutf-primary)] border border-transparent';
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
