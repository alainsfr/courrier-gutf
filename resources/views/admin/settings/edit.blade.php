@php
    $input = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 transition focus:border-[var(--gutf-primary)] focus:outline-none focus:ring-2 focus:ring-[color-mix(in_srgb,var(--gutf-primary)_25%,transparent)]';
    $label = 'mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-bold text-gray-900">{{ __('settings.title') }}</h1>
            <p class="mb-0 text-sm text-gray-500">{{ __('settings.subtitle') }}</p>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <ul class="mb-0 list-disc pl-4">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm xl:col-span-2">
                <h2 class="mb-4 text-sm font-bold text-gray-900">{{ __('settings.branding') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}">{{ __('settings.app_name') }}</label>
                        <input class="{{ $input }}" name="app_name" value="{{ old('app_name', $settings['app_name']) }}" required>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}">{{ __('settings.app_tagline') }}</label>
                        <input class="{{ $input }}" name="app_tagline" value="{{ old('app_tagline', $settings['app_tagline']) }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">{{ __('settings.logo') }}</label>
                        <input type="file" name="logo" accept="image/*,.svg" class="{{ $input }}">
                        <p class="mt-1 text-xs text-gray-400">{{ __('settings.logo_help') }}</p>
                        @if ($logoUrl)
                            <label class="mt-2 flex items-center gap-2 text-xs text-gray-600">
                                <input type="checkbox" name="remove_logo" value="1"> {{ __('settings.remove_logo') }}
                            </label>
                        @endif
                    </div>
                    <div>
                        <label class="{{ $label }}">{{ __('settings.favicon') }}</label>
                        <input type="file" name="favicon" accept="image/*,.ico" class="{{ $input }}">
                        <p class="mt-1 text-xs text-gray-400">{{ __('settings.favicon_help') }}</p>
                        @if ($faviconUrl)
                            <label class="mt-2 flex items-center gap-2 text-xs text-gray-600">
                                <input type="checkbox" name="remove_favicon" value="1"> {{ __('settings.remove_favicon') }}
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
                            <img src="{{ $logoUrl }}" alt="logo" class="h-12 w-12 rounded-xl object-contain bg-white p-1">
                        @else
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl text-sm font-bold text-white"
                                  style="background: {{ $settings['primary_color'] }}">
                                {{ strtoupper(substr($settings['app_name'], 0, 1)) }}
                            </span>
                        @endif
                        <div class="min-w-0">
                            <div class="truncate text-sm font-bold" style="color: {{ $settings['primary_color'] }}">{{ $settings['app_name'] }}</div>
                            <div class="truncate text-xs text-gray-400">{{ $settings['app_tagline'] }}</div>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <span class="h-8 flex-1 rounded-lg" style="background: {{ $settings['primary_color'] }}"></span>
                        <span class="h-8 flex-1 rounded-lg" style="background: {{ $settings['accent_color'] }}"></span>
                    </div>
                </div>
            </aside>
        </div>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-bold text-gray-900">{{ __('settings.identity') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="{{ $label }}">{{ __('settings.country') }}</label>
                    <input class="{{ $input }}" name="country" value="{{ old('country', $settings['country']) }}">
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('settings.motto') }}</label>
                    <input class="{{ $input }}" name="motto" value="{{ old('motto', $settings['motto']) }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">{{ __('settings.ministry') }}</label>
                    <input class="{{ $input }}" name="ministry" value="{{ old('ministry', $settings['ministry']) }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">{{ __('settings.structure') }}</label>
                    <textarea class="{{ $input }}" name="structure" rows="3">{{ old('structure', $settings['structure']) }}</textarea>
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('settings.support_email') }}</label>
                    <input type="email" class="{{ $input }}" name="support_email" value="{{ old('support_email', $settings['support_email']) }}">
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-bold text-gray-900">{{ __('settings.theme') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="{{ $label }}">{{ __('settings.primary_color') }}</label>
                    <div class="flex items-center gap-3">
                        <input type="color" value="{{ old('primary_color', $settings['primary_color']) }}"
                               class="h-11 w-14 cursor-pointer rounded border border-gray-300 bg-white p-1"
                               oninput="document.getElementById('primary_color').value=this.value">
                        <input id="primary_color" class="{{ $input }}" name="primary_color"
                               value="{{ old('primary_color', $settings['primary_color']) }}"
                               pattern="^#[0-9A-Fa-f]{6}$" required
                               oninput="if(/^#[0-9A-Fa-f]{6}$/.test(this.value)) this.previousElementSibling.value=this.value">
                    </div>
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('settings.accent_color') }}</label>
                    <div class="flex items-center gap-3">
                        <input type="color" value="{{ old('accent_color', $settings['accent_color']) }}"
                               class="h-11 w-14 cursor-pointer rounded border border-gray-300 bg-white p-1"
                               oninput="document.getElementById('accent_color').value=this.value">
                        <input id="accent_color" class="{{ $input }}" name="accent_color"
                               value="{{ old('accent_color', $settings['accent_color']) }}"
                               pattern="^#[0-9A-Fa-f]{6}$" required
                               oninput="if(/^#[0-9A-Fa-f]{6}$/.test(this.value)) this.previousElementSibling.value=this.value">
                    </div>
                </div>
            </div>
        </section>

        <div class="flex flex-wrap gap-2">
            <x-buttons.primary type="submit">{{ __('settings.save') }}</x-buttons.primary>
            <x-buttons.secondary :href="route('admin.index')">{{ __('users.cancel') }}</x-buttons.secondary>
        </div>
    </form>
</x-app-layout>
