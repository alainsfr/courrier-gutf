@php
    $authUser = Auth::user();
    $authName = $authUser ? (string) $authUser->name : '';
    $unreadCount = $authUser ? $authUser->unreadNotifications()->count() : 0;

    $unreadChatCount = 0;
    if ($authUser) {
        $unreadChatCount = \App\Models\ConversationParticipant::where('user_id', $authUser->id)
            ->where(function ($query) {
                $query->whereNull('last_read_at')
                    ->orWhereRaw('last_read_at < (select updated_at from conversations where id = conversation_participants.conversation_id)');
            })
            ->count();
    }

    $brand = $brand ?? [];
    $appLabel = $brand['app_name'] ?? config('app.name', 'COURRIER GUTF');
    $tagline = $brand['app_tagline'] ?? ($brand['ministry'] ?? 'MINDCAF · MINFI');
    $primary = $brand['primary_color'] ?? '#17384b';

    $linkBase = 'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition no-underline';
    $linkIdle = 'text-slate-600 hover:bg-slate-100 hover:text-[var(--gutf-primary)]';
    $linkActive = 'bg-[color-mix(in_srgb,var(--gutf-primary)_10%,white)] text-[var(--gutf-primary)] font-semibold';
@endphp

{{-- Overlay mobile --}}
<div id="sidebar-overlay"
     class="fixed inset-0 z-40 bg-slate-900/40 opacity-0 pointer-events-none transition-opacity duration-200 lg:hidden"
     aria-hidden="true"></div>

<aside id="app-sidebar"
       class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0">
    <div class="flex h-16 items-center gap-3 border-b border-slate-100 px-5">
        @if (! empty($brandLogoUrl))
            <img src="{{ $brandLogoUrl }}" alt="{{ $appLabel }}" class="h-9 w-9 rounded-xl object-contain">
        @else
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-sm font-bold text-white"
                  style="background: {{ $primary }}">
                {{ strtoupper(substr($appLabel, 0, 1)) }}
            </span>
        @endif
        <div class="min-w-0">
            <div class="truncate text-sm font-semibold tracking-wide" style="color: {{ $primary }}">
                {{ $appLabel }}
            </div>
            <div class="truncate text-[11px] text-slate-400">{{ \Illuminate\Support\Str::limit($tagline, 36) }}</div>
        </div>
        <button type="button"
                class="ml-auto inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 lg:hidden"
                data-sidebar-close aria-label="Fermer">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">{{ __('Menu') }}</p>

        <a href="{{ route('dashboard') }}"
           class="{{ $linkBase }} {{ request()->routeIs('dashboard') || request()->routeIs('statistics.*') || (request()->routeIs('courriers.*') && ! request()->routeIs(['courriers.kanban', 'courriers.search'])) ? $linkActive : $linkIdle }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-[18px] w-[18px] shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
            </svg>
            <span>{{ __('Tableau de bord') }}</span>
        </a>

        @can('courrier.view')
            <a href="{{ route('courriers.kanban') }}"
               class="{{ $linkBase }} {{ request()->routeIs('courriers.kanban') ? $linkActive : $linkIdle }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-[18px] w-[18px] shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125Z" />
                </svg>
                <span>{{ __('Espace de Traitement') }}</span>
            </a>

            <a href="{{ route('courriers.search') }}"
               class="{{ $linkBase }} {{ request()->routeIs('courriers.search') ? $linkActive : $linkIdle }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-[18px] w-[18px] shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <span>{{ __('Recherche Avancée') }}</span>
            </a>
        @endcan

        @can('chat.view')
            <a href="{{ route('messages.index') }}"
               class="{{ $linkBase }} {{ request()->routeIs('messages.*') ? $linkActive : $linkIdle }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-[18px] w-[18px] shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.63m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h.008m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h.008M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 0 1-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8Z" />
                </svg>
                <span class="flex-1">{{ __('Messagerie Interne') }}</span>
                @if($unreadChatCount > 0)
                    <span class="rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $unreadChatCount }}</span>
                @endif
            </a>
        @endcan

        @role('super-admin')
            <p class="mb-2 mt-5 px-3 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">{{ __('Gouvernance') }}</p>
            <a href="{{ route('admin.index') }}"
               class="{{ $linkBase }} {{ request()->routeIs('admin.index') || request()->routeIs('admin.users.*') || request()->routeIs('admin.bureaux.*') ? $linkActive : $linkIdle }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-[18px] w-[18px] shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.174.1.31.245.41.41.112.183.164.39.146.599l-.213 1.281c-.09.542-.56.94-1.11.94h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281a1.125 1.125 0 0 0-.645-.87 1.125 1.125 0 0 1-.41-.41 1.125 1.125 0 0 1-.146-.599l.213-1.281ZM12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z" />
                </svg>
                <span>{{ __('Administration') }}</span>
            </a>
            <a href="{{ route('admin.settings.edit') }}"
               class="{{ $linkBase }} {{ request()->routeIs('admin.settings.*') ? $linkActive : $linkIdle }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-[18px] w-[18px] shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.174.1.31.245.41.41.112.183.164.39.146.599l-.213 1.281c-.09.542-.56.94-1.11.94h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281a1.125 1.125 0 0 0-.645-.87 1.125 1.125 0 0 1-.41-.41 1.125 1.125 0 0 1-.146-.599l.213-1.281ZM12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                </svg>
                <span>{{ __('settings.nav_label') }}</span>
            </a>
        @endrole

        @can('auditor.view')
            <a href="{{ route('audit.index') }}"
               class="{{ $linkBase }} {{ request()->routeIs('audit.*') ? $linkActive : $linkIdle }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-[18px] w-[18px] shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span>{{ __("Journal d'audit") }}</span>
            </a>
        @endcan
    </nav>

    <div class="border-t border-slate-100 p-4">
        <div class="flex items-center gap-3 rounded-xl bg-slate-50 px-3 py-2.5">
            <x-user-avatar :user="$authUser" size="sm" />
            <div class="min-w-0 flex-1">
                <div class="truncate text-sm font-semibold text-slate-800">{{ $authName }}</div>
                <div class="truncate text-[11px] text-slate-400">{{ $authUser?->email }}</div>
            </div>
        </div>
    </div>
</aside>
