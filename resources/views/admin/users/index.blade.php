@php
    $field = 'w-full border border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-1';
    $label = 'mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-bold text-gray-900">{{ __('users.title') }}</h1>
                <p class="mb-0 text-sm text-gray-500">{{ __('nav.administration') }}</p>
            </div>
            <x-buttons.primary :href="route('admin.users.create')">
                <span>+</span> {{ __('users.new') }}
            </x-buttons.primary>
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
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="min-w-0 flex-1">
                <label class="{{ $label }}">{{ __('users.search') }}</label>
                <input name="q" value="{{ $q }}" class="{{ $field }}" placeholder="{{ __('users.search_placeholder') }}">
            </div>
            <div class="flex gap-2">
                <x-buttons.primary type="submit">
                    {{ __('users.apply') }}
                </x-buttons.primary>
                <x-buttons.secondary :href="route('admin.users.index')">
                    {{ __('users.reset') }}
                </x-buttons.secondary>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <th class="px-4 py-3">{{ __('users.name') }}</th>
                        <th class="px-4 py-3">{{ __('users.email') }}</th>
                        <th class="px-4 py-3">{{ __('users.role') }}</th>
                        <th class="px-4 py-3">{{ __('users.bureau') }}</th>
                        <th class="px-4 py-3">{{ __('users.status') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('users.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $u)
                        <tr class="hover:bg-gray-50/80">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-900">{{ $u->name }}</div>
                                <div class="text-xs text-gray-400">#{{ $u->id }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $u->email }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                    {{ str_replace('_', ' ', $u->getRoleNames()->first() ?? '—') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $u->bureau?->nom ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if($u->is_active)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        {{ __('users.active') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                        {{ __('users.inactive') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.users.edit', $u) }}"
                                   class="text-sm font-semibold text-[#17384b] no-underline hover:underline">
                                    {{ __('users.edit') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-4">
                                <x-empty-state
                                    :title="__('users.empty')"
                                    description="Créez un premier compte pour affecter rôles et bureaux."
                                >
                                    <x-slot:icon>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                        </svg>
                                    </x-slot:icon>
                                    <x-buttons.primary :href="route('admin.users.create')">
                                        {{ __('users.new') }}
                                    </x-buttons.primary>
                                </x-empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</x-app-layout>
