<?php

namespace App\Http\Controllers;

use App\Models\ConversationParticipant;
use App\Models\Courrier;
use App\Models\WorkflowEtape;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly StatisticsService $statistics
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $isAdmin = $user->hasRole(['admin', 'super-admin']);

        $query = Courrier::query();

        if (! $isAdmin) {
            $query->where(function ($sub) use ($user) {
                $sub->where('created_by_user_id', $user->id)
                    ->orWhere('assigned_to_user_id', $user->id);

                if ($user->can('courrier.validate')) {
                    $sub->orWhereHas(
                        'workflowEtape',
                        fn ($e) => $e->where('ordre', Courrier::ETAPE_VALIDATION)
                    );
                }
            });
        }

        $kpiIncoming = (clone $query)->where('type', 'entrant')->count();
        $kpiInProgress = (clone $query)->whereEtapeOrdreIn([
            Courrier::ETAPE_ANALYSE,
            Courrier::ETAPE_TRAITEMENT,
            Courrier::ETAPE_VALIDATION,
        ])->count();
        $kpiArchived = (clone $query)->where(function ($q) {
            $q->whereNotNull('archived_at')
                ->orWhereHas('workflowEtape', fn ($e) => $e->where('ordre', Courrier::ETAPE_ARCHIVAGE));
        })->count();
        $kpiLate = (clone $query)->where('is_late', true)->notArchived()->count();
        $kpiValidation = (clone $query)->whereEtapeOrdre(Courrier::ETAPE_VALIDATION)->count();
        $kpiThisMonth = (clone $query)
            ->whereBetween('created_at', [
                now()->startOfMonth()->toDateTimeString(),
                now()->endOfMonth()->toDateTimeString(),
            ])
            ->count();
        $kpiReception = (clone $query)->whereEtapeOrdre(Courrier::ETAPE_RECEPTION)->count();
        $kpiActive = (clone $query)->notArchived()->count();

        $etapes = WorkflowEtape::query()
            ->orderBy('ordre')
            ->get(['id', 'libelle', 'ordre', 'ui_color']);

        $countsByEtape = (clone $query)
            ->selectRaw('workflow_etape_id, COUNT(*) as total')
            ->groupBy('workflow_etape_id')
            ->pluck('total', 'workflow_etape_id');

        $byEtape = $etapes->map(function (WorkflowEtape $etape) use ($countsByEtape) {
            return (object) [
                'id' => $etape->id,
                'libelle' => $etape->libelle,
                'ordre' => $etape->ordre,
                'color' => $etape->ui_color ?: '#64748b',
                'total' => (int) ($countsByEtape[$etape->id] ?? 0),
            ];
        });

        $maxEtape = max(1, (int) $byEtape->max('total'));

        $lateCourriers = (clone $query)
            ->with(['workflowEtape:id,libelle,ordre,ui_color', 'bureauActuel:id,nom', 'assignedTo:id,name'])
            ->where('is_late', true)
            ->notArchived()
            ->orderBy('date_limite')
            ->limit(6)
            ->get();

        $recentCourriers = (clone $query)
            ->with(['assignedTo:id,name', 'workflowEtape:id,libelle,ordre,ui_color', 'bureauActuel:id,nom'])
            ->notArchived()
            ->latest('updated_at')
            ->limit(8)
            ->get();

        $unreadNotifications = $user->unreadNotifications()->limit(5)->get();
        $unreadNotificationsCount = $user->unreadNotifications()->count();

        $unreadChatCount = ConversationParticipant::where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('last_read_at')
                    ->orWhereRaw('last_read_at < (select updated_at from conversations where id = conversation_participants.conversation_id)');
            })
            ->count();

        $stats = $isAdmin || $user->hasRole('directeur')
            ? $this->statistics->dashboard()
            : null;

        $hour = (int) now()->format('G');
        $greetingKey = match (true) {
            $hour < 12 => 'app.greeting_morning',
            $hour < 18 => 'app.greeting_afternoon',
            default => 'app.greeting_evening',
        };

        return view('dashboard', [
            'user' => $user,
            'isAdmin' => $isAdmin,
            'greetingKey' => $greetingKey,
            'kpiIncoming' => $kpiIncoming,
            'kpiInProgress' => $kpiInProgress,
            'kpiArchived' => $kpiArchived,
            'kpiLate' => $kpiLate,
            'kpiValidation' => $kpiValidation,
            'kpiThisMonth' => $kpiThisMonth,
            'kpiReception' => $kpiReception,
            'kpiActive' => $kpiActive,
            'byEtape' => $byEtape,
            'maxEtape' => $maxEtape,
            'lateCourriers' => $lateCourriers,
            'recentCourriers' => $recentCourriers,
            'unreadNotifications' => $unreadNotifications,
            'unreadNotificationsCount' => $unreadNotificationsCount,
            'unreadChatCount' => $unreadChatCount,
            'monthlyReceived' => $stats['monthlyReceived'] ?? null,
            'byBureau' => $stats['byBureau'] ?? null,
            'treatedThisMonth' => $stats['treatedThisMonth'] ?? null,
        ]);
    }
}
