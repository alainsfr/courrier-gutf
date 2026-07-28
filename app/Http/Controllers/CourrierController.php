<?php

namespace App\Http\Controllers;

use App\Exports\CourriersWorkbookExport;
use App\Models\Courrier;
use App\Models\CourrierAttachment;
use App\Models\User;
use App\Services\CourrierQueryService;
use App\Services\AuditService;
use App\Notifications\CourrierStatusNotification;
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
        private readonly AuditService $auditService
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

        $allowedStatuses = ['recu', 'affecte', 'en_traitement', 'en_validation', 'valide', 'rejete', 'archive'];

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'type' => (string) $request->query('type', ''),
            'status' => (string) $request->query('status', ''),
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

        return view('courriers.index', compact(
            'courriers',
            'filters',
            'allowedStatuses',
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
        if ($status = $request->query('status')) {
            $filtersHuman[] = __('courrier.filters.status') . ': ' . __('courrier.status_label.' . $status);
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
            'type' => ['required', Rule::in(['entrant', 'sortant'])],
            'objet' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'reference_externe' => ['nullable', 'string', 'max:100'],
            'expediteur' => ['nullable', 'string', 'max:255'],
            'destinataire' => ['nullable', 'string', 'max:255'],
            'date_courrier' => ['nullable', 'date'],
            'date_reception' => ['nullable', 'date'],
            'date_envoi' => ['nullable', 'date'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'document_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        $courrier = DB::transaction(function () use ($validated, $request) {

            $year = now()->year;
            $seq = (Courrier::where('type', $validated['type'])->where('year', $year)->max('seq') ?? 0) + 1;
            $prefix = $validated['type'] === 'entrant' ? 'ENT' : 'SOR';

            $courrier = Courrier::create([
                ...$validated,
                'year' => $year,
                'seq' => $seq,
                'reference_interne' => sprintf('GUTF-%s-%d-%06d', $prefix, $year, $seq),
                'status' => empty($validated['assigned_to_user_id']) ? 'recu' : 'affecte',
                'created_by_user_id' => Auth::id(),
            ]);

            $this->auditService->log($courrier, 'created', null, $courrier->status, [
                'reference_interne' => $courrier->reference_interne,
            ]);

            if ($courrier->assigned_to_user_id) {
                $assignee = User::find($courrier->assigned_to_user_id);
                $this->auditService->log($courrier, 'assigned', 'recu', 'affecte', [
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

        // Chargement des relations nécessaires pour l'affichage de l'historique et des PJ
        $courrier->load([
            'assignedTo:id,name,email',
            'createdBy:id,name,email',
            'histories.actor:id,name,email',
            'attachments.uploadedBy:id,name,email'
        ]);

        $agents = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('courriers.show', compact('courrier', 'agents'));
    }

    public function assign(Request $request, Courrier $courrier): RedirectResponse
    {
        $this->authorize('assign', $courrier);

        $validated = $request->validate([
            'assigned_to_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $from = $courrier->status;
        $courrier->update([
            'assigned_to_user_id' => $validated['assigned_to_user_id'],
            'status' => 'affecte',
        ]);

        $assignee = User::find($validated['assigned_to_user_id']);

        $this->auditService->log($courrier, 'assigned', $from, 'affecte', [
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
        abort_unless($courrier->status === 'affecte', 422);

        $from = $courrier->status;
        $courrier->update(['status' => 'en_traitement']);

        $this->auditService->log($courrier, 'processing_started', $from, 'en_traitement');

        return back()->with('status', 'courrier-processing-started');
    }

    public function submitForValidation(Courrier $courrier): RedirectResponse
    {
        $this->authorize('process', $courrier);
        abort_unless(in_array($courrier->status, ['affecte', 'en_traitement']), 422);

        $from = $courrier->status;
        $courrier->update(['status' => 'en_validation']);

        $this->auditService->log($courrier, 'submitted_for_validation', $from, 'en_validation');

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
        abort_unless($courrier->status === 'en_validation', 422);

        $from = $courrier->status;
        $courrier->update([
            'status' => 'valide',
            'validated_by_user_id' => Auth::id(),
            'validated_at' => now(),
        ]);

        $this->auditService->log($courrier, 'validated', $from, 'valide');

        return back()->with('status', 'courrier-validated');
    }

    public function rejectCourrier(Courrier $courrier, Request $request): RedirectResponse
    {
        $this->authorize('validate', $courrier);
        abort_unless($courrier->status === 'en_validation', 422);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $from = $courrier->status;

        $courrier->update(['status' => 'rejete']);

        $this->auditService->log($courrier, 'rejected', $from, 'rejete', $validated);

        return back()->with('status', 'courrier-rejected');
    }

    public function archive(Courrier $courrier): RedirectResponse
    {
        $this->authorize('archive', $courrier);
        abort_unless(in_array($courrier->status, ['valide', 'rejete']), 422);

        $from = $courrier->status;
        $courrier->update(['status' => 'archive', 'archived_at' => now()]);

        $this->auditService->log($courrier, 'archived', $from, 'archive');

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

        $this->auditService->log($courrier, 'attachment_deleted', $courrier->status, $courrier->status, [
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

        $this->auditService->log($courrier, 'attachment_uploaded', $courrier->status, $courrier->status, [
            'file' => $file->getClientOriginalName(),
        ]);
    }
}