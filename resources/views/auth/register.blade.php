<x-guest-layout>
    <h1 class="ui-auth-title">{{ __('ui.register_title') }}</h1>
    <p class="ui-auth-subtitle">{{ __('ui.register_intro') }}</p>
    <x-form-errors />
    <form method="POST" action="{{ route('register') }}" data-submit-once>
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('profile.name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('profile.email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('profile.password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('ui.password_confirm')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-4 mt-6">
            <a class="underline text-sm text-slate-600 hover:text-slate-900 rounded-md" href="{{ route('login') }}">
                {{ __('ui.already_registered') }}
            </a>

            <x-primary-button>
                {{ __('ui.register_title') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
