@php
    $input = 'ui-input';
    $label = 'ui-label';
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-admin-page-header :title="__('users.edit_title')" :subtitle="$user->name">
            <x-slot:actions>
                <x-buttons.secondary :href="route('admin.users.index')">
                    {{ __('users.back') }}
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
    <div class="mb-5"><x-role-guide /></div>

    <div class="ui-card p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                <label for="ui-avatar" class="{{ $label }}">{{ __('users.avatar') }}</label>
                <div class="mt-2 flex flex-wrap items-center gap-4">
                    <x-user-avatar :user="$user" size="lg" />
                    <div class="min-w-0 flex-1">
                        <input id="ui-avatar" type="file" name="avatar" accept="image/png,image/jpeg,image/webp" class="{{ $input }}">
                        <p class="mt-1 text-xs text-slate-400">{{ __('users.avatar_help') }}</p>
                        @if ($user->avatar_path)
                            <label class="mt-2 flex items-center gap-2 text-xs text-slate-600">
                                <input type="checkbox" name="remove_avatar" value="1"> {{ __('users.remove_avatar') }}
                            </label>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="{{ $label }}" for="ui-name">{{ __('users.name') }}</label>
                    <input id="ui-name" class="{{ $input }}" name="name" value="{{ old('name', $user->name) }}" required>
                </div>
                <div>
                    <label class="{{ $label }}" for="ui-email">{{ __('users.email') }}</label>
                    <input id="ui-email" type="email" class="{{ $input }}" name="email" value="{{ old('email', $user->email) }}" required>
                </div>
                <div>
                    <label class="{{ $label }}" for="ui-role">{{ __('users.role') }}</label>
                    <select id="ui-role" class="{{ $input }}" name="role" required>
                        @foreach($roles as $r)
                            <option value="{{ $r->name }}" @selected(old('role', $currentRole) === $r->name)>{{ __('users.roles.'.$r->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}" for="ui-bureau_id">{{ __('users.bureau') }}</label>
                    <select id="ui-bureau_id" class="{{ $input }}" name="bureau_id">
                        <option value="">{{ __('users.bureau_none') }}</option>
                        @foreach($bureaux as $b)
                            <option value="{{ $b->id }}" @selected((string) old('bureau_id', $user->bureau_id) === (string) $b->id)>{{ $b->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}" for="ui-locale">{{ __('users.locale') }}</label>
                    <select id="ui-locale" class="{{ $input }}" name="locale" required>
                        <option value="fr" @selected(old('locale', $user->locale) === 'fr')>FR</option>
                        <option value="en" @selected(old('locale', $user->locale) === 'en')>EN</option>
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}" for="user-active">{{ __('users.status') }}</label>
                    @if ((int) $user->id === (int) auth()->id())
                        <input type="hidden" name="is_active" value="1">
                        <select id="user-active" class="{{ $input }} bg-slate-50" disabled>
                            <option value="1" selected>{{ __('users.active') }}</option>
                        </select>
                        <p class="mt-1 mb-0 text-xs text-slate-400">{{ __('users.cannot_deactivate_self') }}</p>
                    @else
                        <select id="user-active" class="{{ $input }}" name="is_active" required>
                            <option value="1" @selected(old('is_active', $user->is_active ? '1' : '0') === '1')>{{ __('users.active') }}</option>
                            <option value="0" @selected(old('is_active', $user->is_active ? '1' : '0') === '0')>{{ __('users.inactive') }}</option>
                        </select>
                    @endif
                </div>
                <div>
                    <label class="{{ $label }}" for="ui-password">{{ __('users.password_optional') }}</label>
                    <input id="ui-password" type="password" class="{{ $input }}" name="password" autocomplete="new-password">
                </div>
                <div>
                    <label class="{{ $label }}" for="ui-password_confirmation">{{ __('users.password_confirm') }}</label>
                    <input id="ui-password_confirmation" type="password" class="{{ $input }}" name="password_confirmation" autocomplete="new-password">
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5">
                <div class="flex flex-wrap gap-2">
                    <x-buttons.primary type="submit">
                        {{ __('users.save') }}
                    </x-buttons.primary>
                    <x-buttons.secondary :href="route('admin.users.index')">
                        {{ __('users.cancel') }}
                    </x-buttons.secondary>
                </div>
            </div>
        </form>

        @if((int) $user->id !== (int) auth()->id())
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                  class="mt-6 border-t border-slate-100 pt-5"
                  data-confirm-message="{{ __('users.confirm_delete') }}" onsubmit="return confirm(this.dataset.confirmMessage)">
                @csrf
                @method('DELETE')
                <x-buttons.danger type="submit">
                    {{ __('users.delete') }}
                </x-buttons.danger>
            </form>
        @endif
    </div>
</x-app-layout>
