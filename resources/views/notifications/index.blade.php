@php
    $typeMeta = [
        'assigned' => ['label' => __('notifications.type_assigned'), 'bg' => 'bg-brand-soft', 'text' => 'text-brand'],
        'validation' => ['label' => __('notifications.type_validation'), 'bg' => 'bg-amber-50', 'text' => 'text-amber-700'],
        'validated' => ['label' => __('notifications.type_validated'), 'bg' => 'bg-emerald-50', 'text' => 'text-emerald-700'],
        'rejected' => ['label' => __('notifications.type_rejected'), 'bg' => 'bg-rose-50', 'text' => 'text-rose-700'],
        'sla_late' => ['label' => __('notifications.type_late'), 'bg' => 'bg-rose-50', 'text' => 'text-rose-700'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-bold text-slate-900">{{ __('notifications.title') }}</h1>
                <p class="mb-0 text-sm text-slate-500">{{ __('notifications.subtitle') }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($unreadTotal > 0)
                    <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700">
                        {{ trans_choice('notifications.unread_count', $unreadTotal, ['count' => $unreadTotal]) }}
                    </span>
                    <form method="POST" action="{{ route('notifications.read_all') }}">
                        @csrf
                        @method('PATCH')
                        <x-buttons.primary type="submit">
                            {{ __('notifications.mark_all') }}
                        </x-buttons.primary>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="mb-4 ui-alert ui-alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">
        {{-- Non lues --}}
        <section class="overflow-hidden ui-card shadow-sm xl:col-span-3">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <h2 class="mb-0 text-sm font-bold text-slate-900">{{ __('notifications.unread') }}</h2>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($unread as $n)
                    @php
                        $type = $n->data['type'] ?? null;
                        $meta = $typeMeta[$type] ?? ['label' => __('notifications.type_info'), 'bg' => 'bg-slate-100', 'text' => 'text-slate-600'];
                        $hasUrl = ! empty($n->data['url']);
                    @endphp
                    <article class="group flex gap-3 px-5 py-4 transition hover:bg-slate-50">
                        <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $meta['bg'] }} {{ $meta['text'] }}">
                            @if ($type === 'sla_late')
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            @elseif ($type === 'validation')
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            @elseif ($type === 'rejected')
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            @elseif ($type === 'validated')
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                            @endif
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="mb-0 text-sm font-semibold text-slate-900">{{ $n->data['title'] ?? '—' }}</h3>
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $meta['bg'] }} {{ $meta['text'] }}">
                                    {{ $meta['label'] }}
                                </span>
                            </div>
                            <p class="mb-0 mt-1 text-sm text-slate-600">{{ $n->data['message'] ?? '' }}</p>
                            <p class="mb-0 mt-1.5 text-xs text-slate-400">{{ $n->created_at?->diffForHumans() }}</p>

                            <div class="mt-3 flex flex-wrap gap-2">
                                @if ($hasUrl)
                                    <x-buttons.primary :href="route('notifications.open', $n->id)" class="!px-2.5 !py-1.5 text-xs">
                                        {{ __('notifications.open') }}
                                    </x-buttons.primary>
                                @endif
                                <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-buttons.secondary type="submit" class="!px-2.5 !py-1.5 text-xs">
                                        {{ __('notifications.mark_read') }}
                                    </x-buttons.secondary>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-10">
                        <x-empty-state
                            :title="__('notifications.none_unread')"
                            :description="__('notifications.none_unread_hint')"
                        />
                    </div>
                @endforelse
            </div>

            @if ($unread->hasPages())
                <div class="border-t border-slate-100 px-5 py-3">{{ $unread->links() }}</div>
            @endif
        </section>

        {{-- Lues --}}
        <section class="overflow-hidden ui-card shadow-sm xl:col-span-2">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="mb-0 text-sm font-bold text-slate-900">{{ __('notifications.read') }}</h2>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($read as $n)
                    @php $hasUrl = ! empty($n->data['url']); @endphp
                    <article class="px-5 py-3.5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="mb-0 text-sm font-semibold text-slate-800">{{ $n->data['title'] ?? '—' }}</p>
                                <p class="mb-0 mt-1 text-xs text-slate-500 line-clamp-2">{{ $n->data['message'] ?? '' }}</p>
                                <p class="mb-0 mt-1.5 text-[11px] text-slate-400">{{ $n->created_at?->diffForHumans() }}</p>
                            </div>
                            @if ($hasUrl)
                                <a href="{{ route('notifications.open', $n->id) }}"
                                   class="shrink-0 text-xs font-semibold text-accent no-underline hover:underline">
                                    {{ __('notifications.open') }}
                                </a>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-10">
                        <x-empty-state :title="__('notifications.none_read')" class="py-4" />
                    </div>
                @endforelse
            </div>

            @if ($read->hasPages())
                <div class="border-t border-slate-100 px-5 py-3">{{ $read->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
