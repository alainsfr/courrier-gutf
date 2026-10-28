<x-app-layout>
    <x-slot name="header">
        <x-admin-page-header :title="__('recovery.admin_title')" :subtitle="$user->name.' · '.$user->email">
            <x-slot:actions>
                <x-buttons.secondary :href="route('admin.users.edit', $user)">{{ __('users.back') }}</x-buttons.secondary>
            </x-slot:actions>
        </x-admin-page-header>
    </x-slot>
    <x-form-errors />
    @if ($code)
        <section class="ui-card mb-6 p-6" aria-labelledby="issued-code-title">
            <h2 id="issued-code-title" class="text-lg font-bold text-brand">{{ __('recovery.issued') }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ __('recovery.once') }}</p>
            <p data-recovery-secret class="my-4 select-all break-all rounded-xl border border-slate-200 bg-slate-50 p-4 font-mono text-lg font-bold tracking-wide">{{ $code }}</p>
            <p class="text-sm font-semibold">{{ __('recovery.expires', ['time' => $grant->expires_at->format('d/m/Y H:i'), 'zone' => config('app.timezone')]) }}</p>
            <p class="mt-3 text-sm text-slate-600">{{ __('recovery.instructions') }}</p>
        </section>
    @endif
    @if ($user->is_active)
        <section class="ui-card p-6">
            <h2 class="text-base font-bold text-brand">{{ __('recovery.admin_intro') }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ __('recovery.generate_help') }}</p>
            <form method="POST" action="{{ route('admin.users.recovery.issue', $user) }}" class="mt-5 max-w-xl space-y-5" data-submit-once>
                @csrf
                <div>
                    <x-input-label for="admin_password" :value="__('recovery.admin_password')" />
                    <x-text-input id="admin_password" type="password" name="admin_password" autocomplete="current-password" required />
                </div>
                <label class="flex items-start gap-3 text-sm text-slate-700">
                    <input type="checkbox" name="identity_checked" value="1" required class="mt-1 rounded border-slate-300">
                    <span>{{ __('recovery.identity_checked') }}</span>
                </label>
                <x-buttons.primary type="submit">{{ __('recovery.generate') }}</x-buttons.primary>
            </form>
        </section>
    @else
        <div class="ui-alert ui-alert-warning">{{ __('recovery.inactive') }}</div>
    @endif
    <section class="ui-card mt-6 p-6" aria-labelledby="recovery-history-title">
        <h2 id="recovery-history-title" class="text-base font-bold text-brand">{{ __('recovery.history') }}</h2>
        <p class="mt-1 mb-4 text-sm text-slate-500">{{ __('recovery.history_help') }}</p>
        <div class="ui-table-scroll">
            <table class="ui-table text-left">
                <thead><tr><th>{{ __('recovery.date') }}</th><th>{{ __('recovery.issuer') }}</th><th>{{ __('recovery.status') }}</th></tr></thead>
                <tbody>
                    @forelse ($history as $record)
                        @php
                            $state = match (true) {
                                (bool) $record->used_at => 'used',
                                !$user->is_active || (bool) $record->revoked_at || !hash_equals($record->credential_fingerprint, app(\App\Services\AccountRecoveryService::class)->fingerprint($user)) => 'revoked',
                                now()->greaterThanOrEqualTo($record->expires_at) => 'expired',
                                default => 'active',
                            };
                        @endphp
                        <tr>
                            <td class="whitespace-nowrap">{{ $record->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $record->source === 'console' ? __('recovery.console') : ($record->issuer?->name ?? __('recovery.deleted_issuer')) }}</td>
                            <td>{{ __('recovery.'.$state) }}@if ($record->used_at)<span class="mt-1 block whitespace-nowrap text-xs text-slate-500">{{ __('recovery.used_at', ['time' => $record->used_at->format('d/m/Y H:i')]) }}</span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">{{ __('recovery.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
