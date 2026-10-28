<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h1 class="mb-1 text-lg font-bold text-gray-900">{{ __('courrier.create_title') }}</h1><p class="mb-0 text-sm text-gray-500">{{ __('ux.create_intro') }}</p></div>
            <x-buttons.secondary :href="route('courriers.index')">← {{ __('courrier.back') }}</x-buttons.secondary>
        </div>
    </x-slot>
    <div class="mx-auto max-w-6xl">
        <x-form-errors class="mb-5" />
        @error('submission_key')
            <a href="{{ route('courriers.create') }}" class="mb-4 inline-block text-sm font-semibold underline">{{ __('courrier.new') }}</a>
        @enderror
        <p class="mb-5 text-sm text-slate-500">{{ __('ux.required_hint') }}</p>
        <form method="POST" action="{{ route('courriers.store') }}" enctype="multipart/form-data" data-submit-once>
            @csrf
            <input type="hidden" name="submission_key" value="{{ old('submission_key', (string) \Illuminate\Support\Str::uuid()) }}">
            <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)]">
                <section class="courrier-card">
                    <div class="courrier-section-heading"><span>01</span><div><h2>{{ __('ux.identify') }}</h2><p>{{ __('ux.identify_hint') }}</p></div></div>
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="type" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('courrier.type') }} <span class="text-rose-600">*</span></label>
                            <select id="type" name="type" required class="courrier-input" @if($errors->has('type')) aria-invalid="true" aria-describedby="type-error" @endif>
                                @foreach(['entrant' => 'type_in', 'sortant' => 'type_out', 'interne' => 'type_internal'] as $value => $key)
                                    <option value="{{ $value }}" @selected(old('type', 'entrant') === $value)>{{ __('courrier.'.$key) }}</option>
                                @endforeach
                            </select>
                            @error('type')<p id="type-error" class="mt-1.5 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <x-courrier-field name="objet" :label="__('courrier.subject')" :required="true" maxlength="255" :placeholder="__('courrier.field_objet_placeholder')" class="sm:col-span-2" />
                        <x-courrier-field name="expediteur" :label="__('courrier.sender')" maxlength="255" />
                        <x-courrier-field name="destinataire" :label="__('courrier.recipient')" maxlength="255" />
                        <x-courrier-field name="date_reception" type="date" :label="__('courrier.date_received')" :value="now()->format('Y-m-d')" />
                        <x-courrier-field name="reference_externe" :label="__('courrier.external_ref')" maxlength="100" />
                    </div>
                </section>
                <section class="courrier-card">
                    <div class="courrier-section-heading"><span>02</span><div><h2>{{ __('ux.route') }}</h2><p>{{ __('courrier.form_assign_help') }}</p></div></div>
                    <div class="space-y-5">
                        <div>
                            <label for="bureau-destinataire" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('courrier.destination_office') }} <span class="text-rose-600">*</span></label>
                            <select id="bureau-destinataire" name="bureau_actuel_id" required class="courrier-input" @if($errors->has('bureau_actuel_id')) aria-invalid="true" aria-describedby="bureau-error" @endif>
                                <option value="">{{ __('courrier.destination_office_choose') }}</option>
                                @foreach($bureaux as $bureau)
                                    <option value="{{ $bureau->id }}" @selected((string) old('bureau_actuel_id') === (string) $bureau->id)>{{ $bureau->nom }}</option>
                                @endforeach
                            </select>
                            @error('bureau_actuel_id')<p id="bureau-error" class="mt-1.5 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="agent-destinataire" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('courrier.assign_to') }} <span class="font-normal text-slate-400">· {{ __('ux.optional') }}</span></label>
                            <select id="agent-destinataire" name="assigned_to_user_id" class="courrier-input" aria-describedby="agent-help {{ $errors->has('assigned_to_user_id') ? 'agent-error' : '' }}" @if($errors->has('assigned_to_user_id')) aria-invalid="true" @endif>
                                <option value="">{{ __('courrier.assign_none') }}</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}" data-bureau-id="{{ $agent->bureau_id }}" @selected(old('assigned_to_user_id') == $agent->id)>{{ $agent->name }}</option>
                                @endforeach
                            </select>
                            <p id="agent-help" class="mt-2 mb-0 text-xs text-slate-500">{{ __('ux.agent_hint') }}</p>
                            @error('assigned_to_user_id')<p id="agent-error" class="mt-1.5 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        @can('courrier.attach')
                            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                                <label for="document_file" class="mb-2 block text-sm font-semibold text-slate-700">{{ __('courrier.document_title') }}</label>
                                <input id="document_file" name="document_file" type="file" accept=".pdf,.jpg,.jpeg,.png" class="courrier-file" aria-describedby="document-help {{ $errors->has('document_file') ? 'document-error' : '' }}" @if($errors->has('document_file')) aria-invalid="true" @endif>
                                <p id="document-help" class="mt-2 mb-0 text-xs text-slate-500">{{ __('courrier.attachments_help') }}</p>
                                @error('document_file')<p id="document-error" class="mt-1.5 text-xs text-rose-700">{{ $message }}</p>@enderror
                                @if($errors->any())<p class="mt-2 mb-0 text-xs text-amber-800">{{ __('ux.reselect_file') }}</p>@endif
                            </div>
                        @endcan
                    </div>
                </section>
                <section class="courrier-card xl:col-span-2">
                    <div class="courrier-section-heading"><span>03</span><div><h2>{{ __('ux.details') }}</h2><p>{{ __('ux.details_hint') }}</p></div></div>
                    @php
                        $groups = [
                            'correspondence' => ['description' => ['description', 'textarea'], 'date_courrier' => ['date_mail', 'date'], 'date_envoi' => ['date_sent', 'date']],
                            'property' => ['tf' => ['field_tf', 'text'], 'proprietaire' => ['field_proprietaire', 'text'], 'requerant' => ['field_requerant', 'text'], 'vendeur' => ['field_vendeur', 'text'], 'etude_notariale' => ['field_etude', 'text']],
                            'deed' => ['numero_acte' => ['field_numero_acte', 'text'], 'numero_repertoire' => ['field_numero_repertoire', 'text'], 'valeur_immeuble' => ['field_valeur', 'number']],
                        ];
                    @endphp
                    <div class="space-y-3">
                        @foreach($groups as $group => $fields)
                            @php($expanded = collect(array_keys($fields))->contains(fn ($field) => $errors->has($field) || filled(old($field))))
                            <details class="courrier-disclosure" @if($expanded) open @endif>
                                <summary>{{ __('ux.'.$group) }} <span>{{ __('ux.optional') }}</span></summary>
                                <div class="grid gap-6 p-4 sm:grid-cols-2 sm:p-5">
                                    @foreach($fields as $name => [$key, $type])
                                        @if($type === 'number')
                                            <x-courrier-field :name="$name" :label="__('courrier.'.$key)" type="number" min="0" step="0.01" />
                                        @else
                                            <x-courrier-field :name="$name" :label="__('courrier.'.$key)" :type="$type" :class="$type === 'textarea' ? 'sm:col-span-2' : ''" />
                                        @endif
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                    </div>
                </section>
            </div>
            <div class="courrier-form-footer">
                <p>{{ __('courrier.form_workflow_hint') }}</p>
                <div class="flex flex-wrap gap-2">
                    <x-buttons.secondary :href="route('courriers.index')">{{ __('courrier.cancel') }}</x-buttons.secondary>
                    <x-buttons.primary type="submit" data-busy-label="{{ __('ux.saving') }}">{{ __('courrier.save') }}</x-buttons.primary>
                </div>
            </div>
        </form>
    </div>
    @push('scripts')
        <script>
            (() => {
                const office = document.getElementById('bureau-destinataire');
                const agent = document.getElementById('agent-destinataire');
                const filterAgents = () => {
                    for (const option of agent.options) {
                        if (!option.value) continue;
                        const available = option.dataset.bureauId === office.value;
                        option.hidden = !available;
                        option.disabled = !available;
                        if (!available && option.selected) agent.value = '';
                    }
                    agent.disabled = !office.value;
                };
                office.addEventListener('change', filterAgents);
                filterAgents();
            })();
        </script>
    @endpush
</x-app-layout>
