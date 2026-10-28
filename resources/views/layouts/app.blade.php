@php
    $authUser = Auth::user();
    $authName = $authUser ? (string) $authUser->name : '';
    $unreadCount = $authUser ? $authUser->unreadNotifications()->count() : 0;
    $brand = $brand ?? [];
    $primary = $brand['primary_color'] ?? '#17384b';
    $accent = $brand['accent_color'] ?? '#dc633a';
    $appLabel = $brand['app_name'] ?? config('app.name', 'COURRIER GUTF');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $appLabel }}</title>
    @if (! empty($brandFaviconUrl))
        <link rel="icon" href="{{ $brandFaviconUrl }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --gutf-primary: {{ $primary }};
            --gutf-accent: {{ $accent }};
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 text-slate-900 antialiased">
    @include('layouts.navigation')

    <div class="min-h-screen lg:pl-72">
        {{-- Header --}}
        <header class="md:sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur">
            <div class="app-topbar px-4 py-2.5 sm:px-6">
                <button type="button"
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 lg:hidden"
                        data-sidebar-open aria-label="Ouvrir le menu">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <div class="app-page-heading min-w-0 flex-1">
                    @isset($header)
                        <div class="min-w-0 [&_h1]:mb-0 [&_h1]:text-base [&_h1]:font-bold [&_h1]:text-slate-900 sm:[&_h1]:text-lg">
                            {{ $header }}
                        </div>
                    @else
                        <h1 class="truncate text-base font-bold text-slate-900 sm:text-lg">
                            {{ $appLabel }}
                        </h1>
                    @endisset
                </div>

                <div class="app-account-tools flex shrink-0 items-center gap-1 sm:gap-2">
                    <x-locale-switcher />

                    <a href="{{ route('notifications.index') }}"
                       class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl text-slate-600 no-underline hover:bg-slate-50"
                       title="Notifications">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5" style="color: var(--gutf-primary)">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                        <span id="headerNotificationBadge"
                              class="absolute right-1.5 top-1.5 inline-flex min-w-[1.05rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold leading-4 text-white {{ $unreadCount > 0 ? '' : 'hidden' }}">
                            {{ $unreadCount }}
                        </span>
                    </a>

                    <div class="relative" data-profile-menu>
                        <button type="button"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white py-1.5 pl-1.5 pr-2.5 text-left hover:bg-slate-50"
                                data-profile-toggle aria-expanded="false" aria-haspopup="true">
                            <x-user-avatar :user="$authUser" size="sm" />
                            <span class="hidden max-w-[9rem] truncate text-sm font-semibold sm:inline" style="color: var(--gutf-primary)">
                                {{ $authName }}
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="hidden h-4 w-4 text-slate-400 sm:block">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>

                        <div data-profile-dropdown
                             class="absolute right-0 z-50 mt-2 hidden w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white py-1 shadow-lg">
                            <div class="border-b border-slate-100 px-4 py-3">
                                <div class="truncate text-sm font-semibold text-slate-800">{{ $authName }}</div>
                                <div class="truncate text-xs text-slate-400">{{ $authUser?->email }}</div>
                            </div>
                            <a href="{{ route('profile.edit') }}"
                               class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 no-underline hover:bg-slate-50">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                                {{ __('Mon Profil') }}
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm font-semibold text-rose-600 hover:bg-rose-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                                    </svg>
                                    {{ __('Déconnexion') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
    </div>

    @can('chat.view')
        @vite(['resources/js/chat.js'])
        <x-chat-widget :initial-unread="0" />
    @endcan

    @stack('scripts')
    <x-idle-session />

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('app-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const openBtns = document.querySelectorAll('[data-sidebar-open]');
            const closeBtns = document.querySelectorAll('[data-sidebar-close]');

            const openSidebar = () => {
                sidebar?.classList.remove('-translate-x-full');
                overlay?.classList.remove('opacity-0', 'pointer-events-none');
                document.body.classList.add('overflow-hidden');
            };
            const closeSidebar = () => {
                sidebar?.classList.add('-translate-x-full');
                overlay?.classList.add('opacity-0', 'pointer-events-none');
                document.body.classList.remove('overflow-hidden');
            };

            openBtns.forEach((btn) => btn.addEventListener('click', openSidebar));
            closeBtns.forEach((btn) => btn.addEventListener('click', closeSidebar));
            overlay?.addEventListener('click', closeSidebar);
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeSidebar();
                    profileDropdown?.classList.add('hidden');
                    profileToggle?.setAttribute('aria-expanded', 'false');
                }
            });

            const profileRoot = document.querySelector('[data-profile-menu]');
            const profileToggle = profileRoot?.querySelector('[data-profile-toggle]');
            const profileDropdown = profileRoot?.querySelector('[data-profile-dropdown]');

            profileToggle?.addEventListener('click', (e) => {
                e.stopPropagation();
                const open = !profileDropdown?.classList.contains('hidden');
                profileDropdown?.classList.toggle('hidden', open);
                profileToggle.setAttribute('aria-expanded', open ? 'false' : 'true');
            });

            document.addEventListener('click', () => {
                profileDropdown?.classList.add('hidden');
                profileToggle?.setAttribute('aria-expanded', 'false');
            });

            const notifBadge = document.getElementById('headerNotificationBadge');
            const unreadCountUrl = @json(auth()->check() ? route('notifications.unread_count') : null);
            if (notifBadge && unreadCountUrl) {
                const syncNotifBadge = async () => {
                    try {
                        const res = await fetch(unreadCountUrl, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        const count = Number(data.count || 0);
                        notifBadge.textContent = String(count);
                        notifBadge.classList.toggle('hidden', count < 1);
                    } catch (e) {}
                };
                setInterval(syncNotifBadge, 45000);
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible') syncNotifBadge();
                });
            }
        });
    </script>
</body>
</html>
