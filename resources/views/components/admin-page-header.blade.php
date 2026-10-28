@props([
    'title' => null,
    'subtitle' => null,
])

<div class="flex flex-wrap items-center justify-between gap-3">
    <div class="min-w-0">
        <a href="{{ route('admin.index') }}"
           class="mb-1 inline-flex items-center gap-1 text-xs font-semibold text-slate-500 no-underline hover:text-[var(--gutf-primary)]">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
            {{ __('nav.administration') }}
        </a>
        @if ($title)
            <h1 class="truncate text-lg font-bold text-slate-900">{{ $title }}</h1>
        @endif
        @if ($subtitle)
            <p class="mb-0 text-sm text-slate-500">{{ $subtitle }}</p>
        @elseif ($slot->isNotEmpty())
            <div class="text-sm text-slate-500">{{ $slot }}</div>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
