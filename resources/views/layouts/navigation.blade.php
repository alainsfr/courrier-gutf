@php
    $authUser = Auth::user();
    $authName = $authUser ? (string) $authUser->name : '';
    $unreadCount = $authUser ? $authUser->unreadNotifications()->count() : 0;
    
    // Calcul dynamique de départ des discussions non lues
    $unreadChatCount = 0;
    if ($authUser) {
        $unreadChatCount = \App\Models\ConversationParticipant::where('user_id', $authUser->id)
            ->where(function($query) {
                $query->whereNull('last_read_at')
                      ->orWhereRaw('last_read_at < (select updated_at from conversations where id = conversation_participants.conversation_id)');
            })
            ->count();
    }

    // Initiales de l'avatar
    $initials = 'U';
    if ($authUser) {
        $words = explode(' ', $authUser->name);
        $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
    }
@endphp

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom py-3">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ route('dashboard') }}" style="color: var(--gutf-primary); letter-spacing: -0.5px;">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 shadow-sm text-white fw-bold"
                  style="width:34px;height:34px;background:var(--gutf-primary);font-size: 1.15rem;">
                G
            </span>
            <span>{{ config('app.name', 'COURRIER GUTF') }}</span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#gutfNavbar"
                aria-controls="gutfNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="gutfNavbar" style="visibility: visible;">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4 gap-1">
                
                {{-- 1. Tableau de bord --}}
                <li class="nav-item">
                    <a class="nav-link px-3 py-2 rounded-3 d-flex align-items-center gap-2 @if(request()->routeIs('dashboard')) active bg-light fw-bold @endif" href="{{ route('dashboard') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width: 18px; height: 18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                        </svg>
                        <span>{{ __('app.dashboard') }}</span>
                    </a>
                </li>

                {{-- 2. Courriers --}}
                @can('courrier.view')
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 rounded-3 d-flex align-items-center gap-2 @if(request()->routeIs('courriers.*')) active bg-light fw-bold @endif" href="{{ route('courriers.index') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width: 18px; height: 18px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0l-7.5-4.615a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                            <span>{{ __('nav.courriers') }}</span>
                        </a>
                    </li>
                @endcan

                {{-- 3. Utilisateurs --}}
                @can('user.manage')
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 rounded-3 d-flex align-items-center gap-2 @if(request()->routeIs('admin.users.*')) active bg-light fw-bold @endif" href="{{ route('admin.users.index') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width: 18px; height: 18px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.109A11.386 11.386 0 0 1 10.089 20a11.383 11.386 0 0 1-4.717-.941v-.109m0 0.013a9.38 9.38 0 0 1-2.625.372 9.337 9.337 0 0 1-4.121-.952 4.125 4.125 0 0 1 7.533-2.493M10.089 20v-.003c0-1.113.285-2.16.786-3.07M10.089 20V19.9M8.25 12a3.75 3.75 0 1 1 7.5 0 3.75 3.75 0 0 1-7.5 0Z" />
                            </svg>
                            <span>{{ __('nav.users') }}</span>
                        </a>
                    </li>
                @endcan

                {{-- 4. Journal d'audit --}}
                @can('auditor.view')
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 rounded-3 d-flex align-items-center gap-2 @if(request()->routeIs('audit.*')) active bg-light fw-bold @endif" href="{{ route('audit.index') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width: 18px; height: 18px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <span>{{ __('audit.title') }}</span>
                        </a>
                    </li>
                @endcan
            </ul>

            <div class="d-flex align-items-center gap-3">
                <x-locale-switcher />

                {{-- ICÔNE DE NOTIFICATIONS (BELL) AVEC BADGE ROUGE --}}
                <a href="{{ route('notifications.index') }}" class="btn btn-link p-1 text-decoration-none position-relative me-1" style="color: inherit;" title="Notifications">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width: 22px; height: 22px; color: var(--gutf-primary);">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-white" 
                          id="headerNotificationBadge" 
                          style="font-size: 0.65rem; padding: 0.2em 0.4em; {{ $unreadCount > 0 ? '' : 'display:none;' }}">
                        {{ $unreadCount }}
                    </span>
                </a>

                {{-- ICÔNE DE MESSAGERIE (CHAT BUBBLE) AVEC BADGE ROUGE --}}
                @can('chat.view')
                    <a href="{{ route('messages.index') }}" class="btn btn-link p-1 text-decoration-none position-relative me-3" style="color: inherit;" title="Messagerie">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width: 22px; height: 22px; color: var(--gutf-primary);">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.63m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h.008m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h.008M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 0 1-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8Z" />
                        </svg>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-white" 
                              id="headerChatBadge" 
                              style="font-size: 0.65rem; padding: 0.2em 0.4em; {{ $unreadChatCount > 0 ? '' : 'display:none;' }}">
                            {{ $unreadChatCount }}
                        </span>
                    </a>
                @endcan

                {{-- Profil --}}
                <div class="dropdown">
                    <button class="btn btn-link p-0 text-decoration-none d-flex align-items-center gap-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: inherit;">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle text-white fw-bold shadow-sm" 
                             style="width: 34px; height: 34px; background: var(--gutf-accent); font-size: 0.85rem; letter-spacing: 0.5px;">
                            {{ $initials ?: 'U' }}
                        </div>
                        <span class="d-none d-md-inline fw-bold small" style="color: var(--gutf-primary);">{{ $authName }}</span>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-3 mt-2 p-2">
                        <li>
                            <a class="dropdown-item rounded-2 py-2 small fw-semibold" href="{{ route('profile.edit') }}">
                                👤 {{ __('nav.profile') }}
                            </a>
                        </li>
                        <li><hr class="dropdown-divider border-slate-100"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" class="p-1">
                                @csrf
                                <button type="submit" class="btn btn-link dropdown-item text-danger rounded-2 py-2 small fw-bold">
                                    🚪 {{ __('nav.logout') }}
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</nav>