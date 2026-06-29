<!DOCTYPE html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'COURRIER GUTF') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="auth-shell">
    <aside class="auth-left">
        <div class="auth-brand">
            <div class="auth-brand-badge">G</div>
            <div>
                <div style="font-weight:800; font-size:1.1rem;">{{ config('app.name', 'COURRIER GUTF') }}</div>
                <div style="opacity:.9;">{{ __('auth_ui.tagline') }}</div>
            </div>
        </div>

        <div style="max-width:520px;">
            <h1 style="font-weight:900; margin-bottom:10px;">{{ __('auth_ui.hero_title') }}</h1>
            <p style="opacity:.92; line-height:1.55;">
                {{ __('auth_ui.hero_text') }}
            </p>

            <div style="margin-top:18px; display:flex; gap:10px; flex-wrap:wrap;">
                <span class="badge text-bg-light">{{ __('auth_ui.badge_traceability') }}</span>
                <span class="badge text-bg-light">{{ __('auth_ui.badge_sla') }}</span>
                <span class="badge text-bg-light">{{ __('auth_ui.badge_security') }}</span>
                <span class="badge text-bg-light">{{ __('auth_ui.badge_bilingual') }}</span>
            </div>
        </div>

        <div style="opacity:.9; font-size:.9rem;">
            © {{ date('Y') }} {{ config('app.name', 'COURRIER GUTF') }}
        </div>
    </aside>

    <main class="flex-grow-1 d-flex align-items-center justify-content-center p-3 p-lg-5">
        <div class="auth-card">
            {{ $slot }}
        </div>
    </main>
</div>
</body>
</html>