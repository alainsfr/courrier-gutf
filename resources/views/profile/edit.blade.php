@php
    $input = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30';
    $label = 'mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500';
    $currentLocale = old('locale', $user->locale ?? app()->getLocale());
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-bold text-gray-900">{{ __('Profil / Paramètres') }}</h1>
                <p class="mb-0 text-sm text-gray-500">{{ __('Préférences et informations du compte') }}</p>
            </div>
            <x-buttons.secondary :href="route('dashboard')">
                {{ __('Retour au tableau de bord') }}
            </x-buttons.secondary>
        </div>
    </x-slot>

    @if (session('status') === 'profile-updated')
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ __('Profil mis à jour avec succès.') }}
        </div>
    @endif

    @if (session('status') === 'password-updated')
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ __('Votre mot de passe a été mis à jour avec succès.') }}
        </div>
    @endif

    @if ($errors->any() || $errors->updatePassword->any())
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <div class="mb-1 font-semibold">{{ __('Veuillez corriger les erreurs ci-dessous.') }}</div>
            <ul class="mb-0 list-disc pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
                @foreach ($errors->updatePassword->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center gap-4">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-[#dc633a] text-sm font-bold text-white">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </span>
            <div class="min-w-0 flex-1">
                <div class="truncate text-base font-bold text-gray-900">{{ $user->name }}</div>
                <div class="truncate text-sm text-gray-500">{{ $user->email }}</div>
            </div>
            <div class="text-sm text-gray-500">
                <span class="font-medium text-gray-700">{{ __('Langue actuelle') }} :</span>
                {{ strtoupper($currentLocale) === 'FR' ? __('Français') : 'English' }}
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Informations du profil --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-5 border-b border-gray-100 pb-4">
                <h2 class="m-0 text-base font-bold text-gray-900">{{ __('Informations du profil') }}</h2>
                <p class="mb-0 mt-1 text-sm text-gray-500">{{ __('Nom, e-mail et langue d’interface de votre compte.') }}</p>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="{{ $label }}" for="name">{{ __('Nom') }}</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        class="{{ $input }}"
                        value="{{ old('name', $user->name) }}"
                        required
                        autocomplete="name"
                    >
                </div>

                <div>
                    <label class="{{ $label }}" for="email">{{ __('Adresse e-mail') }}</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        class="{{ $input }}"
                        value="{{ old('email', $user->email) }}"
                        required
                        autocomplete="username"
                    >
                </div>

                <div>
                    <label class="{{ $label }}" for="locale">{{ __('Langue') }}</label>
                    <select id="locale" name="locale" class="{{ $input }}" required>
                        <option value="fr" @selected($currentLocale === 'fr')>{{ __('Français') }}</option>
                        <option value="en" @selected($currentLocale === 'en')>English</option>
                    </select>
                    <p class="mb-0 mt-1.5 text-xs text-gray-400">{{ __('Ce choix est enregistré sur votre compte et appliqué immédiatement.') }}</p>
                </div>

                <div class="flex flex-wrap gap-2 border-t border-gray-100 pt-5">
                    <x-buttons.primary type="submit">
                        {{ __('Enregistrer') }}
                    </x-buttons.primary>
                    <x-buttons.secondary :href="route('dashboard')">
                        {{ __('Retour au tableau de bord') }}
                    </x-buttons.secondary>
                </div>
            </form>
        </div>

        {{-- Mot de passe --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-5 border-b border-gray-100 pb-4">
                <h2 class="m-0 text-base font-bold text-gray-900">{{ __('Mettre à jour le mot de passe') }}</h2>
                <p class="mb-0 mt-1 text-sm text-gray-500">{{ __('Utilisez un mot de passe long et unique pour sécuriser votre accès.') }}</p>
            </div>

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="{{ $label }}" for="current_password">{{ __('Mot de passe actuel') }}</label>
                    <input
                        id="current_password"
                        type="password"
                        name="current_password"
                        class="{{ $input }}"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <div>
                    <label class="{{ $label }}" for="password">{{ __('Nouveau mot de passe') }}</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="{{ $input }}"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <div>
                    <label class="{{ $label }}" for="password_confirmation">{{ __('Confirmer le nouveau mot de passe') }}</label>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        class="{{ $input }}"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <div class="border-t border-gray-100 pt-5">
                    <x-buttons.primary type="submit">
                        {{ __('Mettre à jour le mot de passe') }}
                    </x-buttons.primary>
                </div>
            </form>
        </div>
    </div>

    {{--
        Suppression de compte désactivée (application institutionnelle).
        Un agent ne peut pas supprimer son propre compte : gestion réservée à l’Administration.
        Ancienne section DeleteUserForm retirée volontairement.
    --}}
</x-app-layout>
