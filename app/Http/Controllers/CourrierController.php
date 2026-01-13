<?php

namespace App\Http\Controllers;

use App\Exports\CourriersExport;
use App\Models\Courrier;
use App\Models\CourrierAttachment;
use App\Models\CourrierHistory;
use App\Models\User;
use App\Services\CourrierQueryService;
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
    public function __construct(private readonly CourrierQueryService $queryService)
    {
    }

    public function index(Request $request): View
    {
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

        return view('courriers.index', compact('courriers', 'filters', 'allowedStatuses', 'isAdmin', 'agentsList'));
    }

    public function exportExcel(Request $request)
    {
        $rows = $this->queryService->buildFilteredQuery($request)->get();
        $fileName = 'courriers_' . now()->format('Ymd_His') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\CourriersWorkbookExport($rows), $fileName);
    }

    public function exportPdf(Request $request)
    {
        $rows = $this->queryService->buildFilteredQuery($request)->get();

        // Filtres affichés lisiblement dans le PDF
        $filtersHuman = [];

        $q = trim((string) $request->query('q', ''));
        if ($q !== '') $filtersHuman[] = __('courrier.filters.search') . ': ' . $q;

        $type = (string) $request->query('type', '');
        if ($type !== '') $filtersHuman[] = __('courrier.filters.type') . ': ' . strtoupper($type);

        $status = (string) $request->query('status', '');
        if ($status !== '') $filtersHuman[] = __('courrier.filters.status') . ': ' . __('courrier.status_label.' . $status);

        $has = (string) $request->query('has_attachments', '');
        if ($has === 'yes') $filtersHuman[] = __('courrier.filters.attachments') . ': ' . __('courrier.filters.attachments_yes');
        if ($has === 'no')  $filtersHuman[] = __('courrier.filters.attachments') . ': ' . __('courrier.filters.attachments_no');

        $assigned = (string) $request->query('assigned_to', '');
        if ($assigned !== '' && ctype_digit($assigned)) {
            $u = User::find((int) $assigned);
            $filtersHuman[] = __('courrier.filters.assigned_to') . ': ' . ($u ? ($u->name . ' <' . $u->email . '>') : ('ID ' . $assigned));
        }

        $df = (string) $request->query('date_from', '');
        $dt = (string) $request->query('date_to', '');
        if ($df !== '' || $dt !== '') {
            $filtersHuman[] = __('courrier.export_period') . ': ' . ($df ?: '—') . ' → ' . ($dt ?: '—');
        }

        $scope = (string) $request->query('scope', '');
        if ($scope === 'mine') $filtersHuman[] = __('courrier.filters.scope') . ': ' . __('courrier.filters.scope_mine');
        if ($scope === 'all')  $filtersHuman[] = __('courrier.filters.scope') . ': ' . __('courrier.filters.scope_all');

        $pdf = Pdf::loadView('exports.courriers_pdf', [
            'rows' => $rows,
            'filtersHuman' => $filtersHuman,
        ])->setPaper('a4', 'landscape');

        $fileName = 'courriers_' . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($fileName);
    }

    // ----------------- CRUD courrier (inchangé) -----------------

    public function create(): View
    {
        $agents = User::orderBy('name')->get(['id', 'name', 'email']);
        return view('courriers.create', compact('agents'));
    }

    public function store(Request $request): RedirectResponse
    {
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

        $hasUpload = $request->hasFile('document_file');

        if ($hasUpload && ! $request->user()->can('courrier.attach')) {
            return back()->withErrors(['document_file' => __('courrier.no_attach_permission')])->withInput();
        }

        $courrier = DB::transaction(function () use ($validated, $request, $hasUpload) {
            $year = (int) now()->format('Y');

            $maxSeq = Courrier::where('type', $validated['type'])
                ->where('year', $year)
                ->lockForUpdate()
                ->max('seq');

            $seq = (int) ($maxSeq ?? 0) + 1;

            $prefix = $validated['type'] === 'entrant' ? 'ENT' : 'SOR';
            $referenceInterne = sprintf('GUTF-%s-%d-%06d', $prefix, $year, $seq);

            $status = empty($validated['assigned_to_user_id']) ? 'recu' : 'affecte';

            $courrier = Courrier::create([
                'type' => $validated['type'],
                'year' => $year,
                'seq' => $seq,
                'reference_interne' => $referenceInterne,
                'reference_externe' => $validated['reference_externe'] ?? null,
                'objet' => $validated['objet'],
                'description' => $validated['description'] ?? null,
                'expediteur' => $validated['expediteur'] ?? null,
                'destinataire' => $validated['destinataire'] ?? null,
                'date_courrier' => $validated['date_courrier'] ?? null,
                'date_reception' => $validated['date_reception'] ?? null,
                'date_envoi' => $validated['date_envoi'] ?? null,
                'status' => $status,
                'created_by_user_id' => Auth::id(),
                'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
            ]);

            $this->logHistory($courrier, 'created', null, $courrier->status, [
                'reference_interne' => $courrier->reference_interne,
            ]);

            if ($courrier->assigned_to_user_id) {
                $assignee = User::find($courrier->assigned_to_user_id);
                $this->logHistory($courrier, 'assigned', 'recu', 'affecte', [
                    'assigned_to' => $assignee ? ($assignee->name . ' <' . $assignee->email . '>') : ('ID ' . $courrier->assigned_to_user_id),
                ]);
            }

            if ($hasUpload && ($file = $request->file('document_file'))) {
                $this->storeUploadAttachment($courrier, $file);
            }

            return $courrier;
        });

        return redirect()->route('courriers.show', $courrier)->with('status', 'courrier-created');
    }

    public function show(Courrier $courrier, Request $request): View
    {
        if (! $request->user()->hasRole('admin')) {
            abort_unless(
                $courrier->created_by_user_id === $request->user()->id
                || $courrier->assigned_to_user_id === $request->user()->id,
                403
            );
        }

        $courrier->load([
            'createdBy:id,name,email',
            'assignedTo:id,name,email',
            'histories.actor:id,name',
            'attachments.uploadedBy:id,name',
        ]);

        $agents = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('courriers.show', compact('courrier', 'agents'));
    }

    public function assign(Courrier $courrier, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_to_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $from = $courrier->status;
        $courrier->assigned_to_user_id = (int) $validated['assigned_to_user_id'];

        if ($courrier->status === 'recu') {
            $courrier->status = 'affecte';
        }

        $courrier->save();

        $assignee = User::find($courrier->assigned_to_user_id);

        $this->logHistory($courrier, 'assigned', $from, $courrier->status, [
            'assigned_to' => $assignee ? ($assignee->name . ' <' . $assignee->email . '>') : ('ID ' . $courrier->assigned_to_user_id),
        ]);

        return back()->with('status', 'courrier-assigned');
    }

    public function submitForValidation(Courrier $courrier): RedirectResponse
    {
        $from = $courrier->status;
        abort_unless(in_array($from, ['affecte', 'en_traitement'], true), 422);

        $courrier->status = 'en_validation';
        $courrier->save();

        $this->logHistory($courrier, 'submitted_for_validation', $from, 'en_validation');

        return back()->with('status', 'courrier-submitted');
    }

    public function validateCourrier(Courrier $courrier): RedirectResponse
    {
        $from = $courrier->status;
        abort_unless($from === 'en_validation', 422);

        $courrier->status = 'valide';
        $courrier->validated_by_user_id = Auth::id();
        $courrier->validated_at = now();
        $courrier->save();

        $validator = User::find((int) $courrier->validated_by_user_id);

        $this->logHistory($courrier, 'validated', $from, 'valide', [
            'validated_by' => $validator ? ($validator->name . ' <' . $validator->email . '>') : ('ID ' . (string) $courrier->validated_by_user_id),
        ]);

        return back()->with('status', 'courrier-validated');
    }

    public function rejectCourrier(Courrier $courrier, Request $request): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $from = $courrier->status;
        abort_unless($from === 'en_validation', 422);

        $courrier->status = 'rejete';
        $courrier->save();

        $this->logHistory($courrier, 'rejected', $from, 'rejete', ['reason' => $validated['reason']]);

        return back()->with('status', 'courrier-rejected');
    }

    public function archive(Courrier $courrier): RedirectResponse
    {
        $from = $courrier->status;
        abort_unless(in_array($from, ['valide', 'traite', 'rejete'], true), 422);

        $courrier->status = 'archive';
        $courrier->archived_at = now();
        $courrier->save();

        $this->logHistory($courrier, 'archived', $from, 'archive');

        return back()->with('status', 'courrier-archived');
    }

    // ----- Attachments -----

    public function uploadAttachment(Courrier $courrier, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        $this->storeUploadAttachment($courrier, $validated['file']);

        return back()->with('status', 'attachment-uploaded');
    }

    public function previewAttachment(Courrier $courrier, CourrierAttachment $attachment, Request $request)
    {
        abort_unless($attachment->courrier_id === $courrier->id, 404);

        if (! $request->user()->hasRole('admin')) {
            abort_unless(
                $courrier->created_by_user_id === $request->user()->id
                || $courrier->assigned_to_user_id === $request->user()->id,
                403
            );
        }

        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        $fullPath = Storage::disk('local')->path($attachment->path);
        $mime = $attachment->mime ?: 'application/octet-stream';
        $name = $attachment->original_name ?: basename($fullPath);

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . addslashes($name) . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function downloadAttachment(Courrier $courrier, CourrierAttachment $attachment, Request $request)
    {
        abort_unless($attachment->courrier_id === $courrier->id, 404);

        if (! $request->user()->hasRole('admin')) {
            abort_unless(
                $courrier->created_by_user_id === $request->user()->id
                || $courrier->assigned_to_user_id === $request->user()->id,
                403
            );
        }

        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    public function deleteAttachment(Courrier $courrier, CourrierAttachment $attachment, Request $request): RedirectResponse
    {
        abort_unless($attachment->courrier_id === $courrier->id, 404);

        if (! $request->user()->hasRole('admin')) {
            abort_unless(
                $courrier->created_by_user_id === $request->user()->id
                || $courrier->assigned_to_user_id === $request->user()->id,
                403
            );
        }

        $original = $attachment->original_name;
        $path = $attachment->path;

        DB::transaction(function () use ($attachment, $courrier, $original, $path) {
            if (Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }

            $attachment->delete();

            $this->logHistory($courrier, 'attachment_deleted', $courrier->status, $courrier->status, [
                'original_name' => $original,
                'path' => $path,
            ]);
        });

        return back()->with('status', 'attachment-deleted');
    }

    private function storeUploadAttachment(Courrier $courrier, \Illuminate\Http\UploadedFile $file): void
    {
        $dir = "private/courriers/{$courrier->id}";
        $storedName = now()->format('Ymd_His') . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($dir, $storedName, 'local');

        CourrierAttachment::create([
            'courrier_id' => $courrier->id,
            'uploaded_by_user_id' => Auth::id(),
            'source' => 'upload',
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize() ?: 0,
            'path' => $path,
        ]);

        $this->logHistory($courrier, 'attachment_uploaded', $courrier->status, $courrier->status, [
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
        ]);
    }

    private function logHistory(Courrier $courrier, string $action, ?string $from, ?string $to, array $metadata = []): void
    {
        CourrierHistory::create([
            'courrier_id' => $courrier->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'actor_user_id' => Auth::id(),
            'metadata' => $metadata ?: null,
            'created_at' => now(),
        ]);
    }
}
