@php
    $input = 'ui-input';
    $label = 'ui-label';
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-admin-page-header :title="__('users.create_title')" :subtitle="__('users.title')">
            <x-slot:actions>
                <x-buttons.secondary :href="route('admin.users.index')">
                    {{ __('users.back') }}
                </x-buttons.secondary>
            </x-slot:actions>
        </x-admin-page-header>
    </x-slot>

    <x-form-errors />

    <div class="ui-card p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                <label for="ui-avatar" class="{{ $label }}">{{ __('users.avatar') }}</label>
                <div class="mt-2 flex flex-wrap items-center gap-4">
                    <span class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-[var(--gutf-accent)] text-lg font-bold text-white" id="avatarPreviewFallback">+</span>
                    <img id="avatarPreview" src="" alt="" class="hidden h-16 w-16 rounded-full object-cover ring-2 ring-white">
                    <div class="min-w-0 flex-1">
                        <input id="ui-avatar" type="file" name="avatar" accept="image/png,image/jpeg,image/webp" class="{{ $input }}"
                               onchange="const f=this.files?.[0]; const img=document.getElementById('avatarPreview'); const fb=document.getElementById('avatarPreviewFallback'); if(f){img.src=URL.createObjectURL(f); img.classList.remove('hidden'); fb.classList.add('hidden');}">
                        <p class="mt-1 text-xs text-slate-400">{{ __('users.avatar_help') }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="{{ $label }}" for="ui-name">{{ __('users.name') }}</label>
                    <input id="ui-name" class="{{ $input }}" name="name" value="{{ old('name') }}" required>
                </div>
                <div>
                    <label class="{{ $label }}" for="ui-email">{{ __('users.email') }}</label>
                    <input id="ui-email" type="email" class="{{ $input }}" name="email" value="{{ old('email') }}" required>
                </div>
                <div>
                    <label class="{{ $label }}" for="ui-role">{{ __('users.role') }}</label>
                    <select id="ui-role" class="{{ $input }}" name="role" required>
                        <option value="" disabled @selected(! old('role'))>Choisir un rôle</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->name }}" @selected(old('role') === $r->name)>{{ __('users.roles.'.$r->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}" for="ui-bureau_id">{{ __('users.bureau') }}</label>
                    <select id="ui-bureau_id" class="{{ $input }}" name="bureau_id">
                        <option value="">{{ __('users.bureau_none') }}</option>
                        @foreach($bureaux as $b)
                            <option value="{{ $b->id }}" @selected((string) old('bureau_id') === (string) $b->id)>{{ $b->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}" for="ui-locale">{{ __('users.locale') }}</label>
                    <select id="ui-locale" class="{{ $input }}" name="locale" required>
                        <option value="fr" @selected(old('locale', 'fr') === 'fr')>FR</option>
                        <option value="en" @selected(old('locale') === 'en')>EN</option>
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}" for="ui-is_active">{{ __('users.status') }}</label>
                    <select id="ui-is_active" class="{{ $input }}" name="is_active" required>
                        <option value="1" @selected(old('is_active', '1') === '1')>{{ __('users.active') }}</option>
                        <option value="0" @selected(old('is_active') === '0')>{{ __('users.inactive') }}</option>
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}" for="ui-password">{{ __('users.password') }}</label>
                    <input id="ui-password" type="password" class="{{ $input }}" name="password" required>
                </div>
                <div>
                    <label class="{{ $label }}" for="ui-password_confirmation">{{ __('users.password_confirm') }}</label>
                    <input id="ui-password_confirmation" type="password" class="{{ $input }}" name="password_confirmation" required>
                </div>
            </div>

            <x-role-guide />
            <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-5">
                <x-buttons.primary type="submit">
                    {{ __('users.save') }}
                </x-buttons.primary>
                <x-buttons.secondary :href="route('admin.users.index')">
                    {{ __('users.cancel') }}
                </x-buttons.secondary>
            </div>
        </form>
    </div>
</x-app-layout>
