<x-guest-layout>
    <h1 class="mb-3 text-xl font-semibold text-slate-900">{{ __('auth_mail.request_title') }}</h1>
    <div class="mb-4 text-sm text-slate-600">
        {{ __('auth_mail.request_intro') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('auth_mail.email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="email" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('auth_mail.send_link') }}
            </x-primary-button>
        </div>
    </form>
    <a href="{{ route('login') }}" class="mt-5 inline-block text-sm font-medium underline">{{ __('auth_mail.back_login') }}</a>
</x-guest-layout>
