@php
    $brand = $brand ?? [];
    $appLabel = $brand['app_name'] ?? config('app.name', 'COURRIER GUTF');
    $tagline = $brand['app_tagline'] ?? ($brand['ministry'] ?? '');
    $primary = $brand['primary_color'] ?? '#17384b';
    $accent = $brand['accent_color'] ?? '#dc633a';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $appLabel }}</title>
    @if (! empty($brandFaviconUrl))<link rel="icon" href="{{ $brandFaviconUrl }}">@endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:root { --gutf-primary: {{ $primary }}; --gutf-accent: {{ $accent }}; }</style>
</head>
<body class="ui-auth antialiased" data-error-fields='@json($errors->keys())'>
    <a href="#auth-content" class="ui-skip-link">{{ __('ui.skip') }}</a>
    <div class="ui-auth-shell">
        <aside class="ui-auth-aside">
            <x-brand-mark :logo-url="$brandLogoUrl ?? null" :label="$appLabel" :tagline="$tagline" :primary="$primary" inverse />
            <div>
                <h1>{{ __('ui.auth_title') }}</h1>
                <p class="mt-5">{{ __('ui.auth_intro') }}</p>
                <ol class="ui-auth-steps mt-8">
                    @foreach (['one', 'two', 'three'] as $step)
                        <li><span>{{ $loop->iteration }}</span>{{ __('ui.auth_step_'.$step) }}</li>
                    @endforeach
                </ol>
            </div>
            <small class="text-white/65">© {{ date('Y') }} · {{ $appLabel }}</small>
        </aside>
        <main id="auth-content" class="ui-auth-main" tabindex="-1">
            <div class="ui-auth-language"><x-locale-switcher /></div>
            <div class="ui-auth-content">
                <div class="mb-8 lg:hidden"><x-brand-mark :logo-url="$brandLogoUrl ?? null" :label="$appLabel" :tagline="$tagline" :primary="$primary" compact /></div>
                {{ $slot }}
            </div>
        </main>
    </div>
    <x-idle-session />
    <x-confirm-dialog />
</body>
</html>
