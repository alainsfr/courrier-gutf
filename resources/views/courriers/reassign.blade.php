@php($input = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand-soft')
<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-bold text-slate-900">{{ __('courrier.reassign_title') }}</h1>
        <p class="mb-0 text-sm text-slate-500">{{ $courrier->reference_interne }} · {{ $courrier->objet }}</p>
    </x-slot>
    <x-form-errors />
    <form method="POST" action="{{ route('courriers.reassign', $courrier) }}" class="mx-auto max-w-2xl space-y-5 ui-card p-6 shadow-sm">
        @csrf
        <input type="hidden" name="version" value="{{ old('version', $version) }}">
        <div class="rounded-lg border border-amber-100 bg-amber-50 p-4 text-sm text-amber-900">{{ __('courrier.reassign_hint') }}</div>
        <p class="text-sm text-slate-600">{{ __('courrier.show_bureau') }} : <strong>{{ $courrier->bureauActuel?->nom ?? '—' }}</strong><br>{{ __('courrier.assigned_to') }} : {{ $courrier->assignedTo?->name ?? '—' }}</p>
        <div>
            <label for="bureau-destinataire" class="mb-1.5 block text-xs font-semibold text-slate-600">{{ __('courrier.destination_office') }} *</label>
            <select id="bureau-destinataire" name="bureau_actuel_id" required class="{{ $input }}">
                @foreach($bureaux as $bureau)
                    <option value="{{ $bureau->id }}" @selected((string) old('bureau_actuel_id', $courrier->bureau_actuel_id) === (string) $bureau->id)>{{ $bureau->nom }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="agent-destinataire" class="mb-1.5 block text-xs font-semibold text-slate-600">{{ __('courrier.assign_to') }}</label>
            <select id="agent-destinataire" name="assigned_to_user_id" class="{{ $input }}">
                <option value="">{{ __('courrier.assign_none') }}</option>
                @foreach($agents as $agent)
                    <option value="{{ $agent->id }}" data-bureau-id="{{ $agent->bureau_id }}" @selected((string) old('assigned_to_user_id', $courrier->assigned_to_user_id) === (string) $agent->id)>{{ $agent->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="reassignment-reason" class="mb-1.5 block text-xs font-semibold text-slate-600">{{ __('courrier.change_reason') }} *</label>
            <textarea id="reassignment-reason" name="reason" rows="3" required minlength="5" maxlength="2000" class="{{ $input }}" placeholder="{{ __('courrier.reason_placeholder') }}">{{ old('reason') }}</textarea>
            <p class="mt-1.5 text-xs text-slate-500">{{ __('courrier.reason_audit') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-buttons.primary type="submit">{{ __('courrier.confirm_reassign') }}</x-buttons.primary>
            <x-buttons.secondary :href="route('courriers.show', $courrier)">{{ __('courrier.back') }}</x-buttons.secondary>
        </div>
    </form>
    @push('scripts')
    <script>
        (() => {
            const office = document.getElementById('bureau-destinataire');
            const agent = document.getElementById('agent-destinataire');
            const filter = () => {
                for (const option of agent.options) {
                    if (!option.value) continue;
                    const available = option.dataset.bureauId === office.value;
                    option.hidden = !available;
                    option.disabled = !available;
                    if (!available && option.selected) agent.value = '';
                }
            };
            office.addEventListener('change', filter);
            filter();
        })();
    </script>
    @endpush
</x-app-layout>
