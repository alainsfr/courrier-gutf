<?php

namespace App\Http\Controllers;

use App\Exports\CourriersWorkbookExport;
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

        $isAdmin = $request->user()->hasRole('admin');

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'type' => (string) $request->query('type', ''),
            'workflow_etape_id' => (string) $request->query('workflow_etape_id', ''),
            'has_attachments' => (string) $request->query('has_attachments', ''),
            'assigned_to' => (string) $request->query('assigned_to', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'scope' => (string) $request->query('scope', $isAdmin ? 'all' : 'mine'),
        ];

        $courriers = $this->queryService
            ->buildFilteredQuery($request)
            ->paginate(15)
            ->withQueryString();

        $agentsList = User::orderBy('name')->get(['id', 'name', 'email']);
        $etapes = WorkflowEtape::query()->orderBy('ordre')->get(['id', 'libelle', 'ordre']);

        return view('courriers.index', compact(
            'courriers',
            'filters',
            'etapes',
            'isAdmin',
            'agentsList'
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
            'document_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
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

    public function show(Courrier $courrier): View
    {
        $this->authorize('view', $courrier);

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
        ]);

        $agents = User::orderBy('name')->get(['id', 'name', 'email']);

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

        $previewAttachment = $courrier->attachments
            ->first(fn ($a) => str_contains(strtolower((string) $a->mime), 'pdf'))
            ?? $courrier->attachments->first();

        $user = Auth::user();
        $canActOnBureau = $user
            && $user->bureau_id
            && $courrier->bureau_actuel_id
            && (int) $user->bureau_id === (int) $courrier->bureau_actuel_id;

        return view('courriers.show', compact(
            'courrier',
            'agents',
            'nextEtape',
            'prevEtape',
            'previewAttachment',
            'canActOnBureau'
        ));
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

        if ($assignee) {
            $this->notifyUsers(
                [$assignee],
                __('notifications.courrier_assigned_title'),
                __('notifications.courrier_assigned_msg', ['ref' => $courrier->reference_interne]),
                route('courriers.show', $courrier)
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

        $validators = User::role('validateur')->get();
        $this->notifyUsers(
            $validators->all(),
            __('notifications.courrier_validation_title'),
            __('notifications.courrier_validation_msg', ['ref' => $courrier->reference_interne]),
            route('courriers.show', $courrier)
        );

        return back()->with('status', 'courrier-submitted');
    }

    public function validateCourrier(Courrier $courrier): RedirectResponse
    {
        $this->authorize('validate', $courrier);
        abort_unless($courrier->isEnValidation(), 422);

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

        return back()->with('status', 'courrier-validated');
    }

    public function rejectCourrier(Courrier $courrier, Request $request): RedirectResponse
    {
        $this->authorize('validate', $courrier);
        abort_unless($courrier->isEnValidation(), 422);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $cible = WorkflowEtape::query()->where('ordre', Courrier::ETAPE_TRAITEMENT)->firstOrFail();
        $this->workflowService->transfererCourrier(
            $courrier,
            (int) $cible->id,
            $validated['reason'],
            Auth::user(),
            WorkflowService::ACTION_REJECT
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
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        if ($file = $request->file('file')) {
            $this->storeUploadAttachment($courrier, $file);
        }

        return back()->with('status', 'attachment-uploaded');
    }

    public function previewAttachment(Courrier $courrier, CourrierAttachment $attachment)
    {
        $this->authorize('downloadAttachment', $courrier);
        abort_unless($attachment->courrier_id === $courrier->id, 404);

        $path = $attachment->path;
        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $safeName = str_replace(['"', "\r", "\n"], '', $attachment->original_name);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $attachment->mime,
            'Content-Disposition' => 'inline; filename="'.$safeName.'"',
        ]);
    }

    public function downloadAttachment(Courrier $courrier, CourrierAttachment $attachment)
    {
        $this->authorize('downloadAttachment', $courrier);
        abort_unless($attachment->courrier_id === $courrier->id, 404);

        $path = $attachment->path;
        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $safeName = str_replace(['"', "\r", "\n"], '', $attachment->original_name);

        return response()->download(Storage::disk('local')->path($path), $safeName);
    }

    public function deleteAttachment(Courrier $courrier, CourrierAttachment $attachment): RedirectResponse
    {
        $this->authorize('detach', $courrier);
        abort_unless($attachment->courrier_id === $courrier->id, 404);

        $path = $attachment->path;
        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
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
        $path = $file->storeAs(
            "courriers/{$courrier->id}",
            now()->format('Ymd_His') . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension(),
            'local'
        );

        CourrierAttachment::create([
            'courrier_id' => $courrier->id,
            'uploaded_by_user_id' => Auth::id(),
            'source' => 'upload',
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => basename($path),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'path' => $path,
        ]);

        $etapeLabel = $courrier->workflowEtape?->libelle;

        $this->auditService->log($courrier, 'attachment_uploaded', $etapeLabel, $etapeLabel, [
            'file' => $file->getClientOriginalName(),
        ]);
    }
}