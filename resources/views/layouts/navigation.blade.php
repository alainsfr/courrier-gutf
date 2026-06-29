@php
    $authUser = Auth::user();
    $authName = $authUser ? (string) $authUser->name : '';
    $unreadCount = $authUser ? $authUser->unreadNotifications()->count() : 0;
    
    // Génération des initiales de l'avatar (ex: Admin SYSCOM -> AS)
    $initials = 'U';
    if ($authUser) {
        $words = explode(' ', $authUser->name);
        $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
    }
@endphp

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom py-3">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ route('dashboard') }}" style="color: var(--gutf-primary);">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 shadow-sm"
                  style="width:34px;height:34px;background:var(--gutf-primary);color:#fff;font-weight:800; font-size: 1.1rem;">
                G
            </span>
            <span class="letter-spacing-tight">{{ config('app.name', 'COURRIER GUTF') }}</span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#gutfNavbar"
                aria-controls="gutfNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="gutfNavbar" style="visibility: visible;">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4 gap-1">
                <li class="nav-item">
                    <a class="nav-link px-3 py-2 rounded-3 d-flex align-items-center gap-2 @if(request()->routeIs('dashboard')) active bg-light fw-bold @endif" href="{{ route('dashboard') }}">
                        <span>📊</span> {{ __('app.dashboard') }}
                    </a>
                </li>

                @can('courrier.view')
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 rounded-3 d-flex align-items-center gap-2 @if(request()->routeIs('courriers.*')) active bg-light fw-bold @endif" href="{{ route('courriers.index') }}">
                            <span>✉️</span> {{ __('nav.courriers') }}
                        </a>
                    </li>
                @endcan

                @can('user.manage')
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 rounded-3 d-flex align-items-center gap-2 @if(request()->routeIs('admin.users.*')) active bg-light fw-bold @endif" href="{{ route('admin.users.index') }}">
                            <span>👥</span> {{ __('nav.users') }}
                        </a>
                    </li>
                @endcan

                @can('auditor.view')
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 rounded-3 d-flex align-items-center gap-2 @if(request()->routeIs('audit.*')) active bg-light fw-bold @endif" href="{{ route('audit.index') }}">
                            <span>⏳</span> {{ __('audit.title') }}
                        </a>
                    </li>
                @endcan

                <li class="nav-item">
                    <a class="nav-link px-3 py-2 rounded-3 d-flex align-items-center gap-2 @if(request()->routeIs('notifications.*')) active bg-light fw-bold @endif" href="{{ route('notifications.index') }}">
                        <span>🔔</span> {{ __('notifications.title') }}
                        @if($unreadCount > 0)
                            <span class="badge text-bg-danger ms-1 rounded-pill">{{ $unreadCount }}</span>
                        @endif
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-4 flex-wrap">
                <x-locale-switcher />

                <div class="dropdown">
                    <button class="btn btn-link p-0 text-decoration-none d-flex align-items-center gap-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: inherit;">
                        {{-- Avatar dynamique coloré avec initiales --}}
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