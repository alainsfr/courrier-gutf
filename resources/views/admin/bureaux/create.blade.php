@php
    $input = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30';
    $label = 'mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-bold text-gray-900">{{ __('bureaux.create_title') }}</h1>
                <p class="mb-0 text-sm text-gray-500">{{ __('bureaux.title') }}</p>
            </div>
            <x-buttons.secondary :href="route('admin.bureaux.index')">
                {{ __('bureaux.back') }}
            </x-buttons.secondary>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <div class="mb-1 font-semibold">{{ __('bureaux.form_error') }}</div>
            <ul class="mb-0 list-disc pl-4">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.bureaux.store') }}" class="space-y-5">
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">{{ __('bureaux.nom') }}</label>
                    <input class="{{ $input }}" name="nom" value="{{ old('nom') }}" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">{{ __('bureaux.description') }}</label>
                    <textarea class="{{ $input }}" name="description" rows="3">{{ old('description') }}</textarea>
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('bureaux.code') }}</label>
                    <input class="{{ $input }}" name="code" value="{{ old('code') }}" placeholder="ex. RECEPTION">
                    <p class="mb-0 mt-1 text-xs text-gray-400">{{ __('bureaux.code_hint') }}</p>
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('bureaux.ordre') }}</label>
                    <input type="number" min="0" class="{{ $input }}" name="ordre" value="{{ old('ordre', 0) }}">
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('bureaux.status') }}</label>
                    <select class="{{ $input }}" name="is_active">
                        <option value="1" @selected(old('is_active', '1') === '1')>{{ __('bureaux.active') }}</option>
                        <option value="0" @selected(old('is_active') === '0')>{{ __('bureaux.inactive') }}</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 border-t border-gray-100 pt-5">
                <x-buttons.primary type="submit">
                    {{ __('bureaux.save') }}
                </x-buttons.primary>
                <x-buttons.secondary :href="route('admin.bureaux.index')">
                    {{ __('bureaux.cancel') }}
                </x-buttons.secondary>
            </div>
        </form>
    </div>
</x-app-layout>
