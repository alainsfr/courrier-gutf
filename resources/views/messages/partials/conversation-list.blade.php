@php
    $activeId = isset($conversation) ? (int) $conversation->id : null;
@endphp

{{-- Liste conversations (partagée index / show) --}}
<div class="flex h-full min-h-0 flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <div class="shrink-0 border-b border-gray-200 px-4 py-3.5">
        <h2 class="m-0 text-sm font-bold text-gray-900">{{ __('nav.messages') }}</h2>
        <p class="mb-0 mt-0.5 text-xs text-gray-500">Discussions internes GUTF</p>
    </div>

    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-3">
        {{-- Nouvelle discussion --}}
        <form method="POST" action="{{ route('messages.direct') }}" class="rounded-lg border border-gray-100 bg-gray-50 p-3">
            @csrf
            <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-gray-400">Nouveau message</label>
            <select name="user_id" required
                    class="mb-2 w-full border border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm px-3 py-2 text-sm focus:outline-none focus:ring-1">
                <option value="" disabled selected>Choisir un collaborateur</option>
                @foreach($users as $u)
                    @if($u->id !== auth()->id())
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endif
                @endforeach
            </select>
            <x-buttons.primary type="submit" class="w-full">
                Démarrer
            </x-buttons.primary>
        </form>

        @can('chat.group.create')
            <form method="POST" action="{{ route('messages.group.create') }}" class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                @csrf
                <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-gray-400">Nouveau groupe</label>
                <input class="mb-2 w-full border border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm px-3 py-2 text-sm focus:outline-none focus:ring-1"
                       name="title" placeholder="Nom du groupe" required>
                <select name="members[]" multiple required
                        class="mb-2 h-24 w-full border border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm px-3 py-2 text-sm focus:outline-none focus:ring-1">
                    @foreach($users as $u)
                        @if($u->id !== auth()->id())
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endif
                    @endforeach
                </select>
                <x-buttons.secondary type="submit" class="w-full">
                    Créer le groupe
                </x-buttons.secondary>
            </form>
        @endcan

        <div class="space-y-1">
            @forelse($conversations as $c)
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
                        : ($c->title ?? 'Groupe');
                    $initial = strtoupper(substr($title, 0, 1));
                    $isActive = $activeId === (int) $c->id;
                @endphp
                <a href="{{ route('messages.show', $c) }}"
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 no-underline transition
                          {{ $isActive ? 'bg-[#17384b]/10 ring-1 ring-[#17384b]/20' : 'hover:bg-gray-50' }}">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white
                                 {{ $c->type === 'group' ? 'bg-[#dc633a]' : 'bg-[#17384b]' }}">
                        {{ $initial }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-semibold {{ $isActive ? 'text-[#17384b]' : 'text-gray-900' }}">
                            {{ $title }}
                        </div>
                        <div class="truncate text-xs text-gray-500">
                            {{ $last?->body ? \Illuminate\Support\Str::limit($last->body, 42) : 'Aucun message' }}
                        </div>
                    </div>
                    @if($last?->created_at)
                        <span class="shrink-0 text-[10px] text-gray-400">{{ $last->created_at->format('H:i') }}</span>
                    @endif
                </a>
            @empty
                <div class="px-3 py-8 text-center text-xs text-gray-400">
                    Aucune conversation.
                </div>
            @endforelse
        </div>
    </div>
</div>
