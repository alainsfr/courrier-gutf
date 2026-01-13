<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between">
            <h1 class="h4 mb-0">{{ __('profile.title') }}</h1>
            <div class="text-muted small">{{ __('profile.subtitle') }}</div>
        </div>
    </x-slot>

    @if (session('status') === 'profile-updated')
        <div class="alert alert-success">
            {{ __('profile.updated') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">{{ __('profile.form_error_title') }}</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <strong>{{ __('profile.information') }}</strong>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label class="form-label" for="name">{{ __('profile.name') }}</label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $user->name) }}"
                                required
                                autocomplete="name"
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="email">{{ __('profile.email') }}</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $user->email) }}"
                                required
                                autocomplete="username"
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                                <div class="form-text text-warning">
                                    {{ __('profile.email_unverified') }}
                                </div>
                            @endif
                        </div>

                        <hr class="my-4">

                        <div class="mb-3">
                            <label class="form-label">{{ __('profile.language') }}</label>

                            <div class="d-flex flex-wrap align-items-center gap-3">
                                {{-- Segmented control (FR/EN) --}}
                                <div class="lang-switch" role="group" aria-label="Language switcher">
                                    @php
                                        $currentLocale = old('locale', $user->locale ?? app()->getLocale());
                                    @endphp

                                    <label class="m-0">
                                        <input type="radio" name="locale" value="fr" class="d-none" @checked($currentLocale === 'fr')>
                                        <span class="btn btn-sm px-2 {{ $currentLocale === 'fr' ? 'btn-lang-active' : 'btn-lang' }}">
                                            FR
                                        </span>
                                    </label>

                                    <label class="m-0">
                                        <input type="radio" name="locale" value="en" class="d-none" @checked($currentLocale === 'en')>
                                        <span class="btn btn-sm px-2 {{ $currentLocale === 'en' ? 'btn-lang-active' : 'btn-lang' }}">
                                            EN
                                        </span>
                                    </label>
                                </div>

                                <div class="text-muted small">
                                    {{ __('profile.language_help') }}
                                </div>
                            </div>

                            @error('locale')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-gutf">
                                {{ __('profile.save') }}
                            </button>
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                                {{ __('profile.back_dashboard') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Bloc latéral V1 (infos) --}}
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <strong>{{ __('profile.summary') }}</strong>
                </div>
                <div class="card-body">
                    <div class="small text-muted mb-1">{{ __('profile.current_language') }}</div>
                    <div class="fw-semibold mb-3">{{ strtoupper(app()->getLocale()) }}</div>

                    <div class="small text-muted mb-1">{{ __('profile.account_email') }}</div>
                    <div class="fw-semibold">{{ $user->email }}</div>
                </div>
            </div>

            <div class="card border-danger mt-4">
                <div class="card-header bg-white text-danger">
                    <strong>{{ __('profile.delete_account') }}</strong>
                </div>
                <div class="card-body">
                    <div class="text-muted small mb-3">
                        {{ __('profile.delete_help') }}
                    </div>

                    <form method="POST" action="{{ route('profile.destroy') }}">
                        @csrf
                        @method('DELETE')

                        <div class="mb-3">
                            <label class="form-label" for="password">{{ __('profile.password') }}</label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                required
                                autocomplete="current-password"
                            >
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-outline-danger">
                            {{ __('profile.delete') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
