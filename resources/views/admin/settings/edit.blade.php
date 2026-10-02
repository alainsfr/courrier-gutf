@php
    $input = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 transition focus:border-[var(--gutf-primary)] focus:outline-none focus:ring-2 focus:ring-[color-mix(in_srgb,var(--gutf-primary)_25%,transparent)]';
    $label = 'mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500';
    $previewAppName = old('app_name', $settings['app_name']);
    $previewTagline = old('app_tagline', $settings['app_tagline']);
    $previewPrimary = old('primary_color', $settings['primary_color']);
    $previewAccent = old('accent_color', $settings['accent_color']);
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-admin-page-header :title="__('settings.title')" :subtitle="__('settings.subtitle')" />
    </x-slot>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
            {{ session('status') }}
        </div>
    @endif

    <x-form-errors />

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm xl:col-span-2">
                <h2 class="mb-1 text-sm font-bold text-gray-900">{{ __('settings.branding') }}</h2>
                <p class="mb-4 text-xs text-gray-400">{{ __('settings.branding_help') }}</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}" for="app_name">{{ __('settings.app_name') }}</label>
                        <input id="app_name" class="{{ $input }} @error('app_name') border-rose-400 @enderror"
                               name="app_name" value="{{ old('app_name', $settings['app_name']) }}" required maxlength="120">
                        <x-input-error class="mt-1" :messages="$errors->get('app_name')" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}" for="app_tagline">{{ __('settings.app_tagline') }}</label>
                        <input id="app_tagline" class="{{ $input }} @error('app_tagline') border-rose-400 @enderror"
                               name="app_tagline" value="{{ old('app_tagline', $settings['app_tagline']) }}" maxlength="255">
                        <x-input-error class="mt-1" :messages="$errors->get('app_tagline')" />
                    </div>

                    <div>
                        <label class="{{ $label }}" for="logo">{{ __('settings.logo') }}</label>
                        @if ($logoUrl)
                            <div class="mb-2 flex items-center gap-3 rounded-lg border border-gray-100 bg-slate-50 p-2">
                                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white ring-1 ring-gray-200 p-1.5">
                                    <img src="{{ $logoUrl }}" alt="{{ __('settings.logo') }}" class="h-full w-full object-contain">
                                </span>
                                <span class="text-xs text-gray-500">{{ __('settings.current_file') }}</span>
                            </div>
                        @endif
                        <input id="logo" type="file" name="logo" accept=".png,.jpg,.jpeg,.webp,.svg,image/png,image/jpeg,image/webp,image/svg+xml"
                               class="{{ $input }} @error('logo') border-rose-400 @enderror">
                        <p class="mt-1 text-xs text-gray-400">{{ __('settings.logo_help') }}</p>
                        <x-input-error class="mt-1" :messages="$errors->get('logo')" />
                        @if ($logoUrl)
                            <label class="mt-2 flex items-center gap-2 text-xs text-gray-600">
                                <input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo'))>
                                {{ __('settings.remove_logo') }}
                            </label>
                        @endif
                    </div>

                    <div>
                        <label class="{{ $label }}" for="favicon">{{ __('settings.favicon') }}</label>
                        @if ($faviconUrl)
                            <div class="mb-2 flex items-center gap-3 rounded-lg border border-gray-100 bg-slate-50 p-2">
                                <img src="{{ $faviconUrl }}" alt="{{ __('settings.favicon') }}" class="h-10 w-10 rounded bg-white object-contain p-1">
                                <span class="text-xs text-gray-500">{{ __('settings.current_file') }}</span>
                            </div>
                        @endif
                        <input id="favicon" type="file" name="favicon" accept=".png,.jpg,.jpeg,.webp,.ico,image/png,image/jpeg,image/webp,image/x-icon"
                               class="{{ $input }} @error('favicon') border-rose-400 @enderror">
                        <p class="mt-1 text-xs text-gray-400">{{ __('settings.favicon_help') }}</p>
                        <x-input-error class="mt-1" :messages="$errors->get('favicon')" />
                        @if ($faviconUrl)
                            <label class="mt-2 flex items-center gap-2 text-xs text-gray-600">
                                <input type="checkbox" name="remove_favicon" value="1" @checked(old('remove_favicon'))>
                                {{ __('settings.remove_favicon') }}
                            </label>
                        @endif
                    </div>
                </div>
            </section>

            <aside class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-bold text-gray-900">{{ __('settings.preview') }}</h2>
                <div class="rounded-xl border border-gray-100 bg-slate-50 p-4">
                    <div class="flex items-center gap-3">
                        @if ($logoUrl)
                            <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white ring-1 ring-gray-200 p-1.5">
                                <img src="{{ $logoUrl }}" alt="logo" class="h-full w-full object-contain">
                            </span>
                        @else
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-full text-sm font-bold text-white"
                                  style="background: {{ $previewPrimary }}">
                                {{ strtoupper(substr((string) $previewAppName, 0, 1)) }}
                            </span>
                        @endif
                        <div class="min-w-0">
                            <div class="truncate text-sm font-bold" style="color: {{ $previewPrimary }}">{{ $previewAppName }}</div>
                            <div class="truncate text-xs text-gray-400">{{ $previewTagline }}</div>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <span class="h-8 flex-1 rounded-lg" style="background: {{ $previewPrimary }}"></span>
                        <span class="h-8 flex-1 rounded-lg" style="background: {{ $previewAccent }}"></span>
                    </div>
                    @if ($faviconUrl)
                        <div class="mt-3 flex items-center gap-2 text-xs text-gray-500">
                            <img src="{{ $faviconUrl }}" alt="" class="h-4 w-4 object-contain">
                            {{ __('settings.favicon') }}
                        </div>
                    @endif
                </div>
            </aside>
        </div>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-1 text-sm font-bold text-gray-900">{{ __('settings.identity') }}</h2>
            <p class="mb-4 text-xs text-gray-400">{{ __('settings.identity_help') }}</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="{{ $label }}" for="country">{{ __('settings.country') }}</label>
                    <input id="country" class="{{ $input }}" name="country" value="{{ old('country', $settings['country']) }}">
                </div>
                <div>
                    <label class="{{ $label }}" for="motto">{{ __('settings.motto') }}</label>
                    <input id="motto" class="{{ $input }}" name="motto" value="{{ old('motto', $settings['motto']) }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $label }}" for="ministry">{{ __('settings.ministry') }}</label>
                    <input id="ministry" class="{{ $input }}" name="ministry" value="{{ old('ministry', $settings['ministry']) }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $label }}" for="structure">{{ __('settings.structure') }}</label>
                    <textarea id="structure" class="{{ $input }}" name="structure" rows="3">{{ old('structure', $settings['structure']) }}</textarea>
                </div>
                <div>
                    <label class="{{ $label }}" for="support_email">{{ __('settings.support_email') }}</label>
                    <input id="support_email" type="email" class="{{ $input }} @error('support_email') border-rose-400 @enderror"
                           name="support_email" value="{{ old('support_email', $settings['support_email']) }}">
                    <x-input-error class="mt-1" :messages="$errors->get('support_email')" />
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-1 text-sm font-bold text-gray-900">{{ __('settings.theme') }}</h2>
            <p class="mb-4 text-xs text-gray-400">{{ __('settings.theme_help') }}</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="{{ $label }}" for="primary_color">{{ __('settings.primary_color') }}</label>
                    <div class="flex items-center gap-3">
                        <input type="color" value="{{ $previewPrimary }}"
                               class="h-11 w-14 cursor-pointer rounded border border-gray-300 bg-white p-1"
                               oninput="document.getElementById('primary_color').value=this.value"
                               aria-label="{{ __('settings.primary_color') }}">
                        <input id="primary_color" class="{{ $input }} @error('primary_color') border-rose-400 @enderror" name="primary_color"
                               value="{{ $previewPrimary }}"
                               pattern="^#[0-9A-Fa-f]{6}$" required
                               oninput="if(/^#[0-9A-Fa-f]{6}$/.test(this.value)) this.previousElementSibling.value=this.value">
                    </div>
                    <x-input-error class="mt-1" :messages="$errors->get('primary_color')" />
                </div>
                <div>
                    <label class="{{ $label }}" for="accent_color">{{ __('settings.accent_color') }}</label>
                    <div class="flex items-center gap-3">
                        <input type="color" value="{{ $previewAccent }}"
                               class="h-11 w-14 cursor-pointer rounded border border-gray-300 bg-white p-1"
                               oninput="document.getElementById('accent_color').value=this.value"
                               aria-label="{{ __('settings.accent_color') }}">
                        <input id="accent_color" class="{{ $input }} @error('accent_color') border-rose-400 @enderror" name="accent_color"
                               value="{{ $previewAccent }}"
                               pattern="^#[0-9A-Fa-f]{6}$" required
                               oninput="if(/^#[0-9A-Fa-f]{6}$/.test(this.value)) this.previousElementSibling.value=this.value">
                    </div>
                    <x-input-error class="mt-1" :messages="$errors->get('accent_color')" />
                </div>
            </div>
        </section>

        <div class="flex flex-wrap gap-2">
            <x-buttons.primary type="submit">{{ __('settings.save') }}</x-buttons.primary>
            <x-buttons.secondary :href="route('admin.index')">{{ __('users.cancel') }}</x-buttons.secondary>
        </div>
    </form>
</x-app-layout>
