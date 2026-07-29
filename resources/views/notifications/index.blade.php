@php
    $field = 'w-full border border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-1';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-lg font-bold text-gray-900">{{ __('notifications.title') }}</h1>

            <form method="POST" action="{{ route('notifications.read_all') }}">
                @csrf
                @method('PATCH')
                <x-buttons.secondary type="submit">
                    {{ __('notifications.mark_all') }}
                </x-buttons.secondary>
            </form>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-sm font-bold text-gray-900">{{ __('notifications.unread') }}</h2>
            <div class="space-y-3">
                @forelse($unread as $n)
                    <div class="rounded-lg border border-gray-200 p-4">
                        <div class="font-semibold text-gray-900">{{ $n->data['title'] ?? '—' }}</div>
                        <div class="mt-1 text-sm text-gray-500">{{ $n->data['message'] ?? '' }}</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @if(!empty($n->data['url']))
                                <x-buttons.secondary :href="$n->data['url']">
                                    {{ __('notifications.open') }}
                                </x-buttons.secondary>
                            @endif
                            <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                                @csrf
                                @method('PATCH')
                                <x-buttons.primary type="submit">
                                    {{ __('notifications.mark_read') }}
                                </x-buttons.primary>
                            </form>
                        </div>
                    </div>
                @empty
                    <x-empty-state :title="__('notifications.none')" class="py-6" />
                @endforelse
            </div>
            <div class="mt-4">{{ $unread->links() }}</div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-sm font-bold text-gray-900">{{ __('notifications.read') }}</h2>
            <div class="space-y-3">
                @forelse($read as $n)
                    <div class="rounded-lg border border-gray-100 bg-gray-50/60 p-4">
                        <div class="font-semibold text-gray-800">{{ $n->data['title'] ?? '—' }}</div>
                        <div class="mt-1 text-sm text-gray-500">{{ $n->data['message'] ?? '' }}</div>
                        @if(!empty($n->data['url']))
                            <div class="mt-3">
                                <x-buttons.secondary :href="$n->data['url']">
                                    {{ __('notifications.open') }}
                                </x-buttons.secondary>
                            </div>
                        @endif
                    </div>
                @empty
                    <x-empty-state :title="__('notifications.none')" class="py-6" />
                @endforelse
            </div>
            <div class="mt-4">{{ $read->links() }}</div>
        </div>
    </div>
</x-app-layout>
