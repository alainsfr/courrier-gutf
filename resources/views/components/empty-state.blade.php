@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-4 py-10 text-center']) }}>
    <div class="ui-empty-icon mb-3 flex h-12 w-12 items-center justify-center">
        @isset($icon)
            {{ $icon }}
        @else
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m16.5 0h-16.5m16.5 0V6a2.25 2.25 0 00-2.25-2.25H6A2.25 2.25 0 003.75 6v1.5" />
            </svg>
        @endisset
    </div>

    <h3 class="ui-empty-title m-0">{{ $title }}</h3>

    @if($description)
        <p class="mb-0 mt-1 max-w-sm text-sm text-slate-500">{{ $description }}</p>
    @endif

    @if($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
