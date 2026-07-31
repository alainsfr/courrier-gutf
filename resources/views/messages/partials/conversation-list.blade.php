@php
    $activeId = isset($conversation) ? (int) $conversation->id : null;
    $field = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 placeholder:text-gray-400 transition focus:border-[#17384b] focus:outline-none focus:ring-2 focus:ring-[#17384b]/20';
    $listSearch = $search ?? '';
    $unreadTotal = $unreadTotal ?? 0;
@endphp

{{-- Liste conversations (partagée index / show) --}}
<div class="flex h-full min-h-0 flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <div class="shrink-0 border-b border-gray-200 px-4 py-3.5">
        <div class="flex items-center justify-between gap-2">
            <div>
                <h2 class="m-0 text-sm font-bold text-gray-900">{{ __('nav.messages') }}</h2>
                <p class="mb-0 mt-0.5 text-xs text-gray-500">{{ __('chat.subtitle') }}</p>
            </div>
            @if ($unreadTotal > 0)
                <span class="inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-[#dc633a] px-2 py-0.5 text-[11px] font-bold text-white">
                    {{ $unreadTotal > 99 ? '99+' : $unreadTotal }}
                </span>
            @endif
        </div>

        <form method="GET" action="{{ isset($conversation) ? route('messages.show', $conversation) : route('messages.index') }}" class="mt-3">
            <label class="sr-only" for="chat-search">{{ __('chat.search_conversations') }}</label>
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input id="chat-search"
                       type="search"
                       name="q"
                       value="{{ $listSearch }}"
                       placeholder="{{ __('chat.search_conversations') }}"
                       class="w-full rounded-lg border border-gray-300 bg-gray-50 py-2 pl-9 pr-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-[#17384b] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#17384b]/20"
                       autocomplete="off">
            </div>
        </form>
    </div>

    <div class="min-h-0 flex-1 space-y-3 overflow-y-auto p-3">
        <x-form-errors class="rounded-lg px-3 py-2.5 text-xs" />

        @if (session('status') && ! isset($conversation))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-xs text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        {{-- Actions nouvelles discussions --}}
        <details class="rounded-lg border border-gray-100 bg-gray-50 open:bg-white open:shadow-sm" @if ($errors->has('user_id') || old('user_id')) open @endif>
            <summary class="cursor-pointer list-none px-3 py-2.5 text-xs font-bold uppercase tracking-wider text-gray-500 marker:content-none [&::-webkit-details-marker]:hidden">
                <span class="flex items-center justify-between gap-2">
                    <span>{{ __('chat.new_direct') }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-3.5 w-3.5 text-gray-400"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                </span>
            </summary>
            <form method="POST" action="{{ route('messages.direct') }}" class="border-t border-gray-100 p-3">
                @csrf
                <select name="user_id" required class="mb-2 {{ $field }}">
                    <option value="" disabled @selected(! old('user_id'))>{{ __('chat.choose_colleague') }}</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" @selected((string) old('user_id') === (string) $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
                <x-buttons.primary type="submit" class="w-full">
                    {{ __('chat.start') }}
                </x-buttons.primary>
            </form>
        </details>

        @can('chat.group.create')
            <details class="rounded-lg border border-gray-100 bg-gray-50 open:bg-white open:shadow-sm" @if ($errors->has('title') || $errors->has('members') || old('title') || old('members')) open @endif>
                <summary class="cursor-pointer list-none px-3 py-2.5 text-xs font-bold uppercase tracking-wider text-gray-500 marker:content-none [&::-webkit-details-marker]:hidden">
                    <span class="flex items-center justify-between gap-2">
                        <span>{{ __('chat.new_group') }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-3.5 w-3.5 text-gray-400"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                    </span>
                </summary>
                <form method="POST" action="{{ route('messages.group.create') }}" class="border-t border-gray-100 p-3" data-group-form>
                    @csrf
                    <input class="mb-2 {{ $field }}"
                           name="title"
                           value="{{ old('title') }}"
                           placeholder="{{ __('chat.group_title_placeholder') }}"
                           required
                           maxlength="150">

                    <div class="mb-1.5 flex items-center justify-between gap-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400">{{ __('chat.group_members') }}</span>
                        <span class="text-[11px] font-semibold text-[#17384b]" data-member-count>0</span>
                    </div>

                    <input type="search"
                           class="mb-2 {{ $field }}"
                           placeholder="{{ __('chat.group_search_members') }}"
                           data-member-search
                           autocomplete="off">

                    <div class="mb-2 max-h-40 space-y-1 overflow-y-auto rounded-lg border border-gray-200 bg-white p-2">
                        @forelse ($users as $u)
                            <label class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-slate-50"
                                   data-member-row
                                   data-member-label="{{ strtolower($u->name.' '.$u->email) }}">
                                <input type="checkbox"
                                       name="members[]"
                                       value="{{ $u->id }}"
                                       class="h-4 w-4 rounded border-gray-300 text-[#17384b] focus:ring-[#17384b]/30"
                                       data-member-check
                                       @checked(collect(old('members', []))->map(fn ($id) => (string) $id)->contains((string) $u->id))>
                                <span class="min-w-0 flex-1 truncate">
                                    <span class="font-medium text-gray-800">{{ $u->name }}</span>
                                    <span class="block truncate text-[11px] text-gray-400">{{ $u->email }}</span>
                                </span>
                            </label>
                        @empty
                            <div class="px-2 py-3 text-center text-xs text-gray-400">{{ __('chat.no_colleagues') }}</div>
                        @endforelse
                    </div>
                    <p class="mb-2 text-[11px] text-gray-400">{{ __('chat.group_members_hint') }}</p>

                    <x-buttons.secondary type="submit" class="w-full">
                        {{ __('chat.create_group') }}
                    </x-buttons.secondary>
                </form>
            </details>
        @endcan

        <div class="space-y-1" data-conversation-list>
            @forelse ($conversations as $c)
                @php
                    $chatPartner = null;
                    if ($c->type === 'direct') {
                        $chatPartner = $c->participants
                            ->first(fn ($p) => (int) $p->user_id !== (int) auth()->id())
                            ?->user;
                    }
                    $last = $c->messages->first();
                    $title = ($c->type === 'direct' && $chatPartner)
                        ? $chatPartner->name
                        : ($c->title ?? __('chat.group'));
                    $initial = strtoupper(mb_substr($title, 0, 1));
                    $isActive = $activeId === (int) $c->id;
                    $memberCount = $c->type === 'group' ? $c->participants->count() : null;
                    $unread = (int) ($c->unread_count ?? 0);
                    $searchHaystack = strtolower($title.' '.($last?->body ?? '').' '.($chatPartner?->email ?? ''));
                    $timeLabel = null;
                    if ($last?->created_at) {
                        $timeLabel = $last->created_at->isToday()
                            ? $last->created_at->format('H:i')
                            : ($last->created_at->isYesterday()
                                ? __('chat.yesterday')
                                : $last->created_at->format('d/m'));
                    }
                @endphp
                <a href="{{ route('messages.show', $c) }}"
                   data-conv-item
                   data-search="{{ $searchHaystack }}"
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 no-underline transition
                          {{ $isActive ? 'bg-[#17384b]/10 ring-1 ring-[#17384b]/20' : 'hover:bg-gray-50' }}
                          {{ $unread > 0 && ! $isActive ? 'bg-orange-50/60' : '' }}">
                    <span class="relative inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white
                                 {{ $c->type === 'group' ? 'bg-[#dc633a]' : 'bg-[#17384b]' }}">
                        {{ $initial }}
                        @if ($unread > 0 && ! $isActive)
                            <span class="absolute -right-0.5 -top-0.5 h-2.5 w-2.5 rounded-full border-2 border-white bg-[#dc633a]"></span>
                        @endif
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <div class="truncate text-sm {{ $unread > 0 && ! $isActive ? 'font-bold text-gray-900' : ($isActive ? 'font-semibold text-[#17384b]' : 'font-semibold text-gray-900') }}">
                                {{ $title }}
                            </div>
                            @if ($timeLabel)
                                <span class="shrink-0 text-[10px] {{ $unread > 0 && ! $isActive ? 'font-semibold text-[#dc633a]' : 'text-gray-400' }}">{{ $timeLabel }}</span>
                            @endif
                        </div>
                        <div class="mt-0.5 flex items-center justify-between gap-2">
                            <div class="truncate text-xs {{ $unread > 0 && ! $isActive ? 'font-medium text-gray-700' : 'text-gray-500' }}">
                                @if ($c->type === 'group' && $memberCount)
                                    <span class="mr-1">{{ trans_choice('chat.members_count', $memberCount, ['count' => $memberCount]) }} ·</span>
                                @endif
                                {{ $last?->body ? \Illuminate\Support\Str::limit($last->body, 40) : __('chat.no_messages_yet') }}
                            </div>
                            @if ($unread > 0 && ! $isActive)
                                <span class="inline-flex min-w-[1.25rem] shrink-0 items-center justify-center rounded-full bg-[#dc633a] px-1.5 py-0.5 text-[10px] font-bold text-white">
                                    {{ $unread > 99 ? '99+' : $unread }}
                                </span>
                            @endif
                        </div>
                    </div>
                </a>
            @empty
                <div class="px-3 py-10 text-center">
                    <div class="mx-auto mb-3 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-6 w-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.63m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h.008m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h.008M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 0 1-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8Z" />
                        </svg>
                    </div>
                    <p class="mb-0 text-xs text-gray-400">
                        {{ $listSearch !== '' ? __('chat.search_empty') : __('chat.empty') }}
                    </p>
                </div>
            @endforelse
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-group-form]').forEach((form) => {
                    const search = form.querySelector('[data-member-search]');
                    const rows = form.querySelectorAll('[data-member-row]');
                    const checks = form.querySelectorAll('[data-member-check]');
                    const counter = form.querySelector('[data-member-count]');

                    const syncCount = () => {
                        const n = [...checks].filter((c) => c.checked).length;
                        if (counter) {
                            counter.textContent = n + ' ' + @json(__('chat.selected'));
                        }
                    };

                    search?.addEventListener('input', () => {
                        const q = (search.value || '').trim().toLowerCase();
                        rows.forEach((row) => {
                            const label = row.getAttribute('data-member-label') || '';
                            row.classList.toggle('hidden', q !== '' && !label.includes(q));
                        });
                    });

                    checks.forEach((c) => c.addEventListener('change', syncCount));
                    syncCount();

                    form.addEventListener('submit', (e) => {
                        const n = [...checks].filter((c) => c.checked).length;
                        if (n < 1) {
                            e.preventDefault();
                            alert(@json(__('chat.group_members_required')));
                        }
                    });
                });

                // Filtre instantané côté client (en plus du GET serveur)
                const searchInput = document.getElementById('chat-search');
                const items = document.querySelectorAll('[data-conv-item]');
                let debounce;
                searchInput?.addEventListener('input', () => {
                    clearTimeout(debounce);
                    debounce = setTimeout(() => {
                        const q = (searchInput.value || '').trim().toLowerCase();
                        items.forEach((el) => {
                            const hay = el.getAttribute('data-search') || '';
                            el.classList.toggle('hidden', q !== '' && !hay.includes(q));
                        });
                    }, 120);
                });
            });
        </script>
    @endpush
@endonce
