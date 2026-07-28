@props(['histories'])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white p-4 shadow-sm']) }}>
    <h2 class="mb-4 text-sm font-bold text-slate-900">{{ __('courrier.history') }}</h2>

    @forelse($histories as $h)
        @php
            $labelKey = 'courrier.history_actions.'.$h->action;
            $label = __($labelKey);
            if ($label === $labelKey) {
                $label = $h->action;
            }
            $dotColor = $h->nouvelleEtape?->ui_color
                ?? ($h->action_type === 'reject' ? '#E11D48' : '#64748B');
        @endphp

        <div class="relative flex gap-3 pb-5 last:pb-0">
            @if(! $loop->last)
                <span class="absolute bottom-0 left-[9px] top-5 w-px bg-slate-200" aria-hidden="true"></span>
            @endif

            <span class="relative z-10 mt-1 h-[18px] w-[18px] shrink-0 rounded-full border-2 border-white shadow ring-1 ring-slate-200"
                  style="background: {{ $dotColor }}"
                  aria-hidden="true"></span>

            <div class="min-w-0 flex-1">
                <div class="text-[11px] text-slate-400">
                    {{ $h->created_at?->format('d/m/Y H:i') }}
                    · {{ $h->actor?->name ?? 'Système' }}
                    @if($h->action_type)
                        <span class="ms-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-slate-500">
                            {{ $h->action_type }}
                        </span>
                    @endif
                </div>

                <div class="mt-0.5 text-sm font-semibold text-slate-800">{{ $label }}</div>

                @if($h->ancienneEtape || $h->nouvelleEtape)
                    <div class="mt-1 text-xs text-slate-500">
                        {{ $h->ancienneEtape?->libelle ?? '—' }}
                        →
                        <strong class="text-slate-700">{{ $h->nouvelleEtape?->libelle ?? '—' }}</strong>
                    </div>
                @elseif($h->from_status || $h->to_status)
                    <div class="mt-1 text-xs text-slate-500">
                        {{ $h->from_status ? __('courrier.status_label.'.$h->from_status) : '—' }}
                        →
                        <strong>{{ $h->to_status ? __('courrier.status_label.'.$h->to_status) : '—' }}</strong>
                    </div>
                @endif

                @if($h->note_metier)
                    <div class="mt-2 rounded-lg bg-slate-50 px-2.5 py-2 text-xs leading-relaxed text-slate-600">
                        {{ $h->note_metier }}
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-slate-200 px-3 py-6 text-center text-xs text-slate-400">
            {{ __('courrier.no_history') }}
        </div>
    @endforelse
</div>
