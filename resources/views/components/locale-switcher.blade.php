@props([
    'size' => 'sm', // sm | md
])

@php
    $current = app()->getLocale();
    $btnSize = $size === 'md' ? '' : 'btn-sm';
@endphp

<div class="lang-switch" role="group" aria-label="Language switcher">
    <form method="POST" action="{{ route('locale.set') }}">
        @csrf
        <input type="hidden" name="locale" value="fr">
        <button type="submit"
            class="btn {{ $btnSize }} {{ $current === 'fr' ? 'btn-lang-active' : 'btn-lang' }}">
            FR
        </button>
    </form>

    <form method="POST" action="{{ route('locale.set') }}">
        @csrf
        <input type="hidden" name="locale" value="en">
        <button type="submit"
            class="btn {{ $btnSize }} {{ $current === 'en' ? 'btn-lang-active' : 'btn-lang' }}">
            EN
        </button>
    </form>
</div>
