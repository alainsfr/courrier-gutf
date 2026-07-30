@php
    $isFr = app()->getLocale() === 'fr';
    $inst = config('institution');
    $brand = $brand ?? [];
    $appLabel = $brand['app_name'] ?? ($inst['app_label'] ?? 'COURRIER GUTF');
    $tagline = $brand['app_tagline'] ?? ($brand['ministry'] ?? 'MINDCAF · MINFI');
    $primary = $brand['primary_color'] ?? '#17384b';
    $accent = $brand['accent_color'] ?? '#dc633a';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $appLabel }} — {{ __('auth_ui.sign_in') }}</title>
    @if (! empty($brandFaviconUrl))
        <link rel="icon" href="{{ $brandFaviconUrl }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,650&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { --gutf-primary: {{ $primary }}; --gutf-accent: {{ $accent }}; }
        .font-display { font-family: "Fraunces", Georgia, serif; }
        .font-ui { font-family: "Outfit", system-ui, sans-serif; }
    </style>
</head>
<body class="font-ui min-h-screen bg-white text-slate-900 antialiased">
    <div class="grid min-h-screen lg:grid-cols-2">

        {{-- Panneau institutionnel (desktop uniquement) --}}
        <aside class="relative hidden flex-col justify-between overflow-hidden px-12 py-12 text-white lg:flex" style="background: {{ $primary }}">
            <div class="pointer-events-none absolute inset-0 opacity-30" aria-hidden="true"
                 style="background-image: radial-gradient(rgba(255,255,255,.14) 1px, transparent 1px); background-size: 22px 22px;"></div>
            <div class="pointer-events-none absolute -bottom-24 -right-16 h-72 w-72 rounded-full blur-3xl" style="background: color-mix(in srgb, {{ $accent }} 35%, transparent)" aria-hidden="true"></div>

            <div class="relative z-10 flex items-center gap-3">
                @if (! empty($brandLogoUrl))
                    <img src="{{ $brandLogoUrl }}" alt="{{ $appLabel }}" class="h-10 w-10 rounded-xl bg-white/10 object-contain p-1">
                @else
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-sm font-bold backdrop-blur">
                        {{ strtoupper(substr($appLabel, 0, 1)) }}
                    </span>
                @endif
                <div>
                    <div class="text-sm font-semibold tracking-wide">{{ $appLabel }}</div>
                    <div class="text-xs text-white/65">{{ \Illuminate\Support\Str::limit($tagline, 40) }}</div>
                </div>
            </div>

            <div class="relative z-10 max-w-md">
                <h1 class="font-display text-4xl font-medium leading-tight tracking-tight xl:text-5xl">
                    {{ $isFr ? 'Bienvenue.' : 'Welcome.' }}
                </h1>
                <p class="mt-4 text-base leading-relaxed text-white/75">
                    {{ $isFr
                        ? 'Connectez-vous pour traiter et suivre les dossiers du Guichet Unique du Foncier.'
                        : 'Sign in to process and track dossiers at the Land One-Stop Shop.' }}
                </p>
            </div>

            <p class="relative z-10 text-xs text-white/45">
                © {{ date('Y') }} · {{ $appLabel }}
            </p>
        </aside>

        {{-- Formulaire --}}
        <main class="relative flex flex-col justify-center px-6 py-10 sm:px-10 lg:px-16">
            <div class="absolute right-6 top-6 sm:right-10">
                <div class="flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 p-1">
                    <form method="POST" action="{{ route('locale.set') }}" class="m-0">
                        @csrf
                        <input type="hidden" name="locale" value="fr">
                        <button type="submit"
                                class="rounded-full px-3 py-1 text-xs font-semibold transition {{ $isFr ? 'bg-[#17384b] text-white' : 'text-slate-500' }}">
                            FR
                        </button>
                    </form>
                    <form method="POST" action="{{ route('locale.set') }}" class="m-0">
                        @csrf
                        <input type="hidden" name="locale" value="en">
                        <button type="submit"
                                class="rounded-full px-3 py-1 text-xs font-semibold transition {{ ! $isFr ? 'bg-[#17384b] text-white' : 'text-slate-500' }}">
                            EN
                        </button>
                    </form>
                </div>
            </div>

            <div class="mx-auto w-full max-w-md">
                <div class="mb-8 lg:hidden">
                    <div class="mb-4 flex items-center gap-3">
                        @if (! empty($brandLogoUrl))
                            <img src="{{ $brandLogoUrl }}" alt="{{ $appLabel }}" class="h-9 w-9 rounded-xl object-contain">
                        @else
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-sm font-bold text-white" style="background: {{ $primary }}">{{ strtoupper(substr($appLabel, 0, 1)) }}</span>
                        @endif
                        <span class="text-sm font-semibold" style="color: {{ $primary }}">{{ $appLabel }}</span>
                    </div>
                </div>

                <h2 class="font-display text-3xl font-medium tracking-tight text-[#17384b]">
                    {{ __('auth_ui.sign_in') }}
                </h2>
                <p class="mt-2 text-sm text-slate-500">
                    {{ $isFr ? 'Espace Agent' : 'Agent workspace' }}
                </p>

                @if (session('status'))
                    <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <p class="mb-1 font-semibold">{{ __('auth_ui.form_error_title') }}</p>
                        <ul class="mb-0 list-disc ps-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">
                            {{ __('auth_ui.email') }}
                        </label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                               autocomplete="username"
                               class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 transition focus:border-[#17384b] focus:outline-none focus:ring-4 focus:ring-[#17384b]/15"
                               placeholder="{{ __('auth_ui.email_placeholder') }}">
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">
                            {{ __('auth_ui.password') }}
                        </label>
                        <input id="password" name="password" type="password" required autocomplete="current-password"
                               class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 transition focus:border-[#17384b] focus:outline-none focus:ring-4 focus:ring-[#17384b]/15"
                               placeholder="{{ __('auth_ui.password_placeholder') }}">
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                            <input id="remember_me" name="remember" type="checkbox" value="1"
                                   class="h-4 w-4 rounded border-slate-300 text-[#17384b] focus:ring-[#17384b]/30">
                            {{ __('auth_ui.remember') }}
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}"
                               class="text-sm font-medium text-[#dc633a] no-underline hover:underline">
                                {{ __('auth_ui.forgot') }}
                            </a>
                        @endif
                    </div>

                    <button type="submit"
                            class="w-full rounded-xl bg-[#17384b] px-4 py-3.5 text-sm font-semibold text-white transition hover:bg-[#122c3b] focus:outline-none focus:ring-4 focus:ring-[#17384b]/20">
                        {{ __('auth_ui.sign_in') }}
                    </button>
                </form>

                <p class="mt-8 text-center text-sm text-slate-400">
                    <a href="{{ url('/') }}" class="font-medium text-slate-600 no-underline hover:text-[#17384b]">
                        ← {{ $isFr ? 'Accueil' : 'Home' }}
                    </a>
                </p>
            </div>
        </main>
    </div>
</body>
</html>
