<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h1 class="h4 mb-1 text-dark fw-bold">{{ __('profile.title') }}</h1>
                <p class="text-muted small mb-0">{{ __('profile.subtitle') }}</p>
            </div>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary px-4 rounded-3 fw-semibold">
                ← {{ __('profile.back_dashboard') }}
            </a>
        </div>
    </x-slot>

    {{-- Messages de confirmation --}}
    @if (session('status') === 'profile-updated')
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-2">
            <span>✅</span> <div>{{ __('profile.updated') }}</div>
        </div>
    @endif

    @if (session('status') === 'password-updated')
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-2">
            <span>🔐</span> <div>Votre mot de passe a été mis à jour avec succès.</div>
        </div>
    @endif

    @if ($errors->any() || $errors->updatePassword->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4">
            <div class="fw-bold mb-2">{{ __('profile.form_error_title') }}</div>
            <ul class="mb-0 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
                @foreach ($errors->updatePassword->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        
        {{-- COLONNE PRINCIPALE : FORMULAIRES DE CONFIGURATION (8/12) --}}
        <div class="col-lg-8">
            
            {{-- CARTE 1 : INFORMATIONS PERSONNELLES --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom p-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-4">👤</span>
                        <h5 class="card-title fw-bold text-dark mb-0">{{ __('profile.information') }}</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PATCH')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small" for="name">{{ __('profile.name') }} <span class="text-danger">*</span></label>
                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    class="form-control border-slate-200 rounded-3"
                                    value="{{ old('name', $user->name) }}"
                                    required
                                    autocomplete="name"
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small" for="email">{{ __('profile.email') }} <span class="text-danger">*</span></label>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    class="form-control border-slate-200 rounded-3"
                                    value="{{ old('email', $user->email) }}"
                                    required
                                    autocomplete="username"
                                >
                            </div>

                            <hr class="my-4 border-slate-100">

                            {{-- Menu déroulant professionnel pour le bilinguisme --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold text-muted small mb-2">{{ __('profile.language') }}</label>
                                @php
                                    $currentLocale = old('locale', $user->locale ?? app()->getLocale());
                                @endphp
                                <div class="row align-items-center g-3">
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-slate-200 text-muted">🌐</span>
                                            <select name="locale" class="form-select border-slate-200 rounded-end-3" required>
                                                <option value="fr" @selected($currentLocale === 'fr')>Français (French)</option>
                                                <option value="en" @selected($currentLocale === 'en')>English (English)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6 text-muted small">
                                        {{ __('profile.language_help') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4 pt-2">
                            <button type="submit" class="btn btn-gutf px-4 py-2.5 rounded-3 shadow-sm font-semibold">
                                💾 {{ __('profile.save') }}
                            </button>
                            <a href="{{ route('dashboard') }}" class="btn btn-light border border-slate-200 px-4 py-2.5 rounded-3 font-semibold text-muted">
                                {{ __('profile.back_dashboard') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- CARTE 2 : SÉCURITÉ & CHANGEMENT DE MOT DE PASSE --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom p-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-4">🔒</span>
                        <h5 class="card-title fw-bold text-dark mb-0">Sécurité du compte</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold text-muted small">Mot de passe actuel <span class="text-danger">*</span></label>
                                <input type="password" name="current_password" class="form-control border-slate-200 rounded-3" required autocomplete="current-password" placeholder="Saisissez votre mot de passe actuel">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small">Nouveau mot de passe <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control border-slate-200 rounded-3" required autocomplete="new-password" placeholder="Minimum 8 caractères">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small">Confirmer le nouveau mot de passe <span class="text-danger">*</span></label>
                                <input type="password" name="password_confirmation" class="form-control border-slate-200 rounded-3" required autocomplete="new-password" placeholder="Ressaisissez le nouveau mot de passe">
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4 pt-2">
                            <button type="submit" class="btn btn-outline-secondary px-4 py-2.5 rounded-3 shadow-sm font-semibold">
                                🔒 Mettre à jour le mot de passe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- COLONNE LATÉRALE : RÉSUMÉ & ZONE DE SÉCURITÉ DANGER (4/12) --}}
        <div class="col-lg-4">
            
            {{-- Résumé --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom p-4">
                    <h5 class="fw-bold text-dark mb-0">{{ __('profile.summary') }}</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.75rem;">{{ __('profile.current_language') }}</div>
                        <div class="fw-bold text-dark mt-1" style="font-size: 1.1rem;">
                            {{ strtoupper(app()->getLocale()) === 'FR' ? '🇫🇷 Français' : '🇬🇧 English' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.75rem;">{{ __('profile.account_email') }}</div>
                        <div class="fw-bold text-dark mt-1 small">{{ $user->email }}</div>
                    </div>
                </div>
            </div>

            {{-- Zone de suppression de compte --}}
            <div class="card border border-danger-subtle shadow-sm rounded-4">
                <div class="card-header bg-danger-subtle border-0 text-danger p-4">
                    <h5 class="fw-bold mb-0">🚨 {{ __('profile.delete_account') }}</h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-3">
                        {{ __('profile.delete_help') }}
                    </p>

                    <form method="POST" action="{{ route('profile.destroy') }}">
                        @csrf
                        @method('DELETE')

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted small" for="password">{{ __('profile.password') }} <span class="text-danger">*</span></label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                class="form-control border-slate-200 rounded-3 @error('password') is-invalid @enderror"
                                required
                                autocomplete="current-password"
                                placeholder="Confirmez avec votre mot de passe"
                            >
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-outline-danger w-100 py-2.5 rounded-3 fw-bold small">
                            🗑️ {{ __('profile.delete') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>