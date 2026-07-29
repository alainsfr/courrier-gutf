<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="mb-1 text-lg font-bold text-slate-900">{{ __('courrier.create_title') }}</h1>
                <p class="mb-0 text-sm text-slate-500">{{ __('courrier.create_subtitle') }}</p>
            </div>
            <a href="{{ route('courriers.index') }}"
               class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 no-underline hover:bg-slate-50">
                ← {{ __('courrier.back') }}
            </a>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <p class="mb-2 font-bold">{{ __('courrier.form_error_title') }}</p>
            <ul class="mb-0 list-disc ps-4">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('courriers.store') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
            <div class="space-y-5 xl:col-span-2">

                {{-- Classification --}}
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-bold text-slate-900">{{ __('courrier.form_section_class') }}</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                {{ __('courrier.type') }} <span class="text-rose-500">*</span>
                            </label>
                            <select name="type" required
                                    class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                                <option value="entrant" @selected(old('type', 'entrant') === 'entrant')>{{ __('courrier.type_in') }}</option>
                                <option value="sortant" @selected(old('type') === 'sortant')>{{ __('courrier.type_out') }}</option>
                                <option value="interne" @selected(old('type') === 'interne')>{{ __('courrier.type_internal') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                {{ __('courrier.field_tf') }}
                            </label>
                            <input type="text" name="tf" value="{{ old('tf') }}"
                                   placeholder="{{ __('courrier.field_tf_placeholder') }}"
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                {{ __('courrier.subject') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="objet" value="{{ old('objet') }}" required
                                   placeholder="{{ __('courrier.field_objet_placeholder') }}"
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                {{ __('courrier.description') }}
                            </label>
                            <textarea name="description" rows="3"
                                      placeholder="{{ __('courrier.field_description_placeholder') }}"
                                      class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">{{ old('description') }}</textarea>
                        </div>

                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                {{ __('courrier.external_ref') }}
                            </label>
                            <input type="text" name="reference_externe" value="{{ old('reference_externe') }}"
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>
                    </div>
                </section>

                {{-- Parties --}}
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-bold text-slate-900">{{ __('courrier.form_section_parties') }}</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                {{ __('courrier.sender') }}
                            </label>
                            <input type="text" name="expediteur" value="{{ old('expediteur') }}"
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                {{ __('courrier.recipient') }}
                            </label>
                            <input type="text" name="destinataire" value="{{ old('destinataire') }}"
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                {{ __('courrier.field_proprietaire') }}
                            </label>
                            <input type="text" name="proprietaire" value="{{ old('proprietaire') }}"
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                {{ __('courrier.field_requerant') }}
                            </label>
                            <input type="text" name="requerant" value="{{ old('requerant') }}"
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                {{ __('courrier.field_vendeur') }}
                            </label>
                            <input type="text" name="vendeur" value="{{ old('vendeur') }}"
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                {{ __('courrier.field_etude') }}
                            </label>
                            <input type="text" name="etude_notariale" value="{{ old('etude_notariale') }}"
                                   placeholder="{{ __('courrier.field_etude_placeholder') }}"
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>
                    </div>
                </section>

                {{-- Acte & bien --}}
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-bold text-slate-900">{{ __('courrier.form_section_acte') }}</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                {{ __('courrier.field_numero_acte') }}
                            </label>
                            <input type="text" name="numero_acte" value="{{ old('numero_acte') }}"
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                {{ __('courrier.field_numero_repertoire') }}
                            </label>
                            <input type="text" name="numero_repertoire" value="{{ old('numero_repertoire') }}"
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                {{ __('courrier.field_valeur') }}
                            </label>
                            <input type="number" step="0.01" min="0" name="valeur_immeuble" value="{{ old('valeur_immeuble') }}"
                                   placeholder="0.00"
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>
                    </div>
                </section>

                {{-- Dates --}}
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-bold text-slate-900">{{ __('courrier.form_section_dates') }}</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                {{ __('courrier.date_mail') }}
                            </label>
                            <input type="date" name="date_courrier" value="{{ old('date_courrier', now()->format('Y-m-d')) }}"
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                {{ __('courrier.date_received') }}
                            </label>
                            <input type="date" name="date_reception" value="{{ old('date_reception', now()->format('Y-m-d')) }}"
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                {{ __('courrier.date_sent') }}
                            </label>
                            <input type="date" name="date_envoi" value="{{ old('date_envoi') }}"
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>
                    </div>
                </section>
            </div>

            {{-- Colonne latérale --}}
            <div class="space-y-5">
                <section class="rounded-2xl border border-sky-100 bg-sky-50/60 p-5 shadow-sm">
                    <h2 class="mb-2 text-sm font-bold text-slate-900">{{ __('courrier.form_workflow_hint_title') }}</h2>
                    <p class="mb-0 text-xs leading-relaxed text-slate-600">
                        {{ __('courrier.form_workflow_hint') }}
                    </p>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-3 text-sm font-bold text-slate-900">{{ __('courrier.form_section_assign') }}</h2>
                    <p class="mb-3 text-xs text-slate-500">{{ __('courrier.form_assign_help') }}</p>
                    <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        {{ __('courrier.assign_to') }}
                    </label>
                    <select name="assigned_to_user_id"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        <option value="">{{ __('courrier.assign_none') }}</option>
                        @foreach($agents as $a)
                            <option value="{{ $a->id }}" @selected(old('assigned_to_user_id') == $a->id)>
                                {{ $a->name }} ({{ $a->email }})
                            </option>
                        @endforeach
                    </select>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-3 text-sm font-bold text-slate-900">{{ __('courrier.document_title') }}</h2>
                    <p class="mb-3 text-xs text-slate-500">{{ __('courrier.document_help') }}</p>
                    @can('courrier.attach')
                        <input type="file" name="document_file"
                               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700">
                        <p class="mt-2 mb-0 text-xs text-slate-400">{{ __('courrier.attachments_help') }}</p>
                    @else
                        <p class="mb-0 rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800">
                            {{ __('courrier.no_attach_permission') }}
                        </p>
                    @endcan
                </section>

                <div class="flex flex-col gap-2">
                    <button type="submit"
                            class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                        {{ __('courrier.save') }}
                    </button>
                    <a href="{{ route('courriers.index') }}"
                       class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-center text-sm font-semibold text-slate-600 no-underline hover:bg-slate-50">
                        {{ __('courrier.cancel') }}
                    </a>
                </div>
            </div>
        </div>
    </form>
</x-app-layout>
