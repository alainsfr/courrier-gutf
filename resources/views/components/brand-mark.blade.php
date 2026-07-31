@props([
    'logoUrl' => null,
    'label' => 'COURRIER GUTF',
    'tagline' => null,
    'primary' => '#17384b',
    'compact' => false,
    'inverse' => false,
])

@php
    $logoUrl = $logoUrl ?: null;
    $tagline = filled($tagline) ? (string) $tagline : null;
    $size = $compact ? 'h-9 w-9' : 'h-10 w-10';
    $nameClass = $inverse ? 'text-white' : '';
    $tagClass = $inverse ? 'text-white/65' : 'text-slate-400';
    $circleClass = $inverse
        ? 'bg-white/15 ring-1 ring-white/20'
        : 'bg-slate-50 ring-1 ring-slate-200/80';
@endphp

<div {{ $attributes->merge(['class' => 'flex min-w-0 items-center gap-3']) }}>
    @if ($logoUrl)
        <span class="inline-flex {{ $size }} shrink-0 items-center justify-center overflow-hidden rounded-full {{ $circleClass }} p-1.5">
            <img
                src="{{ $logoUrl }}"
                alt=""
                class="h-full w-full object-contain"
            >
        </span>
    @else
        <span class="inline-flex {{ $size }} shrink-0 items-center justify-center rounded-full text-sm font-bold text-white"
              style="background: {{ $primary }}">
            {{ strtoupper(\Illuminate\Support\Str::substr($label, 0, 1)) }}
        </span>
    @endif

    <div class="min-w-0 flex-1">
        <div class="truncate text-sm font-semibold tracking-wide {{ $nameClass }}" @if (! $inverse) style="color: {{ $primary }}" @endif>
            {{ $label }}
        </div>
        @if ($tagline)
            <div class="truncate text-[11px] {{ $tagClass }}">
                {{ \Illuminate\Support\Str::limit($tagline, $compact ? 28 : 36) }}
            </div>
        @endif
    </div>
</div>
