<x-guest-layout>
    <h1 class="ui-auth-title">{{ __('recovery.title') }}</h1>
    <p class="ui-auth-subtitle">{{ __('recovery.intro') }}</p>
    <x-form-errors />
    <form method="POST" action="{{ route('password.code.store') }}" data-submit-once class="space-y-5">
        @csrf
        <div>
            <x-input-label for="email" :value="__('auth_mail.email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" autocomplete="username" required autofocus />
        </div>
        <div>
            <x-input-label for="recovery_code" :value="__('recovery.code')" />
            <x-text-input id="recovery_code" name="recovery_code" autocomplete="off" spellcheck="false" maxlength="80" required aria-describedby="code-help" />
            <p id="code-help" class="mt-2 text-xs text-slate-500">{{ __('recovery.code_help') }}</p>
        </div>
        <div>
            <x-input-label for="password" :value="__('profile.new_password')" />
            <x-text-input id="password" type="password" name="password" autocomplete="new-password" required />
        </div>
        <div>
            <x-input-label for="password_confirmation" :value="__('ui.password_confirm')" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required />
        </div>
        <x-buttons.primary type="submit" class="w-full">{{ __('recovery.submit') }}</x-buttons.primary>
    </form>
    <div class="mt-6 flex flex-wrap gap-4 text-sm font-medium underline">
        <a href="{{ route('password.request') }}">{{ __('recovery.back_email') }}</a>
        <a href="{{ route('login') }}">{{ __('auth_mail.back_login') }}</a>
    </div>
</x-guest-layout>
