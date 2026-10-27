<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="mb-1 text-lg font-bold text-gray-900">{{ __('courrier.kanban_title') }}</h1>
                <p class="mb-0 text-sm text-gray-500">
                    @if($bureau)
                        {{ __('courrier.kanban_bureau', ['bureau' => $bureau->nom]) }}
                    @else
                        {{ __('courrier.kanban_subtitle') }}
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if($isAdmin && $bureaux->isNotEmpty())
                    <form method="GET" action="{{ route('courriers.kanban') }}" class="m-0">
                        <select name="bureau_id"
                                class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:border-[#17384b] focus:outline-none focus:ring-4 focus:ring-[#17384b]/10"
                                onchange="this.form.submit()">
                            <option value="">{{ __('courrier.kanban_all_bureaux') }}</option>
                            @foreach($bureaux as $b)
                                <option value="{{ $b->id }}" @selected(optional($bureau)->id === $b->id)>{{ $b->nom }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
                <a href="{{ route('courriers.index') }}"
                   class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-600 no-underline hover:bg-gray-50">
                    {{ __('courrier.kanban_list_view') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div id="kanban-toast" class="fixed top-4 right-4 z-50 hidden max-w-sm rounded-xl px-4 py-3 text-sm font-medium shadow-lg text-white"></div>

    <div class="kanban-shell w-full">
        <div id="kanban-board"
             class="flex w-full gap-4 overflow-x-auto pb-2 pt-1 snap-x snap-mandatory"
             style="min-height: calc(100vh - 11rem);"
             data-transfer-template="{{ $transferUrlTemplate }}"
             data-csrf="{{ csrf_token() }}">

            @forelse($etapes as $etape)
                @php
                    $cards = $courriersByEtape->get($etape->id, collect());
                    $color = $etape->ui_color ?: '#64748B';
                @endphp

                <section class="kanban-column snap-start flex w-[300px] min-w-[300px] flex-1 flex-col rounded-xl border border-gray-200 bg-gray-100/50"
                         data-etape-id="{{ $etape->id }}"
                         data-etape-ordre="{{ $etape->ordre }}"
                         data-rejectable="{{ $etape->is_rejectable ? '1' : '0' }}">

                    <header class="sticky top-0 z-10 rounded-t-xl border-b border-gray-200/80 bg-white/80 px-3 py-3 backdrop-blur"
                            style="border-top: 3px solid {{ $color }};">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-white"
                                      style="background: {{ $color }};">
                                    <i data-lucide="{{ $etape->ui_icon ?: 'circle' }}" class="h-4 w-4"></i>
                                </span>
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-bold text-gray-800">{{ $etape->libelle }}</div>
                                    <div class="truncate text-[11px] text-gray-500">
                                        {{ (int) $etape->ordre < 5 ? $etape->delai_jours.'j' : __('courrier.direct_same_office') }}
                                    </div>
                                </div>
                            </div>
                            <span class="column-count inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full border border-gray-200 bg-white px-2 text-xs font-bold text-gray-600">
                                {{ $cards->count() }}
                            </span>
                        </div>
                    </header>

                    <div class="kanban-list flex-1 space-y-2.5 overflow-y-auto p-2.5"
                         data-etape-id="{{ $etape->id }}"
                         data-etape-ordre="{{ $etape->ordre }}"
                         style="max-height: calc(100vh - 14rem);">

                        @forelse($cards as $c)
                            <article class="kanban-card group cursor-grab rounded-lg border bg-white p-3 shadow-sm transition hover:shadow-md active:cursor-grabbing {{ $c->is_late ? 'border-rose-300 ring-1 ring-rose-100' : 'border-gray-200' }}"
                                     data-courrier-id="{{ $c->id }}"
                                     data-can-move="{{ auth()->user()->can('transfer', $c) ? '1' : '0' }}"
                                     data-etape-id="{{ $etape->id }}"
                                     data-etape-ordre="{{ $etape->ordre }}"
                                     data-rejectable="{{ $etape->is_rejectable ? '1' : '0' }}">
                                <div class="mb-2 flex items-start justify-between gap-2">
                                    <a href="{{ route('courriers.show', $c) }}"
                                       class="text-[11px] font-bold tracking-wide text-gray-500 no-underline hover:text-gray-800">
                                        {{ $c->reference_interne }}
                                    </a>
                                    <div class="flex shrink-0 flex-col items-end gap-1">
                                        @if($c->is_late)
                                            <span class="rounded-md bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">
                                                {{ __('courrier.kanban_late') }}
                                            </span>
                                        @endif
                                        <span class="rounded-md px-1.5 py-0.5 text-[10px] font-semibold uppercase"
                                              style="color: {{ $color }}; background: {{ $color }}18;">
                                            {{ $c->type }}
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-2 line-clamp-2 text-sm font-semibold leading-snug text-gray-800">
                                    {{ $c->objet }}
                                </div>
                                <div class="flex items-center justify-between gap-2 text-[11px] text-gray-500">
                                    <span class="truncate">
                                        {{ $c->assignedTo?->name ?? '—' }}
                                    </span>
                                    <span class="shrink-0 rounded-full border border-gray-100 bg-gray-50 px-2 py-0.5">
                                        {{ $c->workflowEtape?->libelle ?? '—' }}
                                    </span>
                                </div>
                                @if($c->date_limite)
                                    <div class="mt-2 text-[10px] {{ $c->is_late ? 'font-semibold text-rose-600' : 'text-gray-400' }}">
                                        SLA : {{ $c->date_limite->format('d/m/Y') }}
                                    </div>
                                @endif
                            </article>
                        @empty
                            <x-empty-state
                                class="kanban-empty pointer-events-none py-8"
                                :title="__('courrier.kanban_empty')"
                            >
                                <x-slot:icon>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125Z" />
                                    </svg>
                                </x-slot:icon>
                            </x-empty-state>
                        @endforelse
                    </div>
                </section>
            @empty
                <div class="flex w-full flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
                    <p class="mb-1 text-sm font-semibold text-gray-800">{{ __('courrier.kanban_no_columns') }}</p>
                    <p class="mb-0 max-w-md text-xs text-gray-500">{{ __('courrier.kanban_no_columns_hint') }}</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Modal note pour rejet --}}
    <div id="reject-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-900/40 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl">
            <h3 class="mb-1 text-lg font-bold text-slate-800">{{ __('courrier.kanban_reject_title') }}</h3>
            <p class="mb-3 text-sm text-slate-500">{{ __('courrier.kanban_reject_help') }}</p>
            <textarea id="reject-note" rows="3"
                      class="mb-4 w-full border border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm px-3 py-2 text-sm focus:outline-none focus:ring-1"
                      placeholder="{{ __('courrier.reject_reason') }}"></textarea>
            <div class="flex justify-end gap-2">
                <x-buttons.secondary type="button" id="reject-cancel">
                    {{ __('courrier.cancel') }}
                </x-buttons.secondary>
                <x-buttons.danger type="button" id="reject-confirm">
                    {{ __('courrier.kanban_reject_confirm') }}
                </x-buttons.danger>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.468.0/dist/umd/lucide.min.js"></script>
    <script>
        (function () {
            if (window.lucide) window.lucide.createIcons();

            const board = document.getElementById('kanban-board');
            if (!board || typeof Sortable === 'undefined') return;

            const transferTemplate = board.dataset.transferTemplate;
            const csrf = board.dataset.csrf;
            const nextOrders = @json($nextOrders);
            const previousOrders = @json($previousOrders);
            const toastEl = document.getElementById('kanban-toast');
            const rejectModal = document.getElementById('reject-modal');
            const rejectNote = document.getElementById('reject-note');
            const rejectCancel = document.getElementById('reject-cancel');
            const rejectConfirm = document.getElementById('reject-confirm');

            let pendingReject = null;

            function toast(message, ok = true) {
                if (!toastEl) return;
                toastEl.textContent = message;
                toastEl.classList.remove('hidden', 'bg-emerald-600', 'bg-rose-600');
                toastEl.classList.add(ok ? 'bg-emerald-600' : 'bg-rose-600');
                setTimeout(() => toastEl.classList.add('hidden'), 3200);
            }

            function refreshCounts() {
                document.querySelectorAll('.kanban-column').forEach((col) => {
                    const n = col.querySelectorAll('.kanban-card').length;
                    const badge = col.querySelector('.column-count');
                    if (badge) badge.textContent = n;
                });
            }

            function resolveAction(fromOrdre, toOrdre, fromRejectable) {
                if (Number(nextOrders[fromOrdre]) === toOrdre) return 'forward';
                if (Number(previousOrders[fromOrdre]) === toOrdre) return 'backward';
                return null;
            }

            async function postTransfer(courrierId, nouvelleEtapeId, actionType, note) {
                const url = transferTemplate.replace('__ID__', courrierId);
                const body = {
                    nouvelle_etape_id: Number(nouvelleEtapeId),
                    action_type: actionType,
                    note_metier: note || null,
                };

                if (window.axios) {
                    const { data } = await window.axios.post(url, body, {
                        headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                    });
                    return data;
                }

                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json();
                if (!res.ok) {
                    const err = new Error(data.message || 'Erreur');
                    err.response = { data, status: res.status };
                    throw err;
                }
                return data;
            }

            function openRejectModal(ctx) {
                pendingReject = ctx;
                rejectNote.value = '';
                rejectModal.classList.remove('hidden');
                rejectModal.classList.add('flex');
                rejectNote.focus();
            }

            function closeRejectModal() {
                pendingReject = null;
                rejectModal.classList.add('hidden');
                rejectModal.classList.remove('flex');
            }

            rejectCancel?.addEventListener('click', () => {
                if (pendingReject) {
                    pendingReject.fromList.insertBefore(pendingReject.item, pendingReject.fromList.children[pendingReject.oldIndex] || null);
                    refreshCounts();
                }
                closeRejectModal();
            });

            rejectConfirm?.addEventListener('click', async () => {
                if (!pendingReject) return;
                const note = rejectNote.value.trim();
                if (!note) {
                    toast('{{ __('courrier.kanban_reject_required') }}', false);
                    return;
                }
                const ctx = pendingReject;
                closeRejectModal();
                try {
                    const data = await postTransfer(ctx.courrierId, ctx.toEtapeId, 'reject', note);
                    if (!data.ok) throw new Error(data.message || 'Échec');
                    ctx.item.dataset.etapeId = String(ctx.toEtapeId);
                    ctx.item.dataset.etapeOrdre = String(ctx.toOrdre);
                    toast(data.message || 'Transfert effectué');
                    refreshCounts();
                } catch (e) {
                    ctx.fromList.insertBefore(ctx.item, ctx.fromList.children[ctx.oldIndex] || null);
                    refreshCounts();
                    const msg = e.response?.data?.message || e.message || 'Transition refusée';
                    toast(msg, false);
                }
            });

            document.querySelectorAll('.kanban-list').forEach((listEl) => {
                Sortable.create(listEl, {
                    group: 'courriers-kanban',
                    animation: 180,
                    easing: 'cubic-bezier(0.2, 0, 0, 1)',
                    draggable: '.kanban-card',
                    filter: '.kanban-card[data-can-move="0"]',
                    preventOnFilter: false,
                    ghostClass: 'opacity-40',
                    chosenClass: 'ring-2 ring-slate-300',
                    dragClass: 'shadow-lg',
                    onAdd(evt) {
                        const empty = evt.to.querySelector('.kanban-empty');
                        if (empty) empty.remove();
                    },
                    async onEnd(evt) {
                        const item = evt.item;
                        const fromList = evt.from;
                        const toList = evt.to;
                        const courrierId = item.dataset.courrierId;
                        const fromEtapeId = fromList.dataset.etapeId;
                        const toEtapeId = toList.dataset.etapeId;
                        const fromOrdre = Number(item.dataset.etapeOrdre || fromList.dataset.etapeOrdre);
                        const toOrdre = Number(toList.dataset.etapeOrdre);
                        const fromRejectable = item.dataset.rejectable === '1';

                        if (fromEtapeId === toEtapeId) {
                            refreshCounts();
                            return;
                        }

                        const action = resolveAction(fromOrdre, toOrdre, fromRejectable);
                        if (!action) {
                            fromList.insertBefore(item, fromList.children[evt.oldIndex] || null);
                            refreshCounts();
                            toast('{{ __('courrier.kanban_invalid_move') }}', false);
                            return;
                        }

                        if (action === 'reject') {
                            openRejectModal({
                                item, fromList, oldIndex: evt.oldIndex,
                                courrierId, toEtapeId, toOrdre,
                            });
                            return;
                        }

                        item.classList.add('opacity-60', 'pointer-events-none');
                        try {
                            const data = await postTransfer(courrierId, toEtapeId, action, null);
                            if (!data.ok) throw new Error(data.message || 'Échec');
                            item.dataset.etapeId = String(toEtapeId);
                            item.dataset.etapeOrdre = String(toOrdre);
                            item.dataset.rejectable = toList.closest('.kanban-column')?.dataset.rejectable || '0';
                            toast(data.message || 'Transfert effectué');
                            refreshCounts();
                        } catch (e) {
                            fromList.insertBefore(item, fromList.children[evt.oldIndex] || null);
                            refreshCounts();
                            const msg = e.response?.data?.message || e.message || 'Transition refusée';
                            toast(msg, false);
                        } finally {
                            item.classList.remove('opacity-60', 'pointer-events-none');
                        }
                    },
                });
            });
        })();
    </script>
    @endpush
</x-app-layout>
