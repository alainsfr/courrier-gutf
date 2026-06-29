@php
    $isFr = (app()->getLocale() === 'fr');
    $inst = config('institution');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $inst['app_label'] }} - {{ $isFr ? "Accueil" : "Home" }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .hover-translate-up {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .hover-translate-up:hover {
            transform: translateY(-5px);
            box-shadow: 0 1rem 3rem rgba(0,0,0,.08) !important;
        }
    </style>
</head>
<body class="bg-gradient-to-b from-white via-slate-50 to-slate-100">

    {{-- 1. EN-TÊTE DE NAVIGATION --}}
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom py-3 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ url('/') }}" style="color: var(--gutf-primary);">
                <span class="d-inline-flex align-items-center justify-content-center rounded-3 shadow-sm"
                      style="width:34px;height:34px;background:var(--gutf-primary);color:#fff;font-weight:800; font-size: 1.1rem;">
                    G
                </span>
                <span class="letter-spacing-tight">{{ $inst['app_label'] }}</span>
            </a>

            <div class="d-flex align-items-center gap-3">
                <x-locale-switcher />

                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-gutf px-4 rounded-3 fw-semibold small shadow-sm">
                        {{ $isFr ? 'Mon Espace ➔' : 'My Workspace ➔' }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-gutf px-4 rounded-3 fw-semibold small shadow-sm">
                        {{ $isFr ? 'Connexion ➔' : 'Log in ➔' }}
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    {{-- 2. SECTION HERO (INTRODUCTION GUTF / MINISTÈRES) --}}
    <header class="py-5 bg-white border-bottom">
        <div class="container py-5">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill bg-light border border-slate-200 mb-3 shadow-sm">
                        <span class="small">🇨🇲</span>
                        <span class="fw-bold text-dark small text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">{{ $inst['country'] }}</span>
                    </div>

                    <h1 class="display-6 fw-bold mb-3" style="color: var(--gutf-primary); line-height: 1.25;">
                        @if($isFr)
                            Suivi des Courriers du Guichet Unique (GUTF)
                            <span class="d-block h4 text-muted mt-2 fw-normal" style="font-size: 1.15rem;">Bureau de l'Accueil, du Courrier et de Liaison (BACL)</span>
                        @else
                            One-Stop Shop (GUTF) Mail Tracking
                            <span class="d-block h4 text-muted mt-2 fw-normal" style="font-size: 1.15rem;">Reception, Mail and Liaison Bureau (RMLB)</span>
                        @endif
                    </h1>

                    <p class="text-muted leading-relaxed mb-4" style="max-width: 580px; font-size: 1.05rem;">
                        @if($isFr)
                            Bienvenue sur la plateforme applicative du <strong>{{ $inst['structure'] }}</strong>. Un outil de suivi et d'archivage des dossiers sous l'autorité conjointe du MINDCAF et du MINFI.
                        @else
                            Welcome to the application platform of the <strong>{{ $inst['structure_en'] }}</strong>. A tool for tracking and archiving files under the joint authority of MINDCAF and MINFI.
                        @endif
                    </p>

                    <div class="d-flex gap-2 flex-wrap mt-4">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-gutf px-4 py-2.5 rounded-3 fw-bold shadow-sm">
                                {{ $isFr ? 'Accéder à l\'application' : 'Access application' }}
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-gutf px-4 py-2.5 rounded-3 fw-bold shadow-sm">
                                {{ $isFr ? 'Se connecter au portail GUTF' : 'Log in to GUTF portal' }}
                            </a>
                        @endauth
                        <a href="#missions" class="btn btn-light border border-slate-200 px-4 py-2.5 rounded-3 fw-bold text-muted">
                            {{ $isFr ? 'Nos Missions' : 'Our Duties' }}
                        </a>
                    </div>
                </div>

                {{-- Tutelles Nationales connectées avec vos liens officiels --}}
                <div class="col-lg-5 col-md-8 mx-auto">
                    <div class="card border-0 shadow-lg rounded-4 p-4 bg-light" style="border-left: 5px solid var(--gutf-accent) !important;">
                        <div class="fw-bold text-dark mb-1">🏛️ {{ $isFr ? 'Tutelles Nationales' : 'National Oversight' }}</div>
                        <p class="text-muted small mb-3">Rapprochement inter-ministériel (MINDCAF - MINFI)</p>
                        
                        <div class="list-group list-group-flush rounded-3 overflow-hidden shadow-sm">
                            <div class="list-group-item d-flex align-items-center gap-3 p-3 bg-white border-bottom">
                                <span class="fs-4">🗺️</span>
                                <div style="min-width: 0; flex: 1;">
                                    <div class="fw-bold text-dark small" style="font-size: 0.85rem;">MINDCAF</div>
                                    <div class="text-muted small text-truncate" style="font-size: 0.75rem;">
                                        <a href="{{ $inst['mindcaf_url'] }}" target="_blank" class="text-decoration-none text-primary fw-semibold">
                                            {{ $isFr ? 'Ministère des Domaines et du Cadastre' : 'Ministry of State Property' }} ↗
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group-item d-flex align-items-center gap-3 p-3 bg-white">
                                <span class="fs-4">💵</span>
                                <div style="min-width: 0; flex: 1;">
                                    <div class="fw-bold text-dark small" style="font-size: 0.85rem;">MINFI</div>
                                    <div class="text-muted small text-truncate" style="font-size: 0.75rem;">
                                        <a href="{{ $inst['minfi_url'] }}" target="_blank" class="text-decoration-none text-primary fw-semibold">
                                            {{ $isFr ? 'Ministère des Finances' : 'Ministry of Finance' }} ↗
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- 3. LES 5 MISSIONS DU BACL / GUTF --}}
    <section class="py-5" id="missions">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="fw-bold text-dark mb-2">
                    {{ $isFr ? 'Missions d\'Accueil & de Liaison' : 'Welcoming & Liaison Duties' }}
                </h2>
                <p class="text-muted small mx-auto" style="max-width: 600px;">
                    {{ $isFr ? 'L’application de gestion de courrier a été développée pour matérialiser les 5 missions d’accueil et de liaison de la structure :' : 'The mail management application was developed to materialize the structure\'s 5 key welcoming and liaison duties:' }}
                </p>
            </div>

            <div class="row g-4 justify-content-center">
                {{-- Devoir 1 --}}
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-4 bg-white hover-translate-up">
                        <div class="card-body p-0">
                            <div class="d-inline-flex align-items-center justify-content-center bg-blue-50 rounded-4 mb-3" style="width: 48px; height: 48px; background: rgba(23, 56, 75, 0.08);">
                                <span class="fs-4">🤝</span>
                            </div>
                            <h5 class="fw-bold text-dark mb-2" style="font-size: 1.05rem;">
                                {{ $isFr ? '1. Accueil & Orientation' : '1. Welcoming & Guiding' }}
                            </h5>
                            <p class="text-muted small leading-relaxed mb-0">
                                {{ $isFr ? 'Assurer l’accueil physique, l’information claire et l’orientation des usagers déposant des pièces.' : 'Ensuring the welcoming, informing and guiding of users submitting files.' }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Devoir 2 --}}
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-4 bg-white hover-translate-up">
                        <div class="card-body p-0">
                            <div class="d-inline-flex align-items-center justify-content-center bg-orange-50 rounded-4 mb-3" style="width: 48px; height: 48px; background: rgba(220, 99, 58, 0.08);">
                                <span class="fs-4">📥</span>
                            </div>
                            <h5 class="fw-bold text-dark mb-2" style="font-size: 1.05rem;">
                                {{ $isFr ? '2. Réception & Ventilation' : '2. Reception & Distribution' }}
                            </h5>
                            <p class="text-muted small leading-relaxed mb-0">
                                {{ $isFr ? 'Prendre en charge la réception rigoureuse, le traitement et la ventilation interne des courriers.' : 'Taking charge of the receipt, handling and distribution of mails.' }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Devoir 3 --}}
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-4 bg-white hover-translate-up">
                        <div class="card-body p-0">
                            <div class="d-inline-flex align-items-center justify-content-center bg-emerald-50 rounded-4 mb-3" style="width: 48px; height: 48px; background: rgba(34, 197, 94, 0.08);">
                                <span class="fs-4">🔎</span>
                            </div>
                            <h5 class="fw-bold text-dark mb-2" style="font-size: 1.05rem;">
                                {{ $isFr ? '3. Vérification de régularité' : '3. Checking Regularity' }}
                            </h5>
                            <p class="text-muted small leading-relaxed mb-0">
                                {{ $isFr ? 'Vérifier la conformité et la régularité stricte des pièces et dossiers fonciers déposés.' : 'Verifying the correctness of documents and land files submitted.' }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Devoir 4 --}}
                <div class="col-lg-4 col-md-6 mt-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-4 bg-white hover-translate-up">
                        <div class="card-body p-0">
                            <div class="d-inline-flex align-items-center justify-content-center bg-purple-50 rounded-4 mb-3" style="width: 48px; height: 48px; background: rgba(111, 66, 193, 0.08);">
                                <span class="fs-4">⏱️</span>
                            </div>
                            <h5 class="fw-bold text-dark mb-2" style="font-size: 1.05rem;">
                                {{ $isFr ? '4. Relance des Services' : '4. File Follow-up' }}
                            </h5>
                            <p class="text-muted small leading-relaxed mb-0">
                                {{ $isFr ? 'Effectuer des relances d’alerte auprès des services métiers pour respecter les délais légaux.' : 'Following up services to ensure the rapid processing of submitted files.' }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Devoir 5 --}}
                <div class="col-lg-4 col-md-6 mt-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-4 bg-white hover-translate-up">
                        <div class="card-body p-0">
                            <div class="d-inline-flex align-items-center justify-content-center bg-danger-subtle rounded-4 mb-3" style="width: 48px; height: 48px; background: rgba(220, 53, 69, 0.08);">
                                <span class="fs-4">🔔</span>
                            </div>
                            <h5 class="fw-bold text-dark mb-2" style="font-size: 1.05rem;">
                                {{ $isFr ? '5. Notification des Actes' : '5. Notifying Deeds' }}
                            </h5>
                            <p class="text-muted small leading-relaxed mb-0">
                                {{ $isFr ? 'Notifier aux usagers finaux les actes, les droits fonciers et divers documents produits.' : 'Notifying deeds, land rights and various documents produced by the One-Stop Shop.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 4. FOOTER INSTITUTIONNEL CONFORME --}}
    <footer class="bg-white border-top py-5 text-center text-muted small">
        <div class="container">
            <div class="fw-bold text-dark mb-1">
                <a href="{{ $inst['mindcaf_url'] }}" target="_blank" class="text-decoration-none text-dark hover:underline">
                    Ministère des Domaines, du Cadastre et des Affaires Foncières (MINDCAF)
                </a> 
                &amp; 
                <a href="{{ $inst['minfi_url'] }}" target="_blank" class="text-decoration-none text-dark hover:underline">
                    Ministère des Finances (MINFI)
                </a>
            </div>
            <div class="mb-3">{{ $isFr ? $inst['structure'] : $inst['structure_en'] }}</div>
            <div class="mb-4 text-uppercase fw-semibold" style="letter-spacing: 1px; font-size: 0.8rem; color: var(--gutf-accent);">
                🇨🇲 {{ $inst['motto'] }} 🇨🇲
            </div>
            <hr class="my-4 border-slate-100" style="max-width: 600px; margin: 0 auto;">
            <p class="mb-0 text-muted" style="max-width: 700px; margin: 0 auto; font-size: 0.8rem; line-height: 1.5;">
                {{ $inst['confidentiality'] }}
            </p>
            <div class="mt-4" style="font-size: 0.75rem;">
                &copy; {{ date('Y') }} - {{ $inst['app_label'] }}
            </div>
        </div>
    </footer>

</body>
</html>