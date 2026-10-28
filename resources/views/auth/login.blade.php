<x-guest-layout>
    <h2 class="ui-auth-title">{{ __('auth_ui.sign_in') }}</h2>
    <p class="ui-auth-subtitle">{{ __('ui.auth_workspace') }}</p>
    @if (session('status'))<div role="status" class="ui-alert ui-alert-success mb-5">{{ session('status') }}</div>@endif
    @if (request('reason') === 'inactive')<div role="status" class="ui-alert ui-alert-warning mb-5">{{ __('session_ui.expired') }}</div>@endif
    <x-form-errors :title="__('auth_ui.form_error_title')" />
    <form method="POST" action="{{ route('login') }}" class="space-y-5" data-submit-once>
        @csrf
        <div>
            <x-input-label for="email" :value="__('auth_ui.email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" :placeholder="__('auth_ui.email_placeholder')" />
        </div>
        <div>
            <x-input-label for="password" :value="__('auth_ui.password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" :placeholder="__('auth_ui.password_placeholder')" />
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
            <span class="text-slate-500">{{ __('session_ui.login_hint', ['minutes' => config('session.idle_timeout')]) }}</span>
            @if (Route::has('password.request'))<a href="{{ route('password.request') }}" class="font-semibold text-brand">{{ __('auth_ui.forgot') }}</a>@endif
        </div>
        <x-buttons.primary class="w-full">{{ __('auth_ui.sign_in') }}</x-buttons.primary>
    </form>
    <a href="{{ url('/') }}" class="mt-7 inline-block text-sm font-medium text-slate-600">← {{ __('ui.workspace') }}</a>
</x-guest-layout>
