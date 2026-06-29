@php
    $authUser = Auth::user();
    $authName = $authUser ? (string) $authUser->name : '';
    $unreadCount = $authUser ? $authUser->unreadNotifications()->count() : 0;
@endphp

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
    <div class="container">
        <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
            <span class="d-inline-flex align-items-center justify-content-center rounded"
                  style="width:32px;height:32px;background:var(--gutf-primary);color:#fff;font-weight:800;">
                G
            </span>
            <span>{{ config('app.name', 'COURRIER GUTF') }}</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#gutfNavbar"
                aria-controls="gutfNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="gutfNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('dashboard')) active @endif" href="{{ route('dashboard') }}">
                        {{ __('app.dashboard') }}
                    </a>
                </li>

                @can('courrier.view')
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('courriers.*')) active @endif" href="{{ route('courriers.index') }}">
                            {{ __('nav.courriers') }}
                        </a>
                    </li>
                @endcan

                @can('user.manage')
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.users.*')) active @endif" href="{{ route('admin.users.index') }}">
                            {{ __('nav.users') }}
                        </a>
                    </li>
                @endcan

                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('notifications.*')) active @endif" href="{{ route('notifications.index') }}">
                        {{ __('notifications.title') }}
                        @if($unreadCount > 0)
                            <span class="badge text-bg-danger ms-1">{{ $unreadCount }}</span>
                        @endif
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <x-locale-switcher />

                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        {{ $authName }}
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                {{ __('nav.profile') }}
                            </a>
                        </li>

                        <li><hr class="dropdown-divider"></li>

                        <li>
                            <form method="POST" action="{{ route('logout') }}" class="px-3 py-1">
                                @csrf
                                <button type="submit" class="btn btn-link p-0 text-decoration-none text-danger">
                                    {{ __('nav.logout') }}
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</nav>
