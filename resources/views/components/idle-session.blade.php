@auth
<dialog class="idle-session-dialog" aria-labelledby="idle-session-title" aria-describedby="idle-session-description"
        data-idle-session
        data-expires-at="{{ \App\Services\IdleSession::expiresAt(request()) }}"
        data-server-now="{{ now()->timestamp }}"
        data-warning-seconds="{{ config('session.idle_warning_seconds', 120) }}"
        data-storage-key="courrier-idle-{{ session(\App\Services\IdleSession::KEY) }}"
        data-activity-url="{{ route('session.activity') }}"
        data-status-url="{{ route('session.status') }}"
        data-login-url="{{ route('login', ['reason' => 'inactive']) }}">
    <div class="idle-session-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
    </div>
    <h2 id="idle-session-title">{{ __('session_ui.title') }}</h2>
    <p id="idle-session-description">{{ __('session_ui.warning') }} <strong data-idle-countdown></strong>.</p>
    <p class="idle-session-hint">{{ __('session_ui.save_hint') }}</p>
    <p role="alert" data-idle-error hidden>{{ __('session_ui.error') }}</p>
    <div class="idle-session-actions">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-buttons.secondary type="submit">{{ __('session_ui.logout') }}</x-buttons.secondary>
        </form>
        <x-buttons.primary type="button" data-idle-continue>{{ __('session_ui.continue') }}</x-buttons.primary>
    </div>
</dialog>
@endauth
