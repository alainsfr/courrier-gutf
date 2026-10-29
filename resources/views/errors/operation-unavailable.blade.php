@if (auth()->check())
    <x-app-layout>
        <x-slot name="header"><h1>{{ __('ui.operation_unavailable_title') }}</h1></x-slot>
        <div class="ui-card p-6">
            <p class="mb-5 text-slate-600">{{ __('ui.operation_unavailable') }}</p>
            <x-buttons.secondary :href="route('dashboard')">{{ __('ui.workspace') }}</x-buttons.secondary>
        </div>
    </x-app-layout>
@else
    <x-guest-layout>
        <h1 class="ui-auth-title">{{ __('ui.operation_unavailable_title') }}</h1>
        <p class="ui-auth-subtitle">{{ __('ui.operation_unavailable') }}</p>
        <x-buttons.secondary :href="route('login')">{{ __('auth_mail.back_login') }}</x-buttons.secondary>
    </x-guest-layout>
@endif
