<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between">
            <h1 class="h4 mb-0">{{ __('courrier.create_title') }}</h1>
            <a href="{{ route('courriers.index') }}" class="btn btn-outline-secondary">
                {{ __('courrier.back') }}
            </a>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">{{ __('courrier.form_error_title') }}</div>
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('courriers.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('courrier.type') }}</label>
                        <select name="type" class="form-select" required>
                            <option value="entrant" @selected(old('type')==='entrant')>{{ __('courrier.type_in') }}</option>
                            <option value="sortant" @selected(old('type')==='sortant')>{{ __('courrier.type_out') }}</option>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">{{ __('courrier.subject') }}</label>
                        <input name="objet" class="form-control" value="{{ old('objet') }}" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">{{ __('courrier.description') }}</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('courrier.external_ref') }}</label>
                        <input name="reference_externe" class="form-control" value="{{ old('reference_externe') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('courrier.assign_to') }}</label>
                        <select name="assigned_to_user_id" class="form-select">
                            <option value="">{{ __('courrier.assign_none') }}</option>
                            @foreach($agents as $a)
                                <option value="{{ $a->id }}" @selected(old('assigned_to_user_id')==$a->id)>{{ $a->name }} ({{ $a->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('courrier.sender') }}</label>
                        <input name="expediteur" class="form-control" value="{{ old('expediteur') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('courrier.recipient') }}</label>
                        <input name="destinataire" class="form-control" value="{{ old('destinataire') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('courrier.date_mail') }}</label>
                        <input type="date" name="date_courrier" class="form-control" value="{{ old('date_courrier') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('courrier.date_received') }}</label>
                        <input type="date" name="date_reception" class="form-control" value="{{ old('date_reception') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('courrier.date_sent') }}</label>
                        <input type="date" name="date_envoi" class="form-control" value="{{ old('date_envoi') }}">
                    </div>
                </div>

                <hr class="my-4">

                <div class="row g-2">
                    <div class="col-12">
                        <div class="fw-semibold">{{ __('courrier.document_title') }}</div>
                        <div class="text-muted small">{{ __('courrier.document_help') }}</div>
                    </div>

                    @can('courrier.attach')
                        <div class="col-md-8">
                            <input type="file" name="document_file" class="form-control">
                            <div class="form-text">{{ __('courrier.attachments_help') }}</div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="text-muted small">{{ __('courrier.document_optional') }}</div>
                        </div>
                    @else
                        <div class="col-12">
                            <div class="alert alert-light border mb-0">
                                <div class="text-muted small">{{ __('courrier.no_attach_permission') }}</div>
                            </div>
                        </div>
                    @endcan
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-gutf" type="submit">{{ __('courrier.save') }}</button>
                    <a class="btn btn-outline-secondary" href="{{ route('courriers.index') }}">{{ __('courrier.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
