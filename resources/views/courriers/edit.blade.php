@php
    $input = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 focus:border-[#17384b] focus:outline-none focus:ring-2 focus:ring-[#17384b]/20';
    $fields = [
        'objet' => ['subject', 'text'], 'reference_externe' => ['external_ref', 'text'],
        'tf' => ['field_tf', 'text'], 'expediteur' => ['sender', 'text'], 'destinataire' => ['recipient', 'text'],
        'proprietaire' => ['field_proprietaire', 'text'], 'requerant' => ['field_requerant', 'text'],
        'vendeur' => ['field_vendeur', 'text'], 'etude_notariale' => ['field_etude', 'text'],
        'numero_acte' => ['field_numero_acte', 'text'], 'numero_repertoire' => ['field_numero_repertoire', 'text'],
        'valeur_immeuble' => ['field_valeur', 'number'], 'date_courrier' => ['date_mail', 'date'],
        'date_reception' => ['date_received', 'date'], 'date_envoi' => ['date_sent', 'date'],
    ];
@endphp
<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-bold text-gray-900">{{ __('courrier.correct_title') }}</h1>
        <p class="mb-0 text-sm text-gray-500">{{ $courrier->reference_interne }} · {{ __('courrier.correct_hint') }}</p>
    </x-slot>
    <x-form-errors />
    <form method="POST" action="{{ route('courriers.update', $courrier) }}" class="mx-auto max-w-4xl space-y-5 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        <input type="hidden" name="version" value="{{ old('version', $version) }}">
        <div class="grid gap-5 sm:grid-cols-2">
            @foreach($fields as $field => [$label, $type])
                <div class="{{ $field === 'objet' ? 'sm:col-span-2' : '' }}">
                    <label for="correct-{{ $field }}" class="mb-1.5 block text-xs font-semibold text-gray-600">{{ __('courrier.'.$label) }} {{ $field === 'objet' ? '*' : '' }}</label>
                    <input id="correct-{{ $field }}" name="{{ $field }}" type="{{ $type }}" class="{{ $input }}"
                           value="{{ old($field, $type === 'date' ? $courrier->{$field}?->format('Y-m-d') : $courrier->{$field}) }}"
                           @required($field === 'objet') @if($type === 'number') min="0" step="0.01" @endif>
                    @error($field)<p class="mt-1 text-xs text-rose-700" role="alert">{{ $message }}</p>@enderror
                </div>
            @endforeach
            <div class="sm:col-span-2">
                <label for="correct-description" class="mb-1.5 block text-xs font-semibold text-gray-600">{{ __('courrier.description') }}</label>
                <textarea id="correct-description" name="description" rows="3" class="{{ $input }}">{{ old('description', $courrier->description) }}</textarea>
            </div>
        </div>
        <div class="border-t border-gray-100 pt-5">
            <label for="correction-reason" class="mb-1.5 block text-xs font-semibold text-gray-600">{{ __('courrier.change_reason') }} *</label>
            <textarea id="correction-reason" name="reason" required minlength="5" maxlength="2000" rows="2" class="{{ $input }}" placeholder="{{ __('courrier.reason_placeholder') }}">{{ old('reason') }}</textarea>
            <p class="mt-1.5 text-xs text-gray-500">{{ __('courrier.reason_audit') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-buttons.primary type="submit">{{ __('courrier.save_correction') }}</x-buttons.primary>
            <x-buttons.secondary :href="route('courriers.show', $courrier)">{{ __('courrier.back') }}</x-buttons.secondary>
        </div>
    </form>
</x-app-layout>
