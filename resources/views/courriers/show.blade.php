<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="mb-1 flex flex-wrap items-center gap-2">
                    <span class="rounded-full border border-gray-200 bg-white px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                        {{ __('courrier.ref') }}
                    </span>
                    <h1 class="truncate text-lg font-bold text-gray-900">{{ $courrier->reference_interne }}</h1>
                    @if($courrier->workflowEtape)
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-bold text-white"
                              style="background: {{ $courrier->workflowEtape->ui_color ?? '#64748B' }}">
                            {{ $courrier->workflowEtape->libelle }}
                        </span>
                    @endif
                </div>
                <p class="mb-0 truncate text-sm text-gray-500">{{ $courrier->objet }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('courriers.kanban') }}" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-600 no-underline hover:bg-gray-50">
                    {{ __('nav.kanban') }}
                </a>
                <a href="{{ route('courriers.index') }}" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-600 no-underline hover:bg-gray-50">
                    ← {{ __('courrier.back') }}
                </a>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="mb-4 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('status') === 'workflow-transferred' ? __('courrier.show_transfer_ok') : session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <div class="mb-1 font-bold">{{ __('courrier.form_error_title') }}</div>
            <ul class="mb-0 list-disc ps-4">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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
    <div class="grid grid-cols-1 gap-4 lg:h-[calc(100vh-8rem)] lg:grid-cols-3 lg:gap-5 lg:overflow-hidden">

        {{-- GAUCHE : VISIONNEUSE --}}
        <section id="document-viewer" class="order-2 flex h-[70vh] min-h-[420px] flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm lg:order-1 lg:col-span-2 lg:h-full lg:min-h-0">
            <div class="flex shrink-0 items-center justify-between gap-2 border-b border-gray-200 px-4 py-3">
                <div class="min-w-0">
                    <div class="text-sm font-bold text-gray-800">{{ __('courrier.show_viewer_title') }}</div>
                    <div class="truncate text-xs text-gray-500">
                        {{ $previewAttachment?->original_name ?? __('courrier.no_attachments') }}
                    </div>
                </div>
                @if($previewAttachment)
                    <div class="flex shrink-0 items-center gap-2">
                        <a href="#document-viewer"
                           class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-semibold text-gray-600 no-underline hover:bg-gray-50">
                            {{ __('courrier.preview') }}
                        </a>
                        <a href="{{ route('courriers.attachments.download', [$courrier, $previewAttachment]) }}"
                           class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-semibold text-gray-600 no-underline hover:bg-gray-50">
                            {{ __('courrier.download') }}
                        </a>
                    </div>
                @endif
            </div>

            <div class="relative min-h-0 flex-1 overflow-hidden bg-slate-100">
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
                    @vite('resources/js/pdf-viewer.js')
                @elseif($previewAttachment && $isImage && $previewUrl)
                    <div class="flex h-full items-center justify-center overflow-auto bg-slate-900 p-4">
                        <img
                            src="{{ $previewUrl }}"
                            alt="{{ $previewAttachment->original_name }}"
                            class="max-h-full max-w-full rounded-lg object-contain shadow-lg"
                        >
                    </div>
                @elseif($previewAttachment)
                    <div class="flex h-full flex-col items-center justify-center gap-3 p-6 text-center text-gray-500">
                        <div class="text-sm">{{ __('courrier.show_viewer_unsupported') }}</div>
                        <a href="{{ route('courriers.attachments.download', [$courrier, $previewAttachment]) }}"
                           class="rounded-xl bg-[#17384b] px-4 py-2 text-sm font-semibold text-white no-underline">
                            {{ __('courrier.download') }}
                        </a>
                    </div>
                @else
                    <div class="flex h-full flex-col items-center justify-center gap-2 p-8 text-center text-gray-400">
                        <div class="text-sm font-medium">{{ __('courrier.no_attachments') }}</div>
                        <div class="text-xs text-gray-500">{{ __('courrier.show_viewer_empty_hint') }}</div>
                    </div>
                @endif
            </div>

            @if($courrier->attachments->count() > 1)
                <div class="flex shrink-0 gap-2 overflow-x-auto border-t border-gray-200 bg-gray-50 px-3 py-2">
                    @foreach($courrier->attachments as $att)
                        <a href="{{ route('courriers.show', [$courrier, 'att' => $att->id]) }}#document-viewer"
                           class="shrink-0 rounded-lg border px-2.5 py-1.5 text-[11px] font-semibold no-underline
                                  {{ $previewAttachment && $previewAttachment->id === $att->id ? 'border-[#17384b] bg-[#17384b] text-white' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-100' }}">
                            {{ \Illuminate\Support\Str::limit($att->original_name, 28) }}
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- DROITE : CARTES --}}
        <aside class="order-1 flex flex-col gap-4 lg:order-2 lg:col-span-1 lg:min-h-0 lg:overflow-y-auto">

            {{-- Métadonnées --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between gap-2">
                    <h2 class="m-0 text-sm font-bold text-gray-900">{{ __('courrier.show_meta_title') }}</h2>
                    <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide"
                          style="color: {{ $etapeColor }}; background: {{ $etapeColor }}18;">
                        {{ $courrier->workflowEtape?->libelle ?? '—' }}
                    </span>
                </div>

                <dl class="m-0 space-y-3">
                    <div>
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ __('courrier.show_tf') }}</dt>
                        <dd class="m-0 mt-0.5 text-sm font-semibold text-gray-800">{{ $tfDisplay }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ __('courrier.subject') }}</dt>
                        <dd class="m-0 mt-0.5 text-sm font-semibold leading-snug text-gray-800">{{ $courrier->objet }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ __('courrier.show_owner') }}</dt>
                        <dd class="m-0 mt-0.5 text-sm font-semibold text-gray-800">{{ $proprietaire }}</dd>
                    </div>
                    <div class="grid grid-cols-2 gap-3 border-t border-gray-100 pt-3">
                        <div>
                            <dt class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ __('courrier.type') }}</dt>
                            <dd class="m-0 mt-0.5 text-xs font-semibold uppercase text-gray-700">{{ $courrier->type }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ __('courrier.show_bureau') }}</dt>
                            <dd class="m-0 mt-0.5 text-xs font-semibold text-gray-700">{{ $courrier->bureauActuel?->nom ?? '—' }}</dd>
                        </div>
                    </div>
                    <div>
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ __('courrier.assigned_to') }}</dt>
                        <dd class="m-0 mt-0.5 text-sm text-gray-700">{{ $courrier->assignedTo?->name ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Actions transfert --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="mb-3 text-sm font-bold text-gray-900">{{ __('courrier.show_actions_title') }}</h2>

                @if($canActOnBureau && ! $courrier->isArchived())
                    <div class="space-y-2">
                        @if($nextEtape)
                            <form method="POST" action="{{ route('courriers.transfer', $courrier) }}">
                                @csrf
                                <input type="hidden" name="nouvelle_etape_id" value="{{ $nextEtape->id }}">
                                <input type="hidden" name="action_type" value="forward">
                                <x-buttons.primary type="submit" class="w-full">
                                    {{ __('courrier.show_action_validate') }}
                                    <span class="text-[11px] font-normal opacity-90">→ {{ $nextEtape->libelle }}</span>
                                </x-buttons.primary>
                            </form>
                        @endif

                        @if($prevEtape && $courrier->workflowEtape?->is_rejectable)
                            <form method="POST" action="{{ route('courriers.transfer', $courrier) }}" class="rounded-xl border border-rose-100 bg-rose-50/50 p-3">
                                @csrf
                                <input type="hidden" name="nouvelle_etape_id" value="{{ $prevEtape->id }}">
                                <input type="hidden" name="action_type" value="reject">
                                <label class="mb-1 block text-[11px] font-semibold text-rose-700">{{ __('courrier.reject_reason') }}</label>
                                <textarea name="note_metier" rows="2" required
                                          class="mb-2 w-full rounded-md border border-rose-200 px-2.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-200"
                                          placeholder="{{ __('courrier.reject_reason') }}"></textarea>
                                <x-buttons.danger type="submit" class="w-full">
                                    {{ __('courrier.show_action_reject') }}
                                </x-buttons.danger>
                            </form>
                        @endif

                        @if($prevEtape)
                            <form method="POST" action="{{ route('courriers.transfer', $courrier) }}">
                                @csrf
                                <input type="hidden" name="nouvelle_etape_id" value="{{ $prevEtape->id }}">
                                <input type="hidden" name="action_type" value="backward">
                                <x-buttons.secondary type="submit" class="w-full">
                                    {{ __('courrier.show_action_return') }}
                                    <span class="text-[11px] font-normal text-gray-500">→ {{ $prevEtape->libelle }}</span>
                                </x-buttons.secondary>
                            </form>
                        @endif

                        @if(! $nextEtape && ! $prevEtape)
                            <p class="mb-0 text-xs text-gray-500">{{ __('courrier.show_actions_none') }}</p>
                        @endif
                    </div>
                @elseif($courrier->isArchived())
                    <div class="rounded-xl bg-gray-50 px-3 py-4 text-center text-xs text-gray-500">
                        {{ __('courrier.show_archived') }}
                    </div>
                @else
                    <div class="rounded-xl border border-amber-100 bg-amber-50 px-3 py-3 text-xs text-amber-800">
                        {{ __('courrier.show_actions_locked', ['bureau' => $courrier->bureauActuel?->nom ?? '—']) }}
                    </div>
                @endif

                @can('assign', $courrier)
                    @if(! $courrier->isArchived() && $canActOnBureau)
                        <form method="POST" action="{{ route('courriers.assign', $courrier) }}" class="mt-3 border-t border-gray-100 pt-3">
                            @csrf
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-gray-400">{{ __('courrier.assign_to') }}</label>
                            <select name="assigned_to_user_id" class="mb-2 w-full rounded-md border border-gray-300 px-2.5 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30" required>
                                @foreach($agents as $a)
                                    <option value="{{ $a->id }}" @selected($courrier->assigned_to_user_id == $a->id)>{{ $a->name }}</option>
                                @endforeach
                            </select>
                            <x-buttons.primary type="submit" class="w-full">
                                {{ __('courrier.assign') }}
                            </x-buttons.primary>
                        </form>
                    @endif
                @endcan
            </div>

            {{-- Timeline --}}
            <x-courrier-timeline :histories="$courrier->histories" />
        </aside>
    </div>
</x-app-layout>
