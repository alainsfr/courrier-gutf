<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="mb-1 flex flex-wrap items-center gap-2">
                    <span class="rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                        {{ __('courrier.ref') }}
                    </span>
                    <h1 class="truncate text-lg font-bold text-slate-900">{{ $courrier->reference_interne }}</h1>
                    @if($courrier->workflowEtape)
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-bold text-white"
                              style="background: {{ $courrier->workflowEtape->ui_color ?? '#64748B' }}">
                            {{ $courrier->workflowEtape->libelle }}
                        </span>
                    @endif
                </div>
                <p class="mb-0 break-words text-sm text-slate-500">{{ $courrier->objet }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @can('update', $courrier)
                    @if(in_array($courrier->etapeOrdre(), [2, 3], true) && ! $courrier->isArchived())
                        <x-buttons.secondary :href="route('courriers.edit', $courrier)">{{ __('courrier.correct_title') }}</x-buttons.secondary>
                    @endif
                @endcan
                <x-buttons.secondary :href="route('courriers.bordereau', $courrier)" target="_blank" rel="noopener">
                    {{ __('courrier.bordereau_print') }}
                </x-buttons.secondary>
                <a href="{{ route('courriers.kanban') }}" class="ui-card px-3 py-2 text-sm font-semibold text-slate-600 no-underline hover:bg-slate-50">
                    {{ __('nav.kanban') }}
                </a>
                <a href="{{ route('courriers.index') }}" class="ui-card px-3 py-2 text-sm font-semibold text-slate-600 no-underline hover:bg-slate-50">
                    ← {{ __('courrier.back') }}
                </a>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="mb-4 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ match (session('status')) {
                'workflow-transferred' => __('courrier.show_transfer_ok'), 'courrier-completed' => __('courrier.direct_completed'),
                'courrier-claimed' => __('courrier.claimed_ok'), 'courrier-corrected' => __('courrier.corrected_ok'),
                'courrier-reassigned' => __('courrier.reassigned_ok'), 'courrier-created' => __('courrier.created_ok'),
                'courrier-assigned' => __('courrier.assigned_ok'), 'courrier-processing-started' => __('courrier.processing_ok'),
                'courrier-already-created' => __('courrier.already_created'),
                'courrier-archived' => __('courrier.archived_ok'), 'attachment-uploaded' => __('ux.attachment_added'), default => session('status') } }}
        </div>
    @endif

    <x-form-errors />

    @php
        $previewName = strtolower((string) ($previewAttachment?->original_name ?? ''));
        $previewMime = strtolower((string) ($previewAttachment?->mime ?? ''));
        $isPdf = $previewAttachment && (
            method_exists($previewAttachment, 'isPdf')
                ? $previewAttachment->isPdf()
                : (str_contains($previewMime, 'pdf') || str_ends_with($previewName, '.pdf'))
        );
        $isImage = $previewAttachment && (
            method_exists($previewAttachment, 'isImage')
                ? $previewAttachment->isImage()
                : (
                    str_starts_with($previewMime, 'image/')
                    || str_ends_with($previewName, '.png')
                    || str_ends_with($previewName, '.jpg')
                    || str_ends_with($previewName, '.jpeg')
                )
        );
        $proprietaire = $courrier->proprietaire
            ?: ($courrier->type === 'entrant' ? ($courrier->expediteur ?: '—') : ($courrier->destinataire ?: '—'));
        $etapeColor = $courrier->workflowEtape?->ui_color ?? '#64748B';
        $tfDisplay = $courrier->tf ?: ($courrier->reference_externe ?: $courrier->reference_interne);
        $previewUrl = $previewAttachment
            ? route('courriers.attachments.preview', [$courrier, $previewAttachment])
            : null;
    @endphp

    {{-- SPLIT-SCREEN plein viewport --}}
    <div class="courrier-workspace">

        {{-- GAUCHE : VISIONNEUSE --}}
        <section id="document-viewer"
                 data-preview-expanded="0"
                 class="courrier-document flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex shrink-0 items-center justify-between gap-2 border-b border-slate-200 px-4 py-3">
                <div class="min-w-0">
                    <div class="text-sm font-bold text-slate-800">{{ __('courrier.show_viewer_title') }}</div>
                    <div class="truncate text-xs text-slate-500">
                        {{ $previewAttachment?->original_name ?? __('courrier.no_attachments') }}
                    </div>
                </div>
                @if($previewAttachment)
                    <div class="flex shrink-0 items-center gap-2">
                        <button type="button"
                                data-preview-open
                                class="rounded-lg border border-brand bg-brand px-2.5 py-1.5 text-xs font-semibold text-white transition hover:bg-[#122c3b]">
                            {{ __('courrier.preview') }}
                        </button>
                        <button type="button"
                                data-preview-close
                                hidden
                                class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                            {{ __('courrier.preview_close') }}
                        </button>
                        <a href="{{ route('courriers.attachments.download', [$courrier, $previewAttachment]) }}"
                           class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-600 no-underline hover:bg-slate-50">
                            {{ __('courrier.download') }}
                        </a>
                    </div>
                @endif
            </div>

            <div class="relative min-h-0 flex-1 overflow-hidden bg-slate-100" data-preview-stage>
                @if($previewAttachment && $isPdf && $previewUrl)
                    <div
                        data-pdf-viewer
                        data-url="{{ $previewUrl }}"
                        class="absolute inset-0 overflow-auto p-3"
                        aria-label="{{ $previewAttachment->original_name }}"
                    >
                        <p
                            data-pdf-status
                            data-loading-label="{{ __('courrier.show_viewer_loading') }}"
                            data-error-label="{{ __('courrier.show_viewer_error') }}"
                            class="mb-3 text-center text-sm text-slate-500"
                        >
                            {{ __('courrier.show_viewer_loading') }}
                        </p>
                        <div data-pdf-pages class="mx-auto w-full max-w-4xl"></div>
                    </div>
                @elseif($previewAttachment && $isImage && $previewUrl)
                    <div class="flex h-full items-center justify-center overflow-auto bg-slate-900 p-4">
                        <img
                            src="{{ $previewUrl }}"
                            alt="{{ $previewAttachment->original_name }}"
                            class="max-h-full max-w-full rounded-lg object-contain shadow-lg"
                        >
                    </div>
                @elseif($previewAttachment)
                    <div class="flex h-full flex-col items-center justify-center gap-3 p-6 text-center text-slate-500">
                        <div class="text-sm">{{ __('courrier.show_viewer_unsupported') }}</div>
                        <a href="{{ route('courriers.attachments.download', [$courrier, $previewAttachment]) }}"
                           class="rounded-xl bg-brand px-4 py-2 text-sm font-semibold text-white no-underline">
                            {{ __('courrier.download') }}
                        </a>
                    </div>
                @else
                    <div class="flex h-full flex-col items-center justify-center gap-2 p-8 text-center text-slate-400">
                        <svg class="mb-3 h-12 w-12 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><path d="M6 3h8l4 4v14H6zM14 3v5h5M9 12h6M9 16h6"/></svg>
                        <div class="text-sm font-medium">{{ __('courrier.no_attachments') }}</div>
                        <div class="text-xs text-slate-500">{{ __('courrier.show_viewer_empty_hint') }}</div>
                    </div>
                @endif
            </div>

            @if($courrier->attachments->count() > 1)
                <div class="flex shrink-0 gap-2 overflow-x-auto border-t border-slate-200 bg-slate-50 px-3 py-2">
                    @foreach($courrier->attachments as $att)
                        <a href="{{ route('courriers.show', [$courrier, 'att' => $att->id]) }}#document-viewer"
                           class="shrink-0 rounded-lg border px-2.5 py-1.5 text-[11px] font-semibold no-underline
                                  {{ $previewAttachment && $previewAttachment->id === $att->id ? 'border-brand bg-brand text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-100' }}">
                            {{ \Illuminate\Support\Str::limit($att->original_name, 28) }}
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
        @if($previewAttachment)
            @vite('resources/js/pdf-viewer.js')
        @endif

        {{-- DROITE : CARTES --}}
        <aside class="courrier-inspector">

            {{-- Actions transfert --}}
            <div class="ui-card p-6 shadow-sm">
                <h2 class="mb-3 text-sm font-bold text-slate-900">{{ __('ux.next_action') }}</h2>

                @if($canActOnBureau && ! $courrier->isArchived())
                    <div class="space-y-2">
                        @if($canClaim)
                                <form data-submit-once method="POST" action="{{ route('courriers.claim', $courrier) }}">
                                    @csrf
                                    <x-buttons.primary type="submit" class="w-full">{{ __('courrier.claim') }}</x-buttons.primary>
                                    <p class="mt-1.5 text-xs text-slate-500">{{ __('courrier.claim_hint') }}</p>
                                </form>
                        @endif
                        @if($nextEtape && $canAdvance && ! $canClaim)
                            <form data-submit-once method="POST" action="{{ $courrier->isAtOrdre(3) ? route('courriers.complete', $courrier) : route('courriers.transfer', $courrier) }}">
                                @csrf
                                <input type="hidden" name="nouvelle_etape_id" value="{{ $nextEtape->id }}">
                                <input type="hidden" name="action_type" value="forward">
                                <x-buttons.primary type="submit" class="w-full">
                                    {{ match ((int) $nextEtape->ordre) { 3 => __('courrier.direct_start'), 5 => __('courrier.direct_complete'), 6 => __('courrier.archive'), default => __('courrier.show_action_validate') } }}
                                </x-buttons.primary>
                            </form>
                        @endif

                        @if($prevEtape && $canReturn)
                            <details class="courrier-disclosure mt-3"><summary>{{ __('ux.other_actions') }}</summary><div class="space-y-3 p-3">
                        @endif
                        @if($prevEtape && $canReturn && $courrier->workflowEtape?->is_rejectable)
                            <form data-submit-once method="POST" action="{{ route('courriers.transfer', $courrier) }}" class="rounded-xl border border-rose-100 bg-rose-50/50 p-3">
                                @csrf
                                <input type="hidden" name="nouvelle_etape_id" value="{{ $prevEtape->id }}">
                                <input type="hidden" name="action_type" value="reject">
                                <label class="mb-1 block text-[11px] font-semibold text-rose-700" for="ui-note_metier">{{ __('courrier.reject_reason') }}</label>
                                <textarea id="ui-note_metier" name="note_metier" rows="2" required
                                          class="ui-input mb-2 w-full rounded-md border border-rose-200 px-2.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-200"
                                          placeholder="{{ __('courrier.reject_reason') }}"></textarea>
                                <x-buttons.danger type="submit" class="w-full">
                                    {{ __('courrier.show_action_reject') }}
                                </x-buttons.danger>
                            </form>
                        @endif

                        @if($prevEtape && $canReturn)
                            <form data-submit-once method="POST" action="{{ route('courriers.transfer', $courrier) }}">
                                @csrf
                                <input type="hidden" name="nouvelle_etape_id" value="{{ $prevEtape->id }}">
                                <input type="hidden" name="action_type" value="backward">
                                <x-buttons.secondary type="submit" class="w-full">
                                    {{ __('courrier.show_action_return') }}
                                    <span class="text-[11px] font-normal text-slate-500">→ {{ $prevEtape->libelle }}</span>
                                </x-buttons.secondary>
                            </form>
                        @endif

                        @if($prevEtape && $canReturn)</div></details>@endif

                        @if(! $canAdvance && ! $canReturn)
                            <p class="mb-0 text-xs text-slate-500">{{ __('courrier.show_actions_none') }}</p>
                        @endif
                    </div>
                @elseif($courrier->isArchived())
                    <div class="rounded-xl bg-slate-50 px-3 py-4 text-center text-xs text-slate-500">
                        {{ __('courrier.show_archived') }}
                    </div>
                @else
                    <div class="rounded-xl border border-amber-100 bg-amber-50 px-3 py-3 text-xs text-amber-800">
                        {{ __('courrier.show_actions_locked', ['bureau' => $courrier->bureauActuel?->nom ?? '—']) }}
                    </div>
                @endif

                @can('reassign', $courrier)
                    @if(! $courrier->isArchived() && in_array($courrier->etapeOrdre(), [2, 3], true))
                        <div class="mt-3 border-t border-slate-100 pt-3">
                            <x-buttons.secondary :href="route('courriers.reassignment', $courrier)" class="w-full">{{ __('courrier.reassign_title') }}</x-buttons.secondary>
                        </div>
                    @endif
                @endcan
            </div>

            {{-- Métadonnées --}}
            <div class="ui-card p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between gap-2">
                    <h2 class="m-0 text-sm font-bold text-slate-900">{{ __('courrier.show_meta_title') }}</h2>
                    <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide"
                          style="color: {{ $etapeColor }}; background: {{ $etapeColor }}18;">
                        {{ $courrier->workflowEtape?->libelle ?? '—' }}
                    </span>
                </div>

                <dl class="m-0 space-y-3">
                    <div>
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('courrier.show_tf') }}</dt>
                        <dd class="m-0 mt-0.5 text-sm font-semibold text-slate-800">{{ $tfDisplay }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('courrier.subject') }}</dt>
                        <dd class="m-0 mt-0.5 text-sm font-semibold leading-snug text-slate-800">{{ $courrier->objet }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('courrier.show_owner') }}</dt>
                        <dd class="m-0 mt-0.5 text-sm font-semibold text-slate-800">{{ $proprietaire }}</dd>
                    </div>
                    <div class="grid grid-cols-2 gap-3 border-t border-slate-100 pt-3">
                        <div>
                            <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('courrier.type') }}</dt>
                            <dd class="m-0 mt-0.5 text-xs font-semibold uppercase text-slate-700">{{ $courrier->type }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('courrier.show_bureau') }}</dt>
                            <dd class="m-0 mt-0.5 text-xs font-semibold text-slate-700">{{ $courrier->bureauActuel?->nom ?? '—' }}</dd>
                        </div>
                    </div>
                    <div>
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __('courrier.assigned_to') }}</dt>
                        <dd class="m-0 mt-0.5 text-sm text-slate-700">{{ $courrier->assignedTo?->name ?? __('ux.unassigned') }}</dd>
                    </div>

                    <div class="grid grid-cols-2 gap-3 border-t border-slate-100 pt-3">
                        <div><dt class="text-xs text-slate-500">{{ __('ux.received_on') }}</dt><dd class="mt-1 mb-0 text-sm font-semibold">{{ $courrier->date_reception?->format('d/m/Y') ?? '—' }}</dd></div>
                        @if($courrier->date_limite && in_array($courrier->etapeOrdre(), [2, 3], true))
                            <div><dt class="text-xs text-slate-500">{{ __('ux.deadline') }}</dt><dd class="mt-1 mb-0 text-sm font-semibold {{ $courrier->date_limite->isPast() ? 'text-rose-700' : 'text-slate-800' }}">{{ $courrier->date_limite->format('d/m/Y H:i') }} @if($courrier->date_limite->isPast())<span class="mt-1 block text-xs">{{ __('ux.late') }}</span>@endif</dd></div>
                        @endif
                    </div>
                </dl>
            </div>


            <details class="courrier-disclosure bg-white">
                <summary>{{ __('ux.details_title') }}</summary>
                <dl class="m-0 space-y-3 p-4">
                    @foreach(['reference_externe' => 'external_ref', 'description' => 'description', 'expediteur' => 'sender', 'destinataire' => 'recipient', 'tf' => 'field_tf', 'proprietaire' => 'field_proprietaire', 'requerant' => 'field_requerant', 'vendeur' => 'field_vendeur', 'etude_notariale' => 'field_etude', 'numero_acte' => 'field_numero_acte', 'numero_repertoire' => 'field_numero_repertoire', 'valeur_immeuble' => 'field_valeur', 'date_courrier' => 'date_mail', 'date_envoi' => 'date_sent'] as $field => $label)
                        @if(filled($courrier->$field))
                            <div><dt class="text-xs text-slate-500">{{ __('courrier.'.$label) }}</dt><dd class="mt-1 mb-0 whitespace-pre-wrap break-words text-sm text-slate-800">{{ in_array($field, ['date_courrier', 'date_envoi']) ? $courrier->$field->format('d/m/Y') : $courrier->$field }}</dd></div>
                        @endif
                    @endforeach
                </dl>
            </details>
        </aside>
        <div class="courrier-secondary">

            <section class="courrier-card">
                <h2 class="mb-4 text-sm font-bold text-slate-900">{{ __('courrier.attachments') }} <span class="font-normal text-slate-400">({{ $courrier->attachments->count() }})</span></h2>
                <ul class="m-0 list-none space-y-3 p-0">
                    @forelse($courrier->attachments as $attachment)
                        <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-slate-50 px-3 py-2.5">
                            <a class="min-w-0 break-all text-sm font-semibold text-slate-700" href="{{ route('courriers.show', [$courrier, 'att' => $attachment->id]) }}#document-viewer">{{ $attachment->original_name }}</a>
                            <a class="text-xs font-semibold" href="{{ route('courriers.attachments.download', [$courrier, $attachment]) }}">{{ __('courrier.download') }}</a>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">{{ __('courrier.no_attachments') }}</li>
                    @endforelse
                </ul>
                @can('attach', $courrier)
                    @if(! $courrier->isArchived())
                        <form method="POST" action="{{ route('courriers.attachments.upload', $courrier) }}" enctype="multipart/form-data" class="mt-4 border-t border-slate-100 pt-4" data-submit-once>
                            @csrf
                            <label for="additional-document" class="mb-2 block text-sm font-semibold text-slate-700">{{ __('ux.add_document') }}</label>
                            <input id="additional-document" name="file" type="file" accept=".pdf,.jpg,.jpeg,.png" required class="ui-input courrier-file" aria-describedby="additional-document-help">
                            <p id="additional-document-help" class="mt-2 mb-3 text-xs text-slate-500">{{ __('courrier.attachments_help') }}</p>
                            @error('file')<p class="text-xs text-rose-700">{{ $message }}</p>@enderror
                            <x-buttons.secondary type="submit">{{ __('ux.add_document') }}</x-buttons.secondary>
                        </form>
                    @endif
                @endcan
            </section>

            @if (! empty($pendingVisa))
                <div class="rounded-xl border border-accent-soft bg-gradient-to-br from-accent-soft to-white p-6 shadow-sm">
                    <div class="mb-1 text-[10px] font-bold uppercase tracking-[0.14em] text-accent">
                        {{ __('parapheur.nav') }}
                    </div>
                    <h2 class="mb-2 text-base font-bold text-slate-900">{{ __('parapheur.action_title') }}</h2>
                    <p class="mb-4 text-sm text-slate-600">{{ __('parapheur.action_text') }}</p>

                    <button type="button"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-md border border-transparent bg-brand px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-[#122c3b] focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2"

                            data-ui-dialog-open="visa-pin-modal">
                        {{ __('parapheur.approve') }}
                    </button>

                    <form method="POST" action="{{ route('parapheur.reject', $pendingVisa) }}" class="mt-3 rounded-xl border border-rose-100 bg-rose-50/60 p-3">
                        @csrf
                        <label for="visa_reject_comment" class="mb-1 block text-[11px] font-semibold text-rose-700">
                            {{ __('parapheur.reject_comment') }}
                        </label>
                        <textarea id="visa_reject_comment" name="comment" rows="2" required
                                  class="ui-input mb-2 w-full rounded-md border border-rose-200 px-2.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-200"
                                  placeholder="{{ __('parapheur.reject_placeholder') }}">{{ old('comment') }}</textarea>
                        @error('comment')
                            <p class="mb-2 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                        <x-buttons.danger type="submit" class="w-full">
                            {{ __('parapheur.reject') }}
                        </x-buttons.danger>
                    </form>
                </div>

                {{-- Modale PIN signature --}}
                <dialog class="ui-confirm-dialog ui-signature-dialog" id="visa-pin-modal" aria-labelledby="visa-pin-modal-label">
                    <div>
                        <div class="rounded-xl border-0 shadow-lg">
                            <form method="POST" action="{{ route('parapheur.approve', $pendingVisa) }}">
                                @csrf
                                <div class="ui-dialog-header border-b border-slate-100 px-5 py-4">
                                    <h3 class="modal-title text-base font-bold text-slate-900" id="visa-pin-modal-label">
                                        {{ __('parapheur.pin_modal_title') }}
                                    </h3>
                                    <button type="button" class="ui-dialog-close" data-ui-dialog-close aria-label="{{ __('parapheur.pin_modal_cancel') }}"><span aria-hidden="true">×</span></button>
                                </div>
                                <div class="px-5 py-4">
                                    <p class="mb-3 text-sm text-slate-600">{{ __('parapheur.pin_modal_text') }}</p>
                                    <label for="signature_pin" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">
                                        {{ __('parapheur.pin_label') }}
                                    </label>
                                    <input id="signature_pin"
                                           name="signature_pin"
                                           type="password"
                                           inputmode="numeric"
                                           pattern="[0-9]{4}"
                                           maxlength="4"
                                           required
                                           autocomplete="one-time-code"
                                           class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2.5 text-center text-lg tracking-[0.4em] focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand-soft"
                                           placeholder="••••"
                                           >
                                    @error('signature_pin')
                                        <p class="mt-2 mb-0 text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                    @unless(auth()->user()?->signature_pin)
                                        <p class="mt-2 mb-0 text-xs text-amber-700">
                                            {{ __('parapheur.pin_not_set') }}
                                            <a href="{{ route('profile.edit') }}" class="font-semibold underline">{{ __('parapheur.pin_configure') }}</a>
                                        </p>
                                    @endunless
                                </div>
                                <div class="ui-dialog-footer border-t border-slate-100 px-5 py-3">
                                    <x-buttons.secondary type="button" data-ui-dialog-close>
                                        {{ __('parapheur.pin_modal_cancel') }}
                                    </x-buttons.secondary>
                                    <x-buttons.primary type="submit">
                                        {{ __('parapheur.pin_modal_confirm') }}
                                    </x-buttons.primary>
                                </div>
                            </form>
                        </div>
                    </div>
                </dialog>

                @if ($errors->has('signature_pin'))
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            document.querySelector('[data-ui-dialog-open="visa-pin-modal"]')?.click();
                        });
                    </script>
                @endif
            @endif

            @if (empty($pendingVisa) && empty($hasPendingVisa) && ! $courrier->isArchived() && auth()->user()->can('submitVisa', $courrier))
                <details class="courrier-card courrier-disclosure" @if($errors->has("validator_id")) open @endif>
                    <summary>{{ __('ux.visa_optional') }}</summary><div class="pt-4">
                    <p class="mb-3 text-xs text-slate-500">{{ __('parapheur.submit_hint') }}</p>

                    @if (($parapheurValidators ?? collect())->isEmpty())
                        <p class="mb-0 text-xs text-amber-700">{{ __('parapheur.submit_no_validators') }}</p>
                    @else
                        <form method="POST" action="{{ route('courriers.parapheur.submit', $courrier) }}">
                            @csrf
                            <label for="validator_id" class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-400">
                                {{ __('parapheur.submit_validator') }}
                            </label>
                            <select id="validator_id" name="validator_id" required
                                    class="ui-input mb-2 w-full rounded-md border border-slate-300 px-2.5 py-2 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand-soft">
                                <option value="">{{ __('parapheur.submit_validator_placeholder') }}</option>
                                @foreach($parapheurValidators as $validator)
                                    <option value="{{ $validator->id }}" @selected((string) old('validator_id') === (string) $validator->id)>
                                        {{ $validator->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('validator_id')
                                <p class="mb-2 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                            <x-buttons.primary type="submit" class="w-full">
                                {{ __('parapheur.submit_button') }}
                            </x-buttons.primary>
                        </form>
                    @endif
                </div></details>
            @endif

            {{-- Dossier & Courriers Liés --}}
            @php
                $linkedChildren = $courrier->linkedCourriers ?? collect();
                $linkedParents = $courrier->parentCourrier ?? collect();
                $allLinked = $linkedChildren->merge($linkedParents)->unique('id');
            @endphp
            <div class="ui-card p-6 shadow-sm">
                <h2 class="mb-3 text-sm font-bold text-slate-900">{{ __('courrier.links_title') }}</h2>

                <div class="mb-3 space-y-2">
                    @forelse($allLinked as $linked)
                        <a href="{{ route('courriers.show', $linked) }}"
                           class="flex items-start justify-between gap-2 rounded-lg border border-slate-100 bg-slate-50/80 px-3 py-2.5 no-underline transition hover:border-brand-soft hover:bg-white">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold text-slate-900">{{ $linked->reference_interne }}</div>
                                <div class="truncate text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($linked->objet, 48) }}</div>
                            </div>
                            @if($linked->workflowEtape)
                                <span class="shrink-0 rounded-md px-2 py-0.5 text-[10px] font-semibold"
                                      style="color: {{ $linked->workflowEtape->ui_color ?? '#64748B' }}; background: {{ ($linked->workflowEtape->ui_color ?? '#64748B') }}18;">
                                    {{ $linked->workflowEtape->libelle }}
                                </span>
                            @endif
                        </a>
                    @empty
                        <x-empty-state class="!py-5" :title="__('courrier.links_empty')" />
                    @endforelse
                </div>

                @if(! $courrier->isArchived() && auth()->user()->can('link', $courrier))
                <form method="POST" action="{{ route('courriers.links.store', $courrier) }}" class="border-t border-slate-100 pt-3">
                    @csrf
                    <label for="link_reference" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">
                        {{ __('courrier.links_label') }}
                    </label>
                    <div class="flex gap-2">
                        <input id="link_reference" type="text" name="reference" value="{{ old('reference') }}" required
                               class="ui-input min-w-0 flex-1 rounded-md border border-slate-300 px-2.5 py-2 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand-soft"
                               placeholder="{{ __('courrier.links_placeholder') }}">
                        <x-buttons.primary type="submit" class="shrink-0 !px-3">
                            {{ __('courrier.links_submit') }}
                        </x-buttons.primary>
                    </div>
                    @error('reference')
                        <p class="mt-1.5 mb-0 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </form>
                @endif
            </div>

            {{-- Notes & Instructions Internes --}}
            <div class="ui-card p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-bold text-slate-900">{{ __('courrier.comments_title') }}</h2>

                <div class="mb-4 max-h-72 space-y-3 overflow-y-auto pe-1">
                    @forelse($courrier->comments as $comment)
                        <article class="rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-3">
                            <div class="mb-2 flex items-start gap-2.5">
                                <x-user-avatar :user="$comment->user" size="sm" class="shrink-0" />
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-baseline justify-between gap-x-2 gap-y-0.5">
                                        <span class="truncate text-sm font-semibold text-slate-900">
                                            {{ $comment->user?->name ?? '—' }}
                                        </span>
                                        <time class="shrink-0 text-[11px] text-slate-400"
                                              datetime="{{ $comment->created_at?->toIso8601String() }}"
                                              title="{{ $comment->created_at?->format('d/m/Y H:i') }}">
                                            {{ $comment->created_at?->diffForHumans() }}
                                        </time>
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        {{ $comment->user?->bureau?->nom ?? __('courrier.comments_no_bureau') }}
                                    </div>
                                </div>
                            </div>
                            <p class="mb-0 whitespace-pre-wrap text-sm leading-relaxed text-slate-700">{{ $comment->body }}</p>
                        </article>
                    @empty
                        <x-empty-state
                            class="!py-6"
                            :title="__('courrier.comments_empty')"
                        />
                    @endforelse
                </div>

                <form method="POST" action="{{ route('courriers.comments.store', $courrier) }}" class="border-t border-slate-100 pt-3">
                    @csrf
                    <label for="comment_body" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">
                        {{ __('courrier.comments_label') }}
                    </label>
                    <textarea
                        id="comment_body"
                        name="body"
                        rows="3"
                        required
                        maxlength="5000"
                        class="ui-input mb-2 w-full rounded-md border border-slate-300 px-2.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand-soft"
                        placeholder="{{ __('courrier.comments_placeholder') }}"
                    >{{ old('body') }}</textarea>
                    @error('body')
                        <p class="mb-2 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                    <x-buttons.primary type="submit" class="w-full">
                        {{ __('courrier.comments_submit') }}
                    </x-buttons.primary>
                </form>
            </div>

            {{-- Timeline --}}
            <x-courrier-timeline :histories="$courrier->histories" />
        </div>
    </div>
</x-app-layout>
