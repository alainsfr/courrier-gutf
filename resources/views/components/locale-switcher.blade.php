@php
    $current = app()->getLocale();
@endphp

<div {{ $attributes->merge(['class' => 'relative']) }} data-lang-switcher>
    <button type="button"
            data-lang-toggle
            aria-expanded="false"
            aria-haspopup="true"
            aria-label="{{ __('ui.language') }}"
            class="inline-flex items-center gap-2 ui-card py-1.5 pl-1.5 pr-2.5 text-left hover:bg-slate-50">
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-soft text-[11px] font-bold text-brand">
            {{ strtoupper($current) }}
        </span>
        <span class="hidden text-sm font-semibold text-brand sm:inline">Lang</span>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4 text-slate-400">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <div data-lang-dropdown
         class="absolute right-0 z-50 mt-2 hidden w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white py-1 shadow-lg">
        <div class="border-b border-slate-100 px-4 py-3">
            <div class="text-sm font-semibold text-slate-800">{{ __('ui.language') }}</div>
            <div class="text-xs text-slate-400">FR / EN</div>
        </div>
        <a href="{{ route('lang.switch', 'fr') }}"
           class="flex items-center justify-between gap-2 px-4 py-2.5 text-sm no-underline {{ $current === 'fr' ? 'bg-slate-50 font-semibold text-brand' : 'text-slate-700 hover:bg-slate-50' }}">
            <span>{{ __('Français') }}</span>
            <span class="text-[11px] font-bold text-slate-400">FR</span>
        </a>
        <a href="{{ route('lang.switch', 'en') }}"
           class="flex items-center justify-between gap-2 px-4 py-2.5 text-sm no-underline {{ $current === 'en' ? 'bg-slate-50 font-semibold text-brand' : 'text-slate-700 hover:bg-slate-50' }}">
            <span>English</span>
            <span class="text-[11px] font-bold text-slate-400">EN</span>
        </a>
    </div>
</div>

<script>
    (() => {
        const root = document.currentScript.previousElementSibling;
        if (!root || !root.hasAttribute('data-lang-switcher')) return;
        const toggle = root.querySelector('[data-lang-toggle]');
        const dropdown = root.querySelector('[data-lang-dropdown]');
        if (!toggle || !dropdown) return;

        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const open = !dropdown.classList.contains('hidden');
            dropdown.classList.toggle('hidden', open);
            toggle.setAttribute('aria-expanded', open ? 'false' : 'true');
        });

        document.addEventListener('click', () => {
            dropdown.classList.add('hidden');
            toggle.setAttribute('aria-expanded', 'false');
        });
        root.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape' || toggle.getAttribute('aria-expanded') !== 'true') return;
            dropdown.classList.add('hidden');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.focus();
        });
    })();
</script>
