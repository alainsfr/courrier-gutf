@php
    $input = 'w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30';
    $label = 'mb-1.5 block text-xs font-semibold text-gray-600';
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="mb-1 text-lg font-bold text-gray-900">{{ __('courrier.create_title') }}</h1>
                <p class="mb-0 text-sm text-gray-500">{{ __('courrier.create_subtitle') }}</p>
            </div>
            <a href="{{ route('courriers.index') }}"
               class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-600 no-underline hover:bg-gray-50">
                ← {{ __('courrier.back') }}
            </a>
        </div>
    </x-slot>

    <x-form-errors class="mb-5" />

    <form method="POST" action="{{ route('courriers.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm md:p-8">
            {{-- Classification --}}
            <section>
                <h2 class="mb-5 text-lg font-semibold text-gray-900">{{ __('courrier.form_section_class') }}</h2>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="{{ $label }}">{{ __('courrier.type') }} <span class="text-rose-500">*</span></label>
                        <select name="type" required class="{{ $input }}">
                            <option value="entrant" @selected(old('type', 'entrant') === 'entrant')>{{ __('courrier.type_in') }}</option>
                            <option value="sortant" @selected(old('type') === 'sortant')>{{ __('courrier.type_out') }}</option>
                            <option value="interne" @selected(old('type') === 'interne')>{{ __('courrier.type_internal') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">{{ __('courrier.field_tf') }}</label>
                        <input type="text" name="tf" value="{{ old('tf') }}" placeholder="{{ __('courrier.field_tf_placeholder') }}" class="{{ $input }}">
                    </div>
                    <div class="md:col-span-2">
                        <label class="{{ $label }}">{{ __('courrier.subject') }} <span class="text-rose-500">*</span></label>
                        <input type="text" name="objet" value="{{ old('objet') }}" required placeholder="{{ __('courrier.field_objet_placeholder') }}" class="{{ $input }}">
                    </div>
                    <div class="md:col-span-2">
                        <label class="{{ $label }}">{{ __('courrier.description') }}</label>
                        <textarea name="description" rows="2" placeholder="{{ __('courrier.field_description_placeholder') }}" class="{{ $input }}">{{ old('description') }}</textarea>
                    </div>
                    <div>
                        <label class="{{ $label }}">{{ __('courrier.external_ref') }}</label>
                        <input type="text" name="reference_externe" value="{{ old('reference_externe') }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">{{ __('courrier.date_mail') }}</label>
                        <input type="date" name="date_courrier" value="{{ old('date_courrier', now()->format('Y-m-d')) }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">{{ __('courrier.date_received') }}</label>
                        <input type="date" name="date_reception" value="{{ old('date_reception', now()->format('Y-m-d')) }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">{{ __('courrier.date_sent') }}</label>
                        <input type="date" name="date_envoi" value="{{ old('date_envoi') }}" class="{{ $input }}">
                    </div>
                </div>
            </section>

            <hr class="my-8 border-gray-200">

            {{-- Informations foncières --}}
            <section>
                <h2 class="mb-5 text-lg font-semibold text-gray-900">{{ __('courrier.form_section_foncier') }}</h2>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="{{ $label }}">{{ __('courrier.sender') }}</label>
                        <input type="text" name="expediteur" value="{{ old('expediteur') }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">{{ __('courrier.recipient') }}</label>
                        <input type="text" name="destinataire" value="{{ old('destinataire') }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">{{ __('courrier.field_proprietaire') }}</label>
                        <input type="text" name="proprietaire" value="{{ old('proprietaire') }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">{{ __('courrier.field_requerant') }}</label>
                        <input type="text" name="requerant" value="{{ old('requerant') }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">{{ __('courrier.field_vendeur') }}</label>
                        <input type="text" name="vendeur" value="{{ old('vendeur') }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">{{ __('courrier.field_etude') }}</label>
                        <input type="text" name="etude_notariale" value="{{ old('etude_notariale') }}" placeholder="{{ __('courrier.field_etude_placeholder') }}" class="{{ $input }}">
                    </div>
                </div>
            </section>

            <hr class="my-8 border-gray-200">

            {{-- Acte --}}
            <section>
                <h2 class="mb-5 text-lg font-semibold text-gray-900">{{ __('courrier.form_section_acte') }}</h2>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="{{ $label }}">{{ __('courrier.field_numero_acte') }}</label>
                        <input type="text" name="numero_acte" value="{{ old('numero_acte') }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">{{ __('courrier.field_numero_repertoire') }}</label>
                        <input type="text" name="numero_repertoire" value="{{ old('numero_repertoire') }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">{{ __('courrier.field_valeur') }}</label>
                        <input type="number" step="0.01" min="0" name="valeur_immeuble" value="{{ old('valeur_immeuble') }}" placeholder="0.00" class="{{ $input }}">
                    </div>
                    <div>
                        <label for="bureau-destinataire" class="{{ $label }}">{{ __('courrier.destination_office') }} <span class="text-rose-500">*</span></label>
                        <select id="bureau-destinataire" name="bureau_actuel_id" required class="{{ $input }}">
                            <option value="">{{ __('courrier.destination_office_choose') }}</option>
                            @foreach($bureaux as $bureau)
                                <option value="{{ $bureau->id }}" @selected((string) old('bureau_actuel_id') === (string) $bureau->id)>{{ $bureau->nom }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-xs text-gray-500">{{ __('courrier.form_assign_help') }}</p>
                    </div>
                    <div>
                        <label class="{{ $label }}">{{ __('courrier.assign_to') }}</label>
                        <select id="agent-destinataire" name="assigned_to_user_id" class="{{ $input }}">
                            <option value="">{{ __('courrier.assign_none') }}</option>
                            @foreach($agents as $a)
                                <option value="{{ $a->id }}" data-bureau-id="{{ $a->bureau_id }}" @selected(old('assigned_to_user_id') == $a->id)>
                                    {{ $a->name }} ({{ $a->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="{{ $label }}">{{ __('courrier.document_title') }}</label>
                        @can('courrier.attach')
                            <input type="file" name="document_file"
                                   class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                            <p class="mt-1.5 mb-0 text-xs text-gray-400">{{ __('courrier.attachments_help') }}</p>
                        @else
                            <p class="mb-0 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                {{ __('courrier.no_attach_permission') }}
                            </p>
                        @endcan
                    </div>
                </div>
            </section>

            <div class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 pt-6">
                <p class="mb-0 max-w-md text-xs text-gray-500">{{ __('courrier.form_workflow_hint') }}</p>
                <div class="flex flex-wrap gap-2">
                    <x-buttons.secondary :href="route('courriers.index')">
                        {{ __('courrier.cancel') }}
                    </x-buttons.secondary>
                    <x-buttons.primary type="submit">
                        {{ __('courrier.save') }}
                    </x-buttons.primary>
                </div>
            </div>
        </div>
    </form>
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
