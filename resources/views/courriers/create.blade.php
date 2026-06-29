<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h1 class="h4 mb-1 text-dark fw-bold">{{ __('courrier.create_title') }}</h1>
                <p class="text-muted small mb-0">Enregistrement d'un nouveau pli au registre du GUTF</p>
            </div>
            <a href="{{ route('courriers.index') }}" class="btn btn-outline-secondary px-4 rounded-3 fw-semibold">
                ← {{ __('courrier.back') }}
            </a>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4">
            <div class="fw-bold mb-2">{{ __('courrier.form_error_title') }}</div>
            <ul class="mb-0 small">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('courriers.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="row g-4">
            {{-- Colonne Principale : Informations & Contacts --}}
            <div class="col-lg-8">
                
                {{-- Carte 1 : Classification du Courrier --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-4">
                            <span class="fs-4">📝</span>
                            <h5 class="card-title fw-bold text-dark mb-0">Classification du courrier</h5>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-muted small">{{ __('courrier.type') }} <span class="text-danger">*</span></label>
                                <select name="type" class="form-select border-slate-200 rounded-3" required>
                                    <option value="entrant" @selected(old('type')==='entrant')>📥 {{ __('courrier.type_in') }}</option>
                                    <option value="sortant" @selected(old('type')==='sortant')>📤 {{ __('courrier.type_out') }}</option>
                                </select>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label fw-semibold text-muted small">{{ __('courrier.subject') }} <span class="text-danger">*</span></label>
                                <input name="objet" class="form-control border-slate-200 rounded-3" value="{{ old('objet') }}" required placeholder="Ex: Demande de transcription d'acte de naissance">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold text-muted small">{{ __('courrier.description') }}</label>
                                <textarea name="description" class="form-control border-slate-200 rounded-3" rows="4" placeholder="Résumé ou notes supplémentaires concernant le pli..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Carte 2 : Provenance & Destination --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-4">
                            <span class="fs-4">🏢</span>
                            <h5 class="card-title fw-bold text-dark mb-0">Identification des parties</h5>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small">{{ __('courrier.sender') }}</label>
                                <input name="expediteur" class="form-control border-slate-200 rounded-3" value="{{ old('expediteur') }}" placeholder="Nom de l'expéditeur (ex: Consulat du Cameroun à Paris)">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small">{{ __('courrier.recipient') }}</label>
                                <input name="destinataire" class="form-control border-slate-200 rounded-3" value="{{ old('destinataire') }}" placeholder="Service destinataire au sein du GUTF">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Carte 3 : Dates & Calendrier --}}
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-4">
                            <span class="fs-4">📅</span>
                            <h5 class="card-title fw-bold text-dark mb-0">Dates de suivi</h5>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-muted small">{{ __('courrier.date_mail') }}</label>
                                <input type="date" name="date_courrier" class="form-control border-slate-200 rounded-3" value="{{ old('date_courrier', now()->format('Y-m-d')) }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-muted small">{{ __('courrier.date_received') }}</label>
                                <input type="date" name="date_reception" class="form-control border-slate-200 rounded-3" value="{{ old('date_reception', now()->format('Y-m-d')) }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-muted small">{{ __('courrier.date_sent') }}</label>
                                <input type="date" name="date_envoi" class="form-control border-slate-200 rounded-3" value="{{ old('date_envoi') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Colonne Latérale : Traitement & Document Numérique --}}
            <div class="col-lg-4">
                
                {{-- Carte 4 : Affectation du pli --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="fs-4">📌</span>
                            <h5 class="card-title fw-bold text-dark mb-0">Traitement</h5>
                        </div>
                        <p class="text-muted small">Affectez directement ce courrier à un agent pour traitement immédiat.</p>

                        <div class="mb-2">
                            <label class="form-label fw-semibold text-muted small">{{ __('courrier.assign_to') }}</label>
                            <select name="assigned_to_user_id" class="form-select border-slate-200 rounded-3">
                                <option value="">{{ __('courrier.assign_none') }}</option>
                                @foreach($agents as $a)
                                    <option value="{{ $a->id }}" @selected(old('assigned_to_user_id') == $a->id)>
                                        {{ $a->name }} ({{ $a->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Carte 5 : Numérisation & Fichiers --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="fs-4">📎</span>
                            <h5 class="card-title fw-bold text-dark mb-0">Document numérisé</h5>
                        </div>
                        <p class="text-muted small">{{ __('courrier.document_help') }}</p>

                        @can('courrier.attach')
                            <div class="mb-2">
                                <input type="file" name="document_file" class="form-control border-slate-200 rounded-3">
                                <div class="form-text small mt-2">{{ __('courrier.attachments_help') }}</div>
                            </div>
                        @else
                            <div class="alert alert-warning border-0 small mb-0 rounded-3">
                                {{ __('courrier.no_attach_permission') }}
                            </div>
                        @endcan
                    </div>
                </div>

                {{-- Boutons d'Action --}}
                <div class="card border-0 bg-transparent mb-4">
                    <div class="d-grid gap-2">
                        <button class="btn btn-gutf py-3 rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2" type="submit">
                            <span>💾</span> {{ __('courrier.save') }}
                        </button>
                        <a class="btn btn-light py-3 border border-slate-200 rounded-3" href="{{ route('courriers.index') }}">
                            {{ __('courrier.cancel') }}
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </form>
</x-app-layout>