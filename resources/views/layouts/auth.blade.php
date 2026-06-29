@php
    $inst = config('institution');
    $isFr = (app()->getLocale() === 'fr');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $inst['app_label'] }} - Connexion</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="auth-shell">
    
    {{-- Volet gauche de présentation --}}
    <aside class="auth-left">
        <div class="auth-brand">
            <div class="auth-brand-badge">G</div>
            <div>
                <div class="fw-bold" style="font-size:1.1rem; line-height: 1.2;">{{ $inst['app_label'] }}</div>
                <div class="small" style="opacity:.85;">
                    {{ $isFr ? 'Guichet Unique de Traitement' : 'One-Stop Shop Registry' }}
                </div>
            </div>
        </div>

        <div style="max-width:520px;">
            {{-- Badge national officiel --}}
            <div class="d-inline-flex align-items-center gap-1.5 px-3 py-1.5 rounded-pill bg-white text-dark small font-bold text-uppercase mb-3" style="font-size: 0.7rem; letter-spacing: 0.5px; color: var(--gutf-primary) !important;">
                🇨🇲 MINDCAF / MINFI
            </div>

            <h1 class="fw-bold mb-3" style="font-size: 1.85rem; line-height: 1.25;">
                {{ __('auth_ui.hero_title') }}
            </h1>
            
            <p style="opacity:.92; line-height:1.55;" class="small">
                @if($isFr)
                    Centralisez la réception, la vérification de régularité, le traitement et la ventilation du courrier au sein des Conservations Foncières sous l'autorité conjointe du MINDCAF et du MINFI.
                @else
                    Centralize the receipt, compliance check, processing and distribution of mail within Land Registries under the joint authority of MINDCAF and MINFI.
                @endif
            </p>

            <div style="margin-top:24px; display:flex; gap:10px; flex-wrap:wrap;">
                <span class="badge text-bg-light">{{ __('auth_ui.badge_traceability') }}</span>
                <span class="badge text-bg-light">SLA (72h Max)</span>
                <span class="badge text-bg-light">MINDCAF / MINFI</span>
                <span class="badge text-bg-light">Bilingue (FR/EN)</span>
            </div>
        </div>

        <div style="opacity:.9; font-size:.9rem;">
            © {{ date('Y') }} - {{ $inst['app_label'] }}
        </div>
    </aside>

    {{-- Volet droit : Espace formulaire --}}
    <main class="flex-grow-1 d-flex align-items-center justify-content-center p-3 p-lg-5">
        <div class="auth-card">
            @yield('content')
        </div>
    </main>
</div>
</body>
</html>