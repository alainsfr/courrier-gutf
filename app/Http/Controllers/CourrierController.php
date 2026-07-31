<?php

namespace App\Http\Controllers;

use App\Exports\CourriersWorkbookExport;
use App\Models\ParapheurVisa;
use App\Models\Bureau;
use App\Models\Courrier;
use App\Models\CourrierAttachment;
use App\Models\CourrierHistory;
use App\Models\User;
use App\Models\WorkflowEtape;
use App\Services\CourrierQueryService;
use App\Services\AuditService;
use App\Services\WorkflowService;
use App\Notifications\CourrierStatusNotification;
use RuntimeException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class CourrierController extends Controller
{
    public function __construct(
        private readonly CourrierQueryService $queryService,
        private readonly AuditService $auditService,
        private readonly WorkflowService $workflowService,
    ) {}

    /* =========================================================
     |  Notifications helper
     ========================================================= */
    private function notifyUsers(array $users, string $title, string $message, string $url, array $extra = []): void
    {
        foreach ($users as $u) {
            if ($u) {
                $u->notify(new CourrierStatusNotification($title, $message, $url, $extra));
            }
        }
    }

    /* =========================================================
     |  LISTE + EXPORTS
     ========================================================= */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Courrier::class);

        $user = $request->user();
        $isAdmin = $user->hasRole(['admin', 'super-admin']);

        $perPage = (int) $request->query('per_page', 20);
        if (! in_array($perPage, [10, 20, 50], true)) {
            $perPage = 20;
        }

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'type' => (string) $request->query('type', ''),
            'workflow_etape_id' => (string) $request->query('workflow_etape_id', ''),
            'bureau_actuel_id' => (string) $request->query('bureau_actuel_id', ''),
            'has_attachments' => (string) $request->query('has_attachments', ''),
            'assigned_to' => (string) $request->query('assigned_to', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'is_late' => (string) $request->query('is_late', ''),
            'archived' => (string) $request->query('archived', 'active'),
            'sort' => (string) $request->query('sort', 'recent'),
            'scope' => (string) $request->query('scope', $isAdmin ? 'all' : 'mine'),
            'per_page' => $perPage,
        ];

        if (! in_array($filters['archived'], ['active', 'archived', 'all'], true)) {
            $filters['archived'] = 'active';
        }
        if (! in_array($filters['sort'], ['recent', 'oldest', 'deadline', 'ref', 'updated'], true)) {
            $filters['sort'] = 'recent';
        }
        if (! $isAdmin) {
            $filters['scope'] = 'mine';
        }

        $courriers = $this->queryService
            ->buildFilteredQuery($request)
            ->paginate($perPage)
            ->withQueryString();

        $agentsList = User::query()
            ->when(! $isAdmin && $user->bureau_id, fn ($q) => $q->where('bureau_id', $user->bureau_id))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $etapes = WorkflowEtape::query()
            ->when(! $isAdmin && $user->bureau_id, fn ($q) => $q->where('bureau_id', $user->bureau_id))
            ->orderBy('ordre')
            ->get(['id', 'libelle', 'ordre', 'ui_color']);

        $bureaux = Bureau::query()
            ->when(! $isAdmin && $user->bureau_id, fn ($q) => $q->whereKey($user->bureau_id))
            ->orderBy('ordre')
            ->get(['id', 'nom', 'code', 'color']);
        $chips = $this->queryService->registerChipCounts($request);

        $activeFilterCount = collect($filters)
            ->except(['scope', 'sort', 'per_page', 'archived'])
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->count();
        if ($filters['archived'] !== 'active') {
            $activeFilterCount++;
        }

        return view('courriers.index', compact(
            'courriers',
            'filters',
            'etapes',
            'bureaux',
            'isAdmin',
            'agentsList',
            'chips',
            'activeFilterCount'
        ));
    }

    public function exportExcel(Request $request)
    {
        $this->authorize('export', Courrier::class);

        $rows = $this->queryService->buildFilteredQuery($request)->get();
        return Excel::download(
            new CourriersWorkbookExport($rows),
            'courriers_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('export', Courrier::class);

        $rows = $this->queryService->buildFilteredQuery($request)->get();

        $filtersHuman = [];

        if ($q = trim((string) $request->query('q'))) {
            $filtersHuman[] = __('courrier.filters.search') . ': ' . $q;
        }
        if ($type = $request->query('type')) {
            $filtersHuman[] = __('courrier.filters.type') . ': ' . strtoupper($type);
        }
        if ($etapeId = $request->query('workflow_etape_id')) {
            $etape = WorkflowEtape::find($etapeId);
            $filtersHuman[] = __('courrier.filters.etape') . ': ' . ($etape?->libelle ?? $etapeId);
        }
        if ($request->query('has_attachments') === 'yes') {
            $filtersHuman[] = __('courrier.filters.attachments') . ': ' . __('courrier.filters.attachments_yes');
        }
        if ($request->query('has_attachments') === 'no') {
            $filtersHuman[] = __('courrier.filters.attachments') . ': ' . __('courrier.filters.attachments_no');
        }
        if ($assigned = $request->query('assigned_to')) {
            $u = User::find($assigned);
            $filtersHuman[] = __('courrier.filters.assigned_to') . ': ' . ($u ? $u->name : 'ID ' . $assigned);
        }

        if ($request->query('date_from') || $request->query('date_to')) {
            $filtersHuman[] = __('courrier.export_period') . ': ' .
                ($request->query('date_from') ?: '—') . ' → ' .
                ($request->query('date_to') ?: '—');
        }

        $pdf = Pdf::loadView('exports.courriers_pdf', [
            'rows' => $rows,
            'filtersHuman' => $filtersHuman,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('courriers_' . now()->format('Ymd_His') . '.pdf');
    }

    /* =========================================================
     |  CRUD COURRIER + WORKFLOW
     ========================================================= */

    public function create(): View
    {
        $this->authorize('create', Courrier::class);

        $agents = User::orderBy('name')->get(['id', 'name', 'email']);
        return view('courriers.create', compact('agents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Courrier::class);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['entrant', 'sortant', 'interne'])],
            'objet' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'reference_externe' => ['nullable', 'string', 'max:100'],
            'tf' => ['nullable', 'string', 'max:100'],
            'expediteur' => ['nullable', 'string', 'max:255'],
            'destinataire' => ['nullable', 'string', 'max:255'],
            'proprietaire' => ['nullable', 'string', 'max:255'],
            'requerant' => ['nullable', 'string', 'max:255'],
            'vendeur' => ['nullable', 'string', 'max:255'],
            'etude_notariale' => ['nullable', 'string', 'max:255'],
            'valeur_immeuble' => ['nullable', 'numeric', 'min:0'],
            'numero_acte' => ['nullable', 'string', 'max:100'],
            'numero_repertoire' => ['nullable', 'string', 'max:100'],
            'date_courrier' => ['nullable', 'date'],
            'date_reception' => ['nullable', 'date'],
            'date_envoi' => ['nullable', 'date'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'document_file' => ['nullable', 'file', 'max:10240', 'extensions:pdf,jpg,jpeg,png'],
        ]);

        unset($validated['document_file']);

        $courrier = DB::transaction(function () use ($validated, $request) {
            $etapeReception = WorkflowEtape::query()
                ->with('bureau:id,nom,code')
                ->where('ordre', 1)
                ->first();

            if (! $etapeReception) {
                throw new RuntimeException(
                    'Étape « Réception » (ordre 1) introuvable. Exécutez WorkflowEtapesSeeder.'
                );
            }

            $dateLimite = $this->workflowService->calculerDateLimite($etapeReception);

            $year = now()->year;
            $seq = (Courrier::where('type', $validated['type'])->where('year', $year)->max('seq') ?? 0) + 1;
            $prefix = match ($validated['type']) {
                'sortant' => 'SOR',
                'interne' => 'INT',
                default => 'ENT',
            };

            $etapeLabel = $etapeReception->libelle;

            $courrier = Courrier::create([
                ...$validated,
                'year' => $year,
                'seq' => $seq,
                'reference_interne' => sprintf('GUTF-%s-%d-%06d', $prefix, $year, $seq),
                'created_by_user_id' => Auth::id(),
                'workflow_etape_id' => $etapeReception->id,
                'bureau_actuel_id' => $etapeReception->bureau_id,
                'date_limite' => $dateLimite,
                'is_late' => false,
            ]);

            // Première entrée d’historique : création + entrée circuit Kanban
            CourrierHistory::create([
                'courrier_id' => $courrier->id,
                'action' => 'created',
                'from_status' => null,
                'to_status' => $etapeLabel,
                'ancienne_etape_id' => null,
                'nouvelle_etape_id' => $etapeReception->id,
                'note_metier' => null,
                'action_type' => null,
                'actor_user_id' => Auth::id(),
                'metadata' => [
                    'reference_interne' => $courrier->reference_interne,
                    'tf' => $courrier->tf,
                    'bureau_id' => $etapeReception->bureau_id,
                    'bureau_nom' => $etapeReception->bureau?->nom,
                    'etape_libelle' => $etapeReception->libelle,
                    'etape_ordre' => $etapeReception->ordre,
                    'delai_jours' => $etapeReception->delai_jours,
                    'date_limite' => $dateLimite?->toIso8601String(),
                ],
                'created_at' => now(),
            ]);

            if ($courrier->assigned_to_user_id) {
                $assignee = User::find($courrier->assigned_to_user_id);
                $this->auditService->log($courrier, 'assigned', $etapeLabel, $etapeLabel, [
                    'assigned_to' => $assignee?->name,
                ]);
            }

            if ($file = $request->file('document_file')) {
                $this->storeUploadAttachment($courrier, $file);
            }

            return $courrier;
        });

        return redirect()->route('courriers.show', $courrier)->with('status', 'courrier-created');
    }

    public function show(Request $request, Courrier $courrier): View
    {
        $this->authorize('view', $courrier);

        $user = $request->user();

        $courrier->load([
            'assignedTo:id,name,email',
            'createdBy:id,name,email',
            'bureauActuel:id,nom,code,icon,color',
            'workflowEtape:id,libelle,ordre,ui_color,ui_icon,is_rejectable,bureau_id,delai_jours',
            'histories' => fn ($q) => $q->latest('id'),
            'histories.actor:id,name,email',
            'histories.ancienneEtape:id,libelle,ordre,ui_color',
            'histories.nouvelleEtape:id,libelle,ordre,ui_color',
            'attachments.uploadedBy:id,name,email',
            'comments' => fn ($q) => $q->oldest(),
            'comments.user:id,name,email,bureau_id,avatar_path',
            'comments.user.bureau:id,nom',
            'visas' => fn ($q) => $q->latest('id'),
            'linkedCourriers:id,reference_interne,objet,type,workflow_etape_id',
            'linkedCourriers.workflowEtape:id,libelle,ui_color',
            'parentCourrier:id,reference_interne,objet,type,workflow_etape_id',
            'parentCourrier.workflowEtape:id,libelle,ui_color',
        ]);

        $pendingVisa = $courrier->visas
            ->first(fn ($visa) => (int) $visa->validator_id === (int) $user?->id
                && $visa->status === ParapheurVisa::STATUS_PENDING);

        $hasPendingVisa = $courrier->visas
            ->contains(fn ($visa) => $visa->status === ParapheurVisa::STATUS_PENDING);

        $validatorRoleNames = \Spatie\Permission\Models\Role::query()
            ->whereIn('name', ['directeur', 'super-admin'])
            ->pluck('name')
            ->all();

        $parapheurValidators = $validatorRoleNames === []
            ? collect()
            : User::query()
                ->role($validatorRoleNames)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email']);

        $agents = User::query()
            ->when(
                ! $user?->hasRole(['admin', 'super-admin']) && $courrier->bureau_actuel_id,
                fn ($q) => $q->where('bureau_id', $courrier->bureau_actuel_id)
            )
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $nextEtape = null;
        $prevEtape = null;
        if ($courrier->workflowEtape) {
            $nextEtape = WorkflowEtape::query()
                ->where('ordre', (int) $courrier->workflowEtape->ordre + 1)
                ->first();
            $prevEtape = WorkflowEtape::query()
                ->where('ordre', (int) $courrier->workflowEtape->ordre - 1)
                ->first();
        }

        $requestedAttId = (int) $request->query('att', 0);
        $previewAttachment = $requestedAttId > 0
            ? $courrier->attachments->firstWhere('id', $requestedAttId)
            : null;
        $previewAttachment ??= $courrier->attachments
            ->first(fn ($a) => str_contains(strtolower((string) $a->mime), 'pdf')
                || str_ends_with(strtolower((string) $a->original_name), '.pdf'))
            ?? $courrier->attachments->first();

        $canActOnBureau = $user
            && $user->can('transfer', $courrier);

        return view('courriers.show', compact(
            'courrier',
            'agents',
            'nextEtape',
            'prevEtape',
            'previewAttachment',
            'canActOnBureau',
            'pendingVisa',
            'hasPendingVisa',
            'parapheurValidators'
        ));
    }

    public function submitToParapheur(Request $request, Courrier $courrier): RedirectResponse
    {
        $this->authorize('view', $courrier);

        if ($courrier->visas()->pending()->exists()) {
            return back()->withErrors([
                'validator_id' => __('parapheur.submit_already_pending'),
            ]);
        }

        $validated = $request->validate([
            'validator_id' => [
                'required',
                'integer',
                'exists:users,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $validator = User::query()->find($value);
                    if (! $validator || ! $validator->hasAnyRole(['directeur', 'super-admin'])) {
                        $fail(__('parapheur.submit_invalid_validator'));
                    }
                },
            ],
        ]);

        ParapheurVisa::query()->create([
            'courrier_id' => $courrier->id,
            'validator_id' => (int) $validated['validator_id'],
            'status' => ParapheurVisa::STATUS_PENDING,
        ]);

        return redirect()
            ->route('courriers.show', $courrier)
            ->with('status', __('parapheur.submitted'));
    }

    public function linkCourrier(Request $request, Courrier $courrier): RedirectResponse
    {
        $this->authorize('view', $courrier);

        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:100'],
        ]);

        $reference = trim($validated['reference']);
        $child = Courrier::query()
            ->where('reference_interne', $reference)
            ->first();

        if (! $child) {
            return back()
                ->withInput()
                ->withErrors(['reference' => __('courrier.links_not_found')]);
        }

        if ((int) $child->id === (int) $courrier->id) {
            return back()
                ->withInput()
                ->withErrors(['reference' => __('courrier.links_self')]);
        }

        $this->authorize('view', $child);

        $courrier->linkedCourriers()->syncWithoutDetaching([$child->id]);

        return redirect()
            ->route('courriers.show', $courrier)
            ->with('status', __('courrier.links_attached'));
    }

    public function printBordereau(Courrier $courrier): View
    {
        $this->authorize('view', $courrier);

        $courrier->loadMissing([
            'bureauActuel:id,nom',
            'workflowEtape:id,libelle',
            'assignedTo:id,name',
            'createdBy:id,name',
        ]);

        return view('courriers.bordereau', compact('courrier'));
    }

    public function assign(Request $request, Courrier $courrier): RedirectResponse
    {
        $this->authorize('assign', $courrier);

        $validated = $request->validate([
            'assigned_to_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $etapeLabel = $courrier->workflowEtape?->libelle;
        $courrier->update([
            'assigned_to_user_id' => $validated['assigned_to_user_id'],
        ]);

        $assignee = User::find($validated['assigned_to_user_id']);

        $this->auditService->log($courrier, 'assigned', $etapeLabel, $etapeLabel, [
            'assigned_to' => $assignee?->name,
        ]);

        if ($assignee && (int) $assignee->id !== (int) Auth::id()) {
            $this->notifyUsers(
                [$assignee],
                __('notifications.courrier_assigned_title'),
                __('notifications.courrier_assigned_msg', ['ref' => $courrier->reference_interne]),
                route('courriers.show', $courrier),
                ['type' => 'assigned', 'courrier_id' => $courrier->id]
            );
        }

        return back()->with('status', 'courrier-assigned');
    }

    public function startProcessing(Courrier $courrier): RedirectResponse
    {
        $this->authorize('process', $courrier);
        abort_unless($courrier->isAtOrdre(Courrier::ETAPE_ANALYSE), 422);

        $cible = WorkflowEtape::query()->where('ordre', Courrier::ETAPE_TRAITEMENT)->firstOrFail();
        $this->workflowService->transfererCourrier(
            $courrier,
            (int) $cible->id,
            null,
            Auth::user(),
            WorkflowService::ACTION_FORWARD
        );

        return back()->with('status', 'courrier-processing-started');
    }

    public function submitForValidation(Courrier $courrier): RedirectResponse
    {
        $this->authorize('process', $courrier);
        abort_unless($courrier->isAtOrdre(Courrier::ETAPE_TRAITEMENT), 422);

        $cible = WorkflowEtape::query()->where('ordre', Courrier::ETAPE_VALIDATION)->firstOrFail();
        $this->workflowService->transfererCourrier(
            $courrier,
            (int) $cible->id,
            null,
            Auth::user(),
            WorkflowService::ACTION_FORWARD
        );

        $validators = User::role('validateur')->get()
            ->reject(fn (User $u) => (int) $u->id === (int) Auth::id());

        $this->notifyUsers(
            $validators->all(),
            __('notifications.courrier_validation_title'),
            __('notifications.courrier_validation_msg', ['ref' => $courrier->reference_interne]),
            route('courriers.show', $courrier),
            ['type' => 'validation', 'courrier_id' => $courrier->id]
        );

        return back()->with('status', 'courrier-submitted');
    }

    public function validateCourrier(Courrier $courrier): RedirectResponse
    {
        $this->authorize('validate', $courrier);
        abort_unless($courrier->isEnValidation(), 422);

        $courrier->loadMissing(['assignedTo', 'createdBy']);

        $cible = WorkflowEtape::query()->where('ordre', Courrier::ETAPE_SORTIE)->firstOrFail();
        $this->workflowService->transfererCourrier(
            $courrier,
            (int) $cible->id,
            null,
            Auth::user(),
            WorkflowService::ACTION_FORWARD
        );

        $courrier->forceFill([
            'validated_by_user_id' => Auth::id(),
            'validated_at' => now(),
        ])->save();

        $recipients = collect([$courrier->assignedTo, $courrier->createdBy])
            ->filter()
            ->unique('id')
            ->reject(fn (User $u) => (int) $u->id === (int) Auth::id())
            ->values()
            ->all();

        $this->notifyUsers(
            $recipients,
            __('notifications.courrier_validated_title'),
            __('notifications.courrier_validated_msg', ['ref' => $courrier->reference_interne]),
            route('courriers.show', $courrier),
            ['type' => 'validated', 'courrier_id' => $courrier->id]
        );

        return back()->with('status', 'courrier-validated');
    }

    public function rejectCourrier(Courrier $courrier, Request $request): RedirectResponse
    {
        $this->authorize('validate', $courrier);
        abort_unless($courrier->isEnValidation(), 422);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $courrier->loadMissing(['assignedTo', 'createdBy']);

        $cible = WorkflowEtape::query()->where('ordre', Courrier::ETAPE_TRAITEMENT)->firstOrFail();
        $this->workflowService->transfererCourrier(
            $courrier,
            (int) $cible->id,
            $validated['reason'],
            Auth::user(),
            WorkflowService::ACTION_REJECT
        );

        $recipients = collect([$courrier->assignedTo, $courrier->createdBy])
            ->filter()
            ->unique('id')
            ->reject(fn (User $u) => (int) $u->id === (int) Auth::id())
            ->values()
            ->all();

        $this->notifyUsers(
            $recipients,
            __('notifications.courrier_rejected_title'),
            __('notifications.courrier_rejected_msg', [
                'ref' => $courrier->reference_interne,
                'reason' => $validated['reason'],
            ]),
            route('courriers.show', $courrier),
            ['type' => 'rejected', 'courrier_id' => $courrier->id]
        );

        return back()->with('status', 'courrier-rejected');
    }

    public function archive(Courrier $courrier): RedirectResponse
    {
        $this->authorize('archive', $courrier);
        abort_unless($courrier->isAtOrdre(Courrier::ETAPE_SORTIE), 422);

        $cible = WorkflowEtape::query()->where('ordre', Courrier::ETAPE_ARCHIVAGE)->firstOrFail();
        $this->workflowService->transfererCourrier(
            $courrier,
            (int) $cible->id,
            null,
            Auth::user(),
            WorkflowService::ACTION_FORWARD
        );

        return back()->with('status', 'courrier-archived');
    }

    /* =========================================================
     |  ATTACHMENTS (audités)
     ========================================================= */

    public function uploadAttachment(Request $request, Courrier $courrier): RedirectResponse
    {
        $this->authorize('attach', $courrier);

        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'extensions:pdf,jpg,jpeg,png'],
        ]);

        if ($file = $request->file('file')) {
            $this->storeUploadAttachment($courrier, $file);
        }

        return back()->with('status', 'attachment-uploaded');
    }

    public function previewAttachment(Request $request, Courrier $courrier, CourrierAttachment $attachment)
    {
        $this->authorize('downloadAttachment', $courrier);
        abort_unless((int) $attachment->courrier_id === (int) $courrier->id, 404);

        $fullPath = $attachment->absolutePath();
        abort_unless($fullPath, 404);

        $mime = $this->resolveAttachmentMime($attachment, $fullPath);
        $asciiName = $this->asciiDownloadName($attachment->original_name);

        // JSON embed: évite les gestionnaires de téléchargement (IDM, etc.) qui
        // interceptent Content-Type: application/pdf et cassent PDF.js.
        if ($request->boolean('embed')
            || $request->header('X-PDF-Embed') === '1'
            || $request->wantsJson()) {
            $bytes = is_readable($fullPath) ? file_get_contents($fullPath) : false;
            if ($bytes === false || $bytes === '') {
                $relative = $attachment->resolvedStoragePath();
                $bytes = $relative ? Storage::disk('local')->get($relative) : '';
            }
            abort_unless(is_string($bytes) && $bytes !== '', 404);

            return response()->json([
                'mime' => $mime,
                'name' => $attachment->original_name ?: $asciiName,
                'data' => base64_encode($bytes),
            ], 200, [
                'Cache-Control' => 'private, max-age=0, must-revalidate',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$asciiName.'"',
            'Content-Transfer-Encoding' => 'binary',
            'X-Content-Type-Options' => 'nosniff',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }

    public function downloadAttachment(Courrier $courrier, CourrierAttachment $attachment)
    {
        $this->authorize('downloadAttachment', $courrier);
        abort_unless((int) $attachment->courrier_id === (int) $courrier->id, 404);

        $fullPath = $attachment->absolutePath();
        abort_unless($fullPath, 404);

        $asciiName = $this->asciiDownloadName($attachment->original_name);

        return response()->download($fullPath, $attachment->original_name ?: $asciiName, [
            'Content-Type' => $this->resolveAttachmentMime($attachment, $fullPath),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function deleteAttachment(Courrier $courrier, CourrierAttachment $attachment): RedirectResponse
    {
        $this->authorize('detach', $courrier);
        abort_unless((int) $attachment->courrier_id === (int) $courrier->id, 404);

        $relative = $attachment->resolvedStoragePath();
        if ($relative) {
            Storage::disk('local')->delete($relative);
        }

        $attachment->delete();

        $etapeLabel = $courrier->workflowEtape?->libelle;

        $this->auditService->log($courrier, 'attachment_deleted', $etapeLabel, $etapeLabel, [
            'file' => $attachment->original_name,
        ]);

        return back()->with('status', 'attachment-deleted');
    }

    private function storeUploadAttachment(Courrier $courrier, \Illuminate\Http\UploadedFile $file): void
    {
        // Disk "local" root = storage/app/private → ne pas préfixer "private/"
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $storedName = now()->format('Ymd_His').'_'.Str::random(8).'.'.$extension;

        $path = $file->storeAs(
            "courriers/{$courrier->id}",
            $storedName,
            'local'
        );

        $mime = $file->getMimeType()
            ?: $file->getClientMimeType()
            ?: 'application/octet-stream';

        if (str_ends_with($extension, 'pdf')) {
            $mime = 'application/pdf';
        } elseif (in_array($extension, ['jpg', 'jpeg'], true)) {
            $mime = 'image/jpeg';
        } elseif ($extension === 'png') {
            $mime = 'image/png';
        }

        CourrierAttachment::create([
            'courrier_id' => $courrier->id,
            'uploaded_by_user_id' => Auth::id(),
            'source' => 'upload',
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => basename($path),
            'mime' => $mime,
            'size' => $file->getSize(),
            'path' => $path,
        ]);

        $etapeLabel = $courrier->workflowEtape?->libelle;

        $this->auditService->log($courrier, 'attachment_uploaded', $etapeLabel, $etapeLabel, [
            'file' => $file->getClientOriginalName(),
        ]);
    }

    private function resolveAttachmentMime(CourrierAttachment $attachment, string $fullPath): string
    {
        $mime = strtolower(trim((string) $attachment->mime));
        $name = strtolower((string) $attachment->original_name);

        if (str_ends_with($name, '.pdf') || str_contains($mime, 'pdf')) {
            return 'application/pdf';
        }
        if (str_ends_with($name, '.png') || $mime === 'image/png') {
            return 'image/png';
        }
        if (str_ends_with($name, '.jpg') || str_ends_with($name, '.jpeg') || str_contains($mime, 'jpeg')) {
            return 'image/jpeg';
        }
        if (str_starts_with($mime, 'image/')) {
            return $mime;
        }

        $detected = @mime_content_type($fullPath);
        if (is_string($detected) && $detected !== '' && $detected !== 'application/octet-stream') {
            return $detected;
        }

        return $mime !== '' ? $mime : 'application/octet-stream';
    }

    private function asciiDownloadName(?string $original): string
    {
        $name = basename((string) $original);
        $safe = preg_replace('/[^\w.\-]+/u', '_', $name) ?: 'document';

        return trim($safe, '._') ?: 'document';
    }
}