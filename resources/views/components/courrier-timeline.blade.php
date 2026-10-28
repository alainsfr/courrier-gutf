@props(['histories'])

<div {{ $attributes->merge(['class' => 'ui-card p-5 shadow-sm']) }}>
    <h2 class="mb-5 text-sm font-bold text-slate-900">{{ __('courrier.history') }}</h2>

    <div class="relative">
        @forelse($histories as $h)
            @php
                $labelKey = 'courrier.history_actions.'.$h->action;
                $label = __($labelKey);
                if ($label === $labelKey) {
                    $label = $h->action;
                }
                $dotColor = $h->nouvelleEtape?->ui_color
                    ?? ($h->action_type === 'reject' ? '#E11D48' : '#17384b');
            @endphp

            <div class="relative flex gap-3 {{ $loop->last ? '' : 'pb-6' }}">
                @if(! $loop->last)
                    <span class="absolute bottom-0 left-[9px] top-5 w-px border-l border-dashed border-slate-300"
                          aria-hidden="true"></span>
                @endif

                <span class="relative z-10 mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center">
                    <span class="h-3 w-3 rounded-full ring-4 ring-white"
                          style="background: {{ $dotColor }}; box-shadow: 0 0 0 1px {{ $dotColor }}33;"
                          aria-hidden="true"></span>
                </span>

                <div class="min-w-0 flex-1">
                    <div class="text-[11px] text-slate-400">
                        {{ $h->created_at?->format('d/m/Y H:i') }}
                        · {{ $h->actor?->name ?? 'Système' }}
                        @if($h->action_type)
                            <span class="ms-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-slate-500">
                                {{ __('courrier.history_direction.'.$h->action_type) }}
                            </span>
                        @endif
                    </div>

                    <div class="mt-0.5 text-sm font-semibold text-slate-800">{{ $label }}</div>

                    @if($h->ancienneEtape || $h->nouvelleEtape)
                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
                            @if($h->ancienneEtape)
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                      style="color: {{ $h->ancienneEtape->ui_color ?? '#64748B' }}; background: {{ ($h->ancienneEtape->ui_color ?? '#64748B') }}18;">
                                    {{ $h->ancienneEtape->libelle }}
                                </span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                            <span class="text-slate-300">→</span>
                            @if($h->nouvelleEtape)
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                      style="color: {{ $h->nouvelleEtape->ui_color ?? '#64748B' }}; background: {{ ($h->nouvelleEtape->ui_color ?? '#64748B') }}18;">
                                    {{ $h->nouvelleEtape->libelle }}
                                </span>
                            @endif
                        </div>
                    @elseif($h->from_status || $h->to_status)
                        <div class="mt-1 text-xs text-slate-500">
                            {{ $h->from_status ?: '—' }} → <strong class="text-slate-700">{{ $h->to_status ?: '—' }}</strong>
                        </div>
                    @endif

                    @if($h->note_metier)
                        <div class="mt-2 rounded-lg border border-slate-100 bg-slate-50 px-2.5 py-2 text-xs leading-relaxed text-slate-600">
                            {{ $h->note_metier }}
                        </div>
                    @endif
                    @if($h->action === 'reassigned')
                        <p class="mt-1.5 text-xs text-slate-500">{{ data_get($h->metadata, 'before.bureau') ?? '—' }} → {{ data_get($h->metadata, 'after.bureau') ?? '—' }} · {{ data_get($h->metadata, 'after.agent') ?? __('courrier.assign_none') }}</p>
                    @endif
                    @if($h->action === 'details_updated')
                        <details class="mt-2 text-xs text-slate-500">
                            <summary class="cursor-pointer font-semibold">{{ __('courrier.view_changes') }}</summary>
                            <dl class="mt-2 space-y-2">
                                @foreach(data_get($h->metadata, 'after', []) as $field => $value)
                                    <div><dt class="font-semibold">{{ __('courrier.field_labels.'.$field) }}</dt>
                                        <dd class="m-0 break-words">{{ data_get($h->metadata, 'before.'.$field) ?? '—' }} → {{ $value ?? '—' }}</dd></div>
                                @endforeach
                            </dl>
                        </details>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-slate-200 px-3 py-6 text-center text-xs text-slate-400">
                {{ __('courrier.no_history') }}
            </div>
        @endforelse
    </div>
</div>
