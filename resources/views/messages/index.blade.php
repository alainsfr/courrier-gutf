<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="mb-0 text-lg font-bold text-slate-900">{{ __('nav.messages') }}</h1>
            <p class="mb-0 text-sm text-slate-500">{{ __('chat.page_subtitle') }}</p>
        </div>
    </x-slot>

    <div class="flex ui-messaging flex-col gap-4 lg:flex-row lg:gap-6">
        <div class="flex min-h-0 flex-1 flex-col lg:w-1/3 lg:flex-none">
            @include('messages.partials.conversation-list')
        </div>

        <div class="hidden min-h-0 flex-1 flex-col lg:flex lg:w-2/3">
            <div class="flex h-full flex-col items-center justify-center ui-card p-8 text-center shadow-sm">
                <span class="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-soft text-brand">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-7 w-7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.63m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h.008m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h.008M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 0 1-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8Z" />
                    </svg>
                </span>
                <h2 class="mb-2 text-base font-bold text-slate-900">{{ __('chat.select_conversation') }}</h2>
                <p class="mb-0 max-w-sm text-sm text-slate-500">
                    {{ __('chat.select_conversation_hint') }}
                </p>
                @if (($unreadTotal ?? 0) > 0)
                    <p class="mt-4 mb-0 rounded-full bg-accent-soft px-3 py-1 text-xs font-semibold text-accent">
                        {{ trans_choice('chat.unread_total', $unreadTotal, ['count' => $unreadTotal]) }}
                    </p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
