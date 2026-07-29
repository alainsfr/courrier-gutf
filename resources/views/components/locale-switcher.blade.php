@php
    $current = app()->getLocale();
@endphp

<div {{ $attributes->merge(['class' => 'relative']) }} data-lang-switcher>
    <button type="button"
            data-lang-toggle
            aria-expanded="false"
            aria-haspopup="true"
            aria-label="{{ __('Language') }}"
            class="inline-flex h-10 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2.5 text-xs font-bold text-[#17384b] hover:bg-slate-50">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4 text-slate-500">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
        </svg>
        <span>{{ strtoupper($current) }}</span>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-3.5 w-3.5 text-slate-400">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <div data-lang-dropdown
         class="absolute right-0 z-50 mt-2 hidden w-36 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
        <a href="{{ route('lang.switch', 'fr') }}"
           class="flex items-center justify-between px-3 py-2 text-sm no-underline {{ $current === 'fr' ? 'bg-[#17384b]/10 font-semibold text-[#17384b]' : 'text-slate-700 hover:bg-slate-50' }}">
            <span>{{ __('Français') }}</span>
            <span class="text-[11px] font-bold">FR</span>
        </a>
        <a href="{{ route('lang.switch', 'en') }}"
           class="flex items-center justify-between px-3 py-2 text-sm no-underline {{ $current === 'en' ? 'bg-[#17384b]/10 font-semibold text-[#17384b]' : 'text-slate-700 hover:bg-slate-50' }}">
            <span>English</span>
            <span class="text-[11px] font-bold">EN</span>
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
    })();
</script>
