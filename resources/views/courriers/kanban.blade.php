<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h1 class="h4 mb-1 text-dark fw-bold">{{ __('courrier.kanban_title') }}</h1>
                <p class="text-muted small mb-0">
                    @if($bureau)
                        {{ __('courrier.kanban_bureau', ['bureau' => $bureau->nom]) }}
                    @else
                        {{ __('courrier.kanban_subtitle') }}
                    @endif
                </p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                @if($isAdmin && $bureaux->isNotEmpty())
                    <form method="GET" action="{{ route('courriers.kanban') }}" class="d-flex align-items-center gap-2">
                        <select name="bureau_id" class="form-select form-select-sm border-slate-200 rounded-3" onchange="this.form.submit()">
                            <option value="">{{ __('courrier.kanban_all_bureaux') }}</option>
                            @foreach($bureaux as $b)
                                <option value="{{ $b->id }}" @selected(optional($bureau)->id === $b->id)>{{ $b->nom }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
                <a href="{{ route('courriers.index') }}" class="btn btn-light border border-slate-200 rounded-3 btn-sm fw-semibold">
                    {{ __('courrier.kanban_list_view') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div id="kanban-toast" class="fixed top-4 right-4 z-50 hidden max-w-sm rounded-xl px-4 py-3 text-sm font-medium shadow-lg text-white"></div>

    <div class="kanban-shell -mx-2 md:-mx-0">
        <div id="kanban-board"
             class="flex gap-4 overflow-x-auto pb-4 pt-1 snap-x snap-mandatory"
             style="min-height: 70vh;"
             data-transfer-template="{{ $transferUrlTemplate }}"
             data-csrf="{{ csrf_token() }}">

            @foreach($etapes as $etape)
                @php
                    $cards = $courriersByEtape->get($etape->id, collect());
                    $color = $etape->ui_color ?: '#64748B';
                @endphp

                <section class="kanban-column snap-start flex w-[300px] min-w-[300px] flex-col rounded-2xl bg-slate-100/80 border border-slate-200/80 shadow-sm"
                         data-etape-id="{{ $etape->id }}"
                         data-etape-ordre="{{ $etape->ordre }}"
                         data-rejectable="{{ $etape->is_rejectable ? '1' : '0' }}">

                    <header class="sticky top-0 z-10 rounded-t-2xl px-3 py-3 border-b border-black/5"
                            style="background: linear-gradient(135deg, {{ $color }}22, {{ $color }}0A); border-top: 3px solid {{ $color }};">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl text-white shadow-sm"
                                      style="background: {{ $color }};">
                                    <i data-lucide="{{ $etape->ui_icon ?: 'circle' }}" class="h-4 w-4"></i>
                                </span>
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-bold text-slate-800">{{ $etape->libelle }}</div>
                                    <div class="truncate text-[11px] text-slate-500">
                                        {{ $etape->bureau?->nom }} · {{ $etape->delai_jours }}j
                                    </div>
                                </div>
                            </div>
                            <span class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-white px-2 text-xs font-bold text-slate-600 border border-slate-200 column-count">
                                {{ $cards->count() }}
                            </span>
                        </div>
                    </header>

                    <div class="kanban-list flex-1 space-y-2.5 overflow-y-auto p-2.5"
                         data-etape-id="{{ $etape->id }}"
                         data-etape-ordre="{{ $etape->ordre }}"
                         style="max-height: calc(70vh - 64px);">

                        @forelse($cards as $c)
                            <article class="kanban-card group cursor-grab active:cursor-grabbing rounded-xl border bg-white p-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $c->is_late ? 'border-rose-300 ring-1 ring-rose-100' : 'border-slate-200' }}"
                                     data-courrier-id="{{ $c->id }}"
                                     data-etape-id="{{ $etape->id }}"
                                     data-etape-ordre="{{ $etape->ordre }}"
                                     data-rejectable="{{ $etape->is_rejectable ? '1' : '0' }}">
                                <div class="mb-2 flex items-start justify-between gap-2">
                                    <a href="{{ route('courriers.show', $c) }}"
                                       class="text-[11px] font-bold tracking-wide text-slate-500 hover:text-slate-800 no-underline">
                                        {{ $c->reference_interne }}
                                    </a>
                                    <div class="flex shrink-0 flex-col items-end gap-1">
                                        @if($c->is_late)
                                            <span class="rounded-md bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white shadow-sm">
                                                {{ __('courrier.kanban_late') }}
                                            </span>
                                        @endif
                                        <span class="rounded-md px-1.5 py-0.5 text-[10px] font-semibold uppercase"
                                              style="color: {{ $color }}; background: {{ $color }}18;">
                                            {{ $c->type }}
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-2 line-clamp-2 text-sm font-semibold text-slate-800 leading-snug">
                                    {{ $c->objet }}
                                </div>
                                <div class="flex items-center justify-between gap-2 text-[11px] text-slate-500">
                                    <span class="truncate">
                                        {{ $c->assignedTo?->name ?? '—' }}
                                    </span>
                                    <span class="shrink-0 rounded-full bg-slate-50 px-2 py-0.5 border border-slate-100">
                                        {{ __('courrier.status_label.'.$c->status) }}
                                    </span>
                                </div>
                                @if($c->date_limite)
                                    <div class="mt-2 text-[10px] {{ $c->is_late ? 'font-semibold text-rose-600' : 'text-slate-400' }}">
                                        SLA : {{ $c->date_limite->format('d/m/Y') }}
                                    </div>
                                @endif
                            </article>
                        @empty
                            <div class="kanban-empty pointer-events-none rounded-xl border border-dashed border-slate-300 bg-white/40 px-3 py-8 text-center text-xs text-slate-400">
                                {{ __('courrier.kanban_empty') }}
                            </div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </div>

    {{-- Modal note pour rejet --}}
    <div id="reject-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-900/40 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl">
            <h3 class="mb-1 text-lg font-bold text-slate-800">{{ __('courrier.kanban_reject_title') }}</h3>
            <p class="mb-3 text-sm text-slate-500">{{ __('courrier.kanban_reject_help') }}</p>
            <textarea id="reject-note" rows="3" class="mb-4 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300" placeholder="{{ __('courrier.reject_reason') }}"></textarea>
            <div class="flex justify-end gap-2">
                <button type="button" id="reject-cancel" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    {{ __('courrier.cancel') }}
                </button>
                <button type="button" id="reject-confirm" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                    {{ __('courrier.kanban_reject_confirm') }}
                </button>
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
                const delta = toOrdre - fromOrdre;
                if (delta === 1) return 'forward';
                if (delta === -1) return 'backward';
                if (delta < -1 && fromRejectable) return 'reject';
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
