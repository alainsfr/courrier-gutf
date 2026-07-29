@php
    $isFr = app()->getLocale() === 'fr';
    $inst = config('institution');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $inst['app_label'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,650&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .font-display { font-family: "Fraunces", Georgia, serif; }
        .font-ui { font-family: "Outfit", system-ui, sans-serif; }
        @keyframes fade-up {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes soft-pulse {
            0%, 100% { opacity: .45; }
            50% { opacity: .7; }
        }
        .anim-fade-up { animation: fade-up .7s ease-out both; }
        .anim-fade-up-delay { animation: fade-up .7s ease-out .12s both; }
        .anim-fade-up-delay-2 { animation: fade-up .7s ease-out .24s both; }
        .hero-glow {
            animation: soft-pulse 8s ease-in-out infinite;
        }
    </style>
</head>
<body class="font-ui min-h-screen bg-[#f3f5f7] text-slate-900 antialiased">
    <div class="relative flex min-h-screen flex-col overflow-hidden">
        {{-- Atmosphere --}}
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="hero-glow absolute -left-24 top-[-10%] h-[28rem] w-[28rem] rounded-full bg-[#17384b]/10 blur-3xl"></div>
            <div class="absolute bottom-[-20%] right-[-10%] h-[32rem] w-[32rem] rounded-full bg-[#dc633a]/10 blur-3xl"></div>
            <div class="absolute inset-0 opacity-[0.035]"
                 style="background-image: radial-gradient(#17384b 0.8px, transparent 0.8px); background-size: 18px 18px;"></div>
        </div>

        <header class="relative z-10 flex items-center justify-between px-6 py-5 sm:px-10">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-[#17384b] text-sm font-bold text-white">
                    G
                </span>
                <span class="text-sm font-semibold tracking-wide text-[#17384b]">{{ $inst['app_label'] }}</span>
            </div>

            <div class="flex items-center gap-2 rounded-full border border-slate-200/80 bg-white/70 p-1 backdrop-blur">
                <form method="POST" action="{{ route('locale.set') }}" class="m-0">
                    @csrf
                    <input type="hidden" name="locale" value="fr">
                    <button type="submit"
                            class="rounded-full px-3 py-1 text-xs font-semibold transition {{ $isFr ? 'bg-[#17384b] text-white' : 'text-slate-500 hover:text-slate-800' }}">
                        FR
                    </button>
                </form>
                <form method="POST" action="{{ route('locale.set') }}" class="m-0">
                    @csrf
                    <input type="hidden" name="locale" value="en">
                    <button type="submit"
                            class="rounded-full px-3 py-1 text-xs font-semibold transition {{ ! $isFr ? 'bg-[#17384b] text-white' : 'text-slate-500 hover:text-slate-800' }}">
                        EN
                    </button>
                </form>
            </div>
        </header>

        <main class="relative z-10 flex flex-1 flex-col items-center justify-center px-6 pb-20 pt-8 text-center sm:px-10">
            <p class="anim-fade-up mb-5 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                MINDCAF · MINFI
            </p>

            <h1 class="anim-fade-up-delay font-display max-w-3xl text-4xl font-medium leading-[1.12] tracking-tight text-[#17384b] sm:text-5xl lg:text-6xl">
                Guichet Unique du Foncier
            </h1>

            <p class="anim-fade-up-delay-2 mt-5 max-w-md text-base leading-relaxed text-slate-500 sm:text-lg">
                {{ $isFr ? 'Espace sécurisé de suivi du courrier foncier.' : 'Secure space for land registry mail tracking.' }}
            </p>

            <div class="anim-fade-up-delay-2 mt-10">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center justify-center rounded-2xl bg-[#17384b] px-8 py-4 text-sm font-semibold text-white transition duration-300 hover:-translate-y-0.5 hover:bg-[#122c3b] focus:outline-none focus:ring-4 focus:ring-[#17384b]/20">
                        {{ $isFr ? 'Accéder à mon espace' : 'Open my workspace' }}
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center justify-center rounded-2xl bg-[#17384b] px-8 py-4 text-sm font-semibold text-white transition duration-300 hover:-translate-y-0.5 hover:bg-[#122c3b] focus:outline-none focus:ring-4 focus:ring-[#17384b]/20">
                        {{ $isFr ? 'Accéder à l’espace Agent' : 'Access Agent workspace' }}
                    </a>
                @endauth
            </div>
        </main>

        <footer class="relative z-10 px-6 pb-6 text-center text-xs text-slate-400 sm:px-10">
            © {{ date('Y') }} · {{ $inst['app_label'] }}
        </footer>
    </div>
</body>
</html>
