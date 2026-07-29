@props([])

@php
    $current = app()->getLocale();
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5 rounded-full border border-slate-200 bg-slate-50 p-0.5']) }}
     role="group" aria-label="Language switcher">
    <form method="POST" action="{{ route('locale.set') }}" class="m-0">
        @csrf
        <input type="hidden" name="locale" value="fr">
        <button type="submit"
                class="rounded-full px-2.5 py-1 text-[11px] font-semibold transition {{ $current === 'fr' ? 'bg-[#17384b] text-white' : 'text-slate-500 hover:text-slate-800' }}">
            FR
        </button>
    </form>
    <form method="POST" action="{{ route('locale.set') }}" class="m-0">
        @csrf
        <input type="hidden" name="locale" value="en">
        <button type="submit"
                class="rounded-full px-2.5 py-1 text-[11px] font-semibold transition {{ $current === 'en' ? 'bg-[#17384b] text-white' : 'text-slate-500 hover:text-slate-800' }}">
            EN
        </button>
    </form>
</div>
