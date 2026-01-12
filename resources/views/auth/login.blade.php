<x-layouts.auth>
    <div class="d-flex align-items-start justify-content-between mb-3">
        <div>
            <div class="auth-title">{{ __('auth_ui.login_title') }}</div>
            <div class="auth-subtitle">{{ __('auth_ui.login_subtitle') }}</div>
        </div>

        {{-- Sélecteur langue minimal (on le reliera à votre route /locale ensuite) --}}
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                {{ strtoupper(app()->getLocale()) }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <form method="POST" action="/locale" class="px-3 py-1">
                        @csrf
                        <input type="hidden" name="locale" value="fr">
                        <button class="btn btn-link p-0 text-decoration-none" type="submit">FR</button>
                    </form>
                </li>
                <li>
                    <form method="POST" action="/locale" class="px-3 py-1">
                        @csrf
                        <input type="hidden" name="locale" value="en">
                        <button class="btn btn-link p-0 text-decoration-none" type="submit">EN</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">{{ __('auth_ui.form_error_title') }}</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label" for="email">{{ __('auth_ui.email') }}</label>
            <input id="email" name="email" type="email" class="form-control"
                   value="{{ old('email') }}" required autofocus autocomplete="username"
                   placeholder="{{ __('auth_ui.email_placeholder') }}">
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">{{ __('auth_ui.password') }}</label>
            <input id="password" name="password" type="password" class="form-control"
                   required autocomplete="current-password"
                   placeholder="{{ __('auth_ui.password_placeholder') }}">
        </div>

        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="remember_me" name="remember">
                <label class="form-check-label" for="remember_me">
                    {{ __('auth_ui.remember') }}
                </label>
            </div>

            @if (Route::has('password.request'))
                <a class="link-accent" href="{{ route('password.request') }}">
                    {{ __('auth_ui.forgot') }}
                </a>
            @endif
        </div>

        <button type="submit" class="btn btn-gutf w-100">
            {{ __('auth_ui.sign_in') }}
        </button>

        <div class="text-center mt-3" style="color: var(--gutf-muted); font-size:.95rem;">
            {{ __('auth_ui.support_text') }}
            <a class="link-accent" href="mailto:support@gutf.local">{{ __('auth_ui.support_link') }}</a>
        </div>
    </form>
</x-layouts.auth>
