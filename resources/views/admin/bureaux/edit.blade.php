@php
    $input = 'ui-input';
    $label = 'ui-label';
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-admin-page-header :title="__('bureaux.edit_title')" :subtitle="$bureau->nom">
            <x-slot:actions>
                <x-buttons.secondary :href="route('admin.bureaux.index')">
                    {{ __('bureaux.back') }}
                </x-buttons.secondary>
            </x-slot:actions>
        </x-admin-page-header>
    </x-slot>

    @if (session('status'))
        <div class="mb-4 ui-alert ui-alert-success">
            {{ session('status') }}
        </div>
    @endif

    <x-form-errors />

    <div class="ui-card p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.bureaux.update', $bureau) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="{{ $label }}" for="ui-nom">{{ __('bureaux.nom') }}</label>
                    <input id="ui-nom" class="{{ $input }}" name="nom" value="{{ old('nom', $bureau->nom) }}" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $label }}" for="ui-description">{{ __('bureaux.description') }}</label>
                    <textarea id="ui-description" class="{{ $input }}" name="description" rows="3">{{ old('description', $bureau->description) }}</textarea>
                </div>
                <div>
                    <label class="{{ $label }}" for="ui-code">{{ __('bureaux.code') }}</label>
                    <input id="ui-code" class="{{ $input }}" name="code" value="{{ old('code', $bureau->code) }}" required>
                </div>
                <div>
                    <label class="{{ $label }}" for="ui-ordre">{{ __('bureaux.ordre') }}</label>
                    <input id="ui-ordre" type="number" min="0" class="{{ $input }}" name="ordre" value="{{ old('ordre', $bureau->ordre) }}">
                </div>
                <div>
                    <label class="{{ $label }}" for="ui-is_active">{{ __('bureaux.status') }}</label>
                    <select id="ui-is_active" class="{{ $input }}" name="is_active">
                        <option value="1" @selected(old('is_active', (string) (int) $bureau->is_active) === '1')>{{ __('bureaux.active') }}</option>
                        <option value="0" @selected(old('is_active', (string) (int) $bureau->is_active) === '0')>{{ __('bureaux.inactive') }}</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-5">
                <x-buttons.primary type="submit">
                    {{ __('bureaux.save') }}
                </x-buttons.primary>
                <x-buttons.secondary :href="route('admin.bureaux.index')">
                    {{ __('bureaux.cancel') }}
                </x-buttons.secondary>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.bureaux.destroy', $bureau) }}"
              class="mt-6 border-t border-slate-100 pt-5"
              data-confirm-message="{{ __('bureaux.confirm_delete') }}" onsubmit="return confirm(this.dataset.confirmMessage)">
            @csrf
            @method('DELETE')
            <x-buttons.danger type="submit">
                {{ __('bureaux.delete') }}
            </x-buttons.danger>
        </form>
    </div>
</x-app-layout>
