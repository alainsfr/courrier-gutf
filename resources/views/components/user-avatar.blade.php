@props([
    'user' => null,
    'size' => 'md',
    'name' => null,
    'avatarUrl' => null,
])

@php
    $resolvedName = $name ?? ($user?->name ?? 'U');
    $url = $avatarUrl ?? ($user?->avatarUrl());
    $initials = $user?->initials();
    if (! $initials) {
        $words = preg_split('/\s+/', trim((string) $resolvedName)) ?: [];
        $initials = strtoupper(
            substr($words[0] ?? 'U', 0, 1).
            (isset($words[1]) ? substr($words[1], 0, 1) : '')
        );
    }

    $sizeClass = match ($size) {
        'sm' => 'h-8 w-8 text-[10px]',
        'lg' => 'h-14 w-14 text-base',
        'xl' => 'h-20 w-20 text-xl',
        default => 'h-10 w-10 text-xs',
    };
@endphp

@if ($url)
    <img src="{{ $url }}"
         alt="{{ $resolvedName }}"
         {{ $attributes->merge(['class' => "inline-block {$sizeClass} rounded-full object-cover ring-2 ring-white"]) }}>
@else
    <span {{ $attributes->merge(['class' => "inline-flex {$sizeClass} items-center justify-center rounded-full bg-[var(--gutf-accent,#dc633a)] font-bold text-white"]) }}>
        {{ $initials }}
    </span>
@endif
