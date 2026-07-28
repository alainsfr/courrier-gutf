<?php

namespace App\Http\Controllers;

use App\Models\Bureau;
use App\Models\Courrier;
use App\Models\WorkflowEtape;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WorkflowController extends Controller
{
    public function __construct(
        private readonly WorkflowService $workflowService
    ) {}

    /**
     * Tableau Kanban premium : colonnes = étapes, cartes = courriers du bureau agent.
     */
    public function kanban(Request $request): View
    {
        $this->authorize('viewAny', Courrier::class);

        $user = $request->user();
        $isAdmin = $user->hasRole('admin');

        $etapes = WorkflowEtape::query()
            ->with('bureau:id,nom,code,icon,color')
            ->orderBy('ordre')
            ->get();

        $bureauId = null;
        if ($isAdmin && $request->filled('bureau_id')) {
            $bureauId = (int) $request->query('bureau_id');
        } elseif ($user->bureau_id) {
            $bureauId = (int) $user->bureau_id;
        }

        $query = Courrier::query()
            ->with([
                'assignedTo:id,name',
                'createdBy:id,name',
                'workflowEtape:id,libelle,ordre,ui_color,ui_icon',
                'bureauActuel:id,nom,code,color,icon',
            ])
            ->whereNotNull('workflow_etape_id')
            ->where('status', '!=', 'archive');

        if ($bureauId) {
            $query->where('bureau_actuel_id', $bureauId);
        } elseif (! $isAdmin) {
            $query->where(function ($sub) use ($user) {
                $sub->where('assigned_to_user_id', $user->id)
                    ->orWhere('created_by_user_id', $user->id);
            });
        }

        $courriersByEtape = $query
            ->latest('id')
            ->get()
            ->groupBy('workflow_etape_id');

        $bureau = $bureauId
            ? Bureau::query()->find($bureauId)
            : $user->bureau;

        $bureaux = $isAdmin
            ? Bureau::query()->where('is_active', true)->orderBy('ordre')->get(['id', 'nom', 'code', 'color'])
            : collect();

        return view('courriers.kanban', [
            'etapes' => $etapes,
            'courriersByEtape' => $courriersByEtape,
            'bureau' => $bureau,
            'bureaux' => $bureaux,
            'isAdmin' => $isAdmin,
            'transferUrlTemplate' => url('/courriers/__ID__/transfer'),
        ]);
    }

    /**
     * Endpoint AJAX SortableJS → WorkflowService.
     */
    public function transfer(Request $request, Courrier $courrier): JsonResponse
    {
        $this->authorize('view', $courrier);

        $validated = $request->validate([
            'nouvelle_etape_id' => ['required', 'integer', 'exists:workflow_etapes,id'],
            'note_metier' => ['nullable', 'string', 'max:2000'],
            'action_type' => ['nullable', 'in:forward,backward,reject'],
        ]);

        $nouvelleEtape = WorkflowEtape::query()->findOrFail((int) $validated['nouvelle_etape_id']);
        $actuelle = $courrier->workflowEtape;

        $actionType = $validated['action_type'] ?? $this->resolveActionType($actuelle, $nouvelleEtape);

        try {
            $history = $this->workflowService->transfererCourrier(
                $courrier,
                (int) $nouvelleEtape->id,
                $validated['note_metier'] ?? null,
                $request->user(),
                $actionType
            );
        } catch (ValidationException $e) {
            return response()->json([
                'ok' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Transition invalide.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage() ?: 'Échec du transfert.',
            ], 422);
        }

        $courrier->refresh()->load(['workflowEtape', 'bureauActuel']);

        return response()->json([
            'ok' => true,
            'message' => 'Courrier transféré.',
            'action_type' => $actionType,
            'history_id' => $history->id,
            'courrier' => [
                'id' => $courrier->id,
                'status' => $courrier->status,
                'workflow_etape_id' => $courrier->workflow_etape_id,
                'bureau_actuel_id' => $courrier->bureau_actuel_id,
                'etape' => $courrier->workflowEtape?->only(['id', 'libelle', 'ordre', 'ui_color', 'ui_icon']),
                'bureau' => $courrier->bureauActuel?->only(['id', 'nom', 'code', 'color', 'icon']),
            ],
        ]);
    }

    private function resolveActionType(?WorkflowEtape $from, WorkflowEtape $to): string
    {
        if ($from === null) {
            return WorkflowService::ACTION_FORWARD;
        }

        $delta = (int) $to->ordre - (int) $from->ordre;

        if ($delta === 1) {
            return WorkflowService::ACTION_FORWARD;
        }

        if ($delta === -1) {
            return WorkflowService::ACTION_BACKWARD;
        }

        if ($delta < -1) {
            return WorkflowService::ACTION_REJECT;
        }

        throw ValidationException::withMessages([
            'nouvelle_etape_id' => 'Seuls les déplacements séquentiels (ou un rejet vers une étape antérieure) sont autorisés.',
        ]);
    }
}
