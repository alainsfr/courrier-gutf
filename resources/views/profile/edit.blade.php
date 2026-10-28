@php
    $input = 'ui-input';
    $label = 'ui-label';
    $currentLocale = old('locale', $user->locale ?? app()->getLocale());
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-bold text-slate-900">{{ __('profile.title') }}</h1>
                <p class="mb-0 text-sm text-slate-500">{{ __('profile.subtitle') }}</p>
            </div>
            <x-buttons.secondary :href="route('dashboard')">
                {{ __('profile.back_dashboard') }}
            </x-buttons.secondary>
        </div>
    </x-slot>

    @if (session('status') === 'profile-updated')
        <div class="mb-4 ui-alert ui-alert-success">
            {{ __('profile.updated') }}
        </div>
    @endif

    @if (session('status') === 'password-updated')
        <div class="mb-4 ui-alert ui-alert-success">
            {{ __('profile.password_updated') }}
        </div>
    @endif

    @if (session('status') === 'signature-pin-updated')
        <div class="mb-4 ui-alert ui-alert-success">
            {{ __('profile.pin_updated') }}
        </div>
    @endif

    <x-form-errors :bags="['updatePassword']" />

    <div class="mb-6 ui-card p-5 shadow-sm">
        <div class="flex flex-wrap items-center gap-4">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-accent text-sm font-bold text-white">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </span>
            <div class="min-w-0 flex-1">
                <div class="truncate text-base font-bold text-slate-900">{{ $user->name }}</div>
                <div class="truncate text-sm text-slate-500">{{ $user->email }}</div>
            </div>
            <div class="text-sm text-slate-500">
                <span class="font-medium text-slate-700">{{ __('profile.current_language') }} :</span>
                {{ strtoupper($currentLocale) === 'FR' ? __('profile.lang_fr') : __('profile.lang_en') }}
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Informations du profil --}}
        <div class="ui-card p-6 shadow-sm">
            <div class="mb-5 border-b border-slate-100 pb-4">
                <h2 class="m-0 text-base font-bold text-slate-900">{{ __('profile.information') }}</h2>
                <p class="mb-0 mt-1 text-sm text-slate-500">{{ __('profile.information_help') }}</p>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="{{ $label }}" for="name">{{ __('profile.name') }}</label>
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
                    <label class="{{ $label }}" for="email">{{ __('profile.email') }}</label>
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
                    <label class="{{ $label }}" for="locale">{{ __('profile.language') }}</label>
                    <select id="locale" name="locale" class="{{ $input }}" required>
                        <option value="fr" @selected($currentLocale === 'fr')>{{ __('profile.lang_fr') }}</option>
                        <option value="en" @selected($currentLocale === 'en')>{{ __('profile.lang_en') }}</option>
                    </select>
                    <p class="mb-0 mt-1.5 text-xs text-slate-400">{{ __('profile.language_help') }}</p>
                </div>

                <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-5">
                    <x-buttons.primary type="submit">
                        {{ __('profile.save') }}
                    </x-buttons.primary>
                    <x-buttons.secondary :href="route('dashboard')">
                        {{ __('profile.back_dashboard') }}
                    </x-buttons.secondary>
                </div>
            </form>
        </div>

        {{-- Mot de passe --}}
        <div class="ui-card p-6 shadow-sm">
            <div class="mb-5 border-b border-slate-100 pb-4">
                <h2 class="m-0 text-base font-bold text-slate-900">{{ __('profile.password_title') }}</h2>
                <p class="mb-0 mt-1 text-sm text-slate-500">{{ __('profile.password_help') }}</p>
            </div>

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="{{ $label }}" for="current_password">{{ __('profile.current_password') }}</label>
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
                    <label class="{{ $label }}" for="password">{{ __('profile.new_password') }}</label>
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
                    <label class="{{ $label }}" for="password_confirmation">{{ __('profile.password_confirm') }}</label>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        class="{{ $input }}"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <div class="border-t border-slate-100 pt-5">
                    <x-buttons.primary type="submit">
                        {{ __('profile.password_save') }}
                    </x-buttons.primary>
                </div>
            </form>
        </div>
    </div>

    {{-- Code PIN de signature --}}
    <div class="mt-6 ui-card p-6 shadow-sm">
        <div class="mb-5 border-b border-slate-100 pb-4">
            <h2 class="m-0 text-base font-bold text-slate-900">{{ __('profile.pin_title') }}</h2>
            <p class="mb-0 mt-1 text-sm text-slate-500">{{ __('profile.pin_subtitle') }}</p>
        </div>

        @if ($user->signature_pin)
            <div class="mb-4 rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-800">
                {{ __('profile.pin_configured') }}
            </div>
        @else
            <div class="mb-4 rounded-lg border border-amber-100 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800">
                {{ __('profile.pin_missing') }}
            </div>
        @endif

        <form method="POST" action="{{ route('profile.signature-pin.update') }}" class="mx-auto max-w-md space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="{{ $label }}" for="pin_password">{{ __('profile.current_password') }}</label>
                <input id="pin_password" name="pin_password" type="password" required autocomplete="current-password" class="{{ $input }}">
                <p class="mt-1.5 mb-4 text-xs text-slate-500">{{ __('profile.pin_password_help') }}</p>
            </div>
            <div>
                <label class="{{ $label }}" for="signature_pin">{{ __('profile.pin_label') }}</label>
                <input
                    id="signature_pin"
                    name="signature_pin"
                    type="password"
                    inputmode="numeric"
                    pattern="[0-9]{4}"
                    maxlength="4"
                    class="{{ $input }} tracking-[0.35em]"
                    required
                    autocomplete="off"
                    placeholder="••••"
                >
            </div>

            <div>
                <label class="{{ $label }}" for="signature_pin_confirmation">{{ __('profile.pin_confirm_label') }}</label>
                <input
                    id="signature_pin_confirmation"
                    name="signature_pin_confirmation"
                    type="password"
                    inputmode="numeric"
                    pattern="[0-9]{4}"
                    maxlength="4"
                    class="{{ $input }} tracking-[0.35em]"
                    required
                    autocomplete="off"
                    placeholder="••••"
                >
            </div>

            <div class="border-t border-slate-100 pt-5">
                <x-buttons.primary type="submit">
                    {{ __('profile.pin_save') }}
                </x-buttons.primary>
            </div>
        </form>
    </div>

    {{--
        Suppression de compte désactivée (application institutionnelle).
        Un agent ne peut pas supprimer son propre compte : gestion réservée à l’Administration.
        Ancienne section DeleteUserForm retirée volontairement.
    --}}
</x-app-layout>
