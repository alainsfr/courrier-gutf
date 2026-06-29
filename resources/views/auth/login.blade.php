@php
    $isFr = (app()->getLocale() === 'fr');
@endphp
@extends('layouts.auth')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <div class="auth-title" style="font-size: 1.25rem;">{{ __('auth_ui.login_title') }}</div>
        <div class="auth-subtitle small text-muted mb-0">{{ __('auth_ui.login_subtitle') }}</div>
    </div>

    {{-- Sélecteur de langue --}}
    <div class="lang-switch p-1 rounded-pill d-inline-flex align-items-center bg-light border">
        <form method="POST" action="{{ route('locale.set') }}" class="m-0">
            @csrf
            <input type="hidden" name="locale" value="fr">
            <button type="submit"
                class="btn btn-sm px-3 py-1 rounded-pill fw-bold small transition-all {{ $isFr ? 'btn-lang-active text-white' : 'text-muted border-0 bg-transparent' }}">
                FR
            </button>
        </form>

        <form method="POST" action="{{ route('locale.set') }}" class="m-0 ms-1">
            @csrf
            <input type="hidden" name="locale" value="en">
            <button type="submit"
                class="btn btn-sm px-3 py-1 rounded-pill fw-bold small transition-all {{ !$isFr ? 'btn-lang-active text-white' : 'text-muted border-0 bg-transparent' }}">
                EN
            </button>
        </form>
    </div>
</div>

@if (session('status'))
    <div class="alert alert-success border-0 shadow-sm rounded-3 mb-3 small">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-3">
        <div class="fw-bold mb-1 small">{{ __('auth_ui.form_error_title') }}</div>
        <ul class="mb-0 small" style="padding-left: 1.2rem;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('login') }}">
    @csrf

    <div class="mb-3">
        <label class="form-label fw-semibold text-muted small" for="email">{{ __('auth_ui.email') }}</label>
        <input id="email" name="email" type="email" class="form-control border-slate-200"
               value="{{ old('email') }}" required autofocus autocomplete="username"
               placeholder="{{ __('auth_ui.email_placeholder') }}">
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold text-muted small" for="password">{{ __('auth_ui.password') }}</label>
        <input id="password" name="password" type="password" class="form-control border-slate-200"
               required autocomplete="current-password"
               placeholder="{{ __('auth_ui.password_placeholder') }}">
    </div>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="1" id="remember_me" name="remember">
            <label class="form-check-label text-muted small fw-semibold" for="remember_me" style="cursor: pointer;">
                {{ __('auth_ui.remember') }}
            </label>
        </div>

        @if (Route::has('password.request'))
            <a class="link-accent small" href="{{ route('password.request') }}">
                {{ __('auth_ui.forgot') }}
            </a>
        @endif
    </div>

    <button type="submit" class="btn btn-gutf w-100 py-2.5 rounded-3 fw-bold shadow-sm">
        {{ __('auth_ui.sign_in') }}
    </button>

    <div class="text-center mt-4 small text-muted">
        {{ __('auth_ui.support_text') }}
        <a class="link-accent" href="mailto:support@bacl-gu.gov.cm">support@bacl-gu.gov.cm</a>
    </div>
</form>
@endsection