@php
    $input = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30';
    $label = 'mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-bold text-gray-900">{{ __('users.create_title') }}</h1>
                <p class="mb-0 text-sm text-gray-500">{{ __('users.title') }}</p>
            </div>
            <x-buttons.secondary :href="route('admin.users.index')">
                {{ __('users.back') }}
            </x-buttons.secondary>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <div class="mb-1 font-semibold">{{ __('users.form_error') }}</div>
            <ul class="mb-0 list-disc pl-4">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div class="rounded-xl border border-dashed border-gray-300 bg-slate-50 p-4">
                <label class="{{ $label }}">{{ __('users.avatar') }}</label>
                <div class="mt-2 flex flex-wrap items-center gap-4">
                    <span class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-[var(--gutf-accent)] text-lg font-bold text-white" id="avatarPreviewFallback">+</span>
                    <img id="avatarPreview" src="" alt="" class="hidden h-16 w-16 rounded-full object-cover ring-2 ring-white">
                    <div class="min-w-0 flex-1">
                        <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp" class="{{ $input }}"
                               onchange="const f=this.files?.[0]; const img=document.getElementById('avatarPreview'); const fb=document.getElementById('avatarPreviewFallback'); if(f){img.src=URL.createObjectURL(f); img.classList.remove('hidden'); fb.classList.add('hidden');}">
                        <p class="mt-1 text-xs text-gray-400">{{ __('users.avatar_help') }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="{{ $label }}">{{ __('users.name') }}</label>
                    <input class="{{ $input }}" name="name" value="{{ old('name') }}" required>
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('users.email') }}</label>
                    <input type="email" class="{{ $input }}" name="email" value="{{ old('email') }}" required>
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('users.role') }}</label>
                    <select class="{{ $input }}" name="role" required>
                        <option value="" disabled @selected(! old('role'))>Choisir un rôle</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->name }}" @selected(old('role') === $r->name)>{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('users.bureau') }}</label>
                    <select class="{{ $input }}" name="bureau_id">
                        <option value="">{{ __('users.bureau_none') }}</option>
                        @foreach($bureaux as $b)
                            <option value="{{ $b->id }}" @selected((string) old('bureau_id') === (string) $b->id)>{{ $b->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('users.locale') }}</label>
                    <select class="{{ $input }}" name="locale" required>
                        <option value="fr" @selected(old('locale', 'fr') === 'fr')>FR</option>
                        <option value="en" @selected(old('locale') === 'en')>EN</option>
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('users.status') }}</label>
                    <select class="{{ $input }}" name="is_active">
                        <option value="1" @selected(old('is_active', '1') === '1')>{{ __('users.active') }}</option>
                        <option value="0" @selected(old('is_active') === '0')>{{ __('users.inactive') }}</option>
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('users.password') }}</label>
                    <input type="password" class="{{ $input }}" name="password" required>
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('users.password_confirm') }}</label>
                    <input type="password" class="{{ $input }}" name="password_confirmation" required>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 border-t border-gray-100 pt-5">
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
