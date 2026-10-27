<?php

namespace App\Services;

use App\Events\CourrierTransfere;
use App\Models\Courrier;
use App\Models\CourrierHistory;
use App\Models\User;
use App\Models\WorkflowEtape;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class WorkflowService
{
    public const ACTION_FORWARD = 'forward';
    public const ACTION_BACKWARD = 'backward';
    public const ACTION_REJECT = 'reject';

    public const DIRECT_ORDERS = [2, 3, 5, 6];

    public function nextOrder(int $order): ?int
    {
        return match ($order) {
            1 => 2,
            2 => 3,
            3, 4 => 5,
            5 => 6,
            default => null,
        };
    }

    public function previousOrder(int $order): ?int
    {
        return match ($order) {
            3 => 2,
            4, 5 => 3,
            default => null,
        };
    }

    public function transitionAbility(int $fromOrder, int $toOrder): string
    {
        return match ($fromOrder) {
            1 => 'assign',
            4 => 'validate',
            5 => $toOrder === 6 ? 'archive' : 'process',
            default => 'process',
        };
    }

    /**
     * Fait avancer le traitement sans changer le bureau responsable.
     *
     * @param  string  $actionType  forward|backward|reject
     */
    public function transfererCourrier(
        Courrier $courrier,
        int $nouvelleEtapeId,
        ?string $note,
        User $user,
        string $actionType = self::ACTION_FORWARD
    ): CourrierHistory {
        $actionType = strtolower(trim($actionType));

        if (! in_array($actionType, [self::ACTION_FORWARD, self::ACTION_BACKWARD, self::ACTION_REJECT], true)) {
            throw new InvalidArgumentException("action_type invalide : {$actionType}");
        }

        if ($actionType === self::ACTION_REJECT && blank($note)) {
            throw ValidationException::withMessages([
                'note_metier' => 'Une note métier est obligatoire pour un rejet.',
            ]);
        }

        $result = DB::transaction(function () use ($courrier, $nouvelleEtapeId, $note, $user, $actionType) {
            /** @var Courrier $courrier */
            $courrier = Courrier::query()
                ->lockForUpdate()
                ->with(['workflowEtape'])
                ->findOrFail($courrier->id);

            $nouvelleEtape = WorkflowEtape::query()
                ->with('bureau')
                ->find($nouvelleEtapeId);

            if (! $nouvelleEtape) {
                throw new InvalidArgumentException("Étape de workflow #{$nouvelleEtapeId} introuvable.");
            }

            $ancienneEtape = $courrier->workflowEtape;

            // A stage changes the progress of the dossier, never its responsible office.
            Gate::forUser($user)->authorize('transfer', $courrier);
            $ability = $this->transitionAbility((int) $ancienneEtape?->ordre, (int) $nouvelleEtape->ordre);
            Gate::forUser($user)->authorize($ability, $courrier);

            $this->assertActionValide($ancienneEtape, $nouvelleEtape, $actionType);

            if ((int) $ancienneEtape?->ordre === 2 && (int) $nouvelleEtape->ordre === 3
                && ! $courrier->assigned_to_user_id && $user->bureau_id
                && (int) $user->bureau_id === (int) $courrier->bureau_actuel_id) {
                app(CourrierManagementService::class)->claim($courrier, $user);
                $courrier->refresh();
            }

            $fromLabel = $ancienneEtape?->libelle;
            $toLabel = $actionType === self::ACTION_REJECT
                ? ($nouvelleEtape->libelle.' (rejet)')
                : $nouvelleEtape->libelle;

            $dateLimite = $this->calculerDateLimite($nouvelleEtape);

            $courrier->forceFill([
                'workflow_etape_id' => $nouvelleEtape->id,
                'date_limite' => $dateLimite,
                'is_late' => false,
                'archived_at' => (int) $nouvelleEtape->ordre === 6 ? now() : null,
                'validated_by_user_id' => (int) $nouvelleEtape->ordre >= 5 ? ($courrier->validated_by_user_id ?? $user->id) : null,
                'validated_at' => (int) $nouvelleEtape->ordre >= 5 ? ($courrier->validated_at ?? now()) : null,
            ])->save();

            $history = CourrierHistory::create([
                'courrier_id' => $courrier->id,
                'action' => 'workflow_transfer',
                'from_status' => $fromLabel,
                'to_status' => $toLabel,
                'ancienne_etape_id' => $ancienneEtape?->id,
                'nouvelle_etape_id' => $nouvelleEtape->id,
                'note_metier' => $note,
                'action_type' => $actionType,
                'actor_user_id' => $user->id,
                'metadata' => [
                    'bureau_id' => $courrier->bureau_actuel_id,
                    'bureau_nom' => $courrier->bureauActuel?->nom,
                    'etape_libelle' => $nouvelleEtape->libelle,
                    'etape_ordre' => $nouvelleEtape->ordre,
                    'ui_color' => $nouvelleEtape->ui_color,
                    'ui_icon' => $nouvelleEtape->ui_icon,
                    'delai_jours' => $nouvelleEtape->delai_jours,
                    'date_limite' => $dateLimite?->toIso8601String(),
                ],
                'created_at' => now(),
            ]);

            $courrier->load(['bureauActuel:id,nom,code,icon,color']);

            return [
                'courrier' => $courrier->fresh(['bureauActuel', 'workflowEtape.bureau']),
                'history' => $history,
                'ancienneEtape' => $ancienneEtape,
                'nouvelleEtape' => $nouvelleEtape,
            ];
        });

        event(new CourrierTransfere(
            courrier: $result['courrier'],
            history: $result['history'],
            acteur: $user,
            actionType: $actionType,
            ancienneEtape: $result['ancienneEtape'],
            nouvelleEtape: $result['nouvelleEtape'],
        ));

        return $result['history'];
    }

    private function assertActionValide(?WorkflowEtape $actuelle, WorkflowEtape $cible, string $actionType): void
    {
        if ($actuelle && (int) $actuelle->id === (int) $cible->id) {
            throw ValidationException::withMessages([
                'workflow_etape_id' => 'Le courrier est déjà sur cette étape.',
            ]);
        }

        match ($actionType) {
            self::ACTION_FORWARD => $this->assertForward($actuelle, $cible),
            self::ACTION_BACKWARD => $this->assertBackward($actuelle, $cible),
            self::ACTION_REJECT => $this->assertReject($actuelle, $cible),
        };
    }

    private function assertForward(?WorkflowEtape $actuelle, WorkflowEtape $cible): void
    {
        if ($actuelle === null) {
            if ((int) $cible->ordre !== 1) {
                throw ValidationException::withMessages([
                    'workflow_etape_id' => 'Sans étape courante, seul le démarrage à « Réception » (ordre 1) est autorisé.',
                ]);
            }

            return;
        }

        if ((int) $cible->ordre !== $this->nextOrder((int) $actuelle->ordre)) {
            throw ValidationException::withMessages([
                'workflow_etape_id' => "Depuis « {$actuelle->libelle} », seule la prochaine étape du traitement est autorisée.",
            ]);
        }
    }

    private function assertBackward(?WorkflowEtape $actuelle, WorkflowEtape $cible): void
    {
        if ($actuelle === null) {
            throw ValidationException::withMessages([
                'workflow_etape_id' => 'Impossible de reculer : aucune étape courante.',
            ]);
        }

        if ((int) $cible->ordre !== $this->previousOrder((int) $actuelle->ordre)) {
            throw ValidationException::withMessages([
                'workflow_etape_id' => "Recul non séquentiel : depuis « {$actuelle->libelle} », seule l'étape précédente est autorisée.",
            ]);
        }
    }

    private function assertReject(?WorkflowEtape $actuelle, WorkflowEtape $cible): void
    {
        if ($actuelle === null) {
            throw ValidationException::withMessages([
                'workflow_etape_id' => 'Impossible de rejeter : aucune étape courante.',
            ]);
        }

        if (! $actuelle->is_rejectable) {
            throw ValidationException::withMessages([
                'action_type' => "L'étape « {$actuelle->libelle} » n'autorise pas le rejet.",
            ]);
        }

        if ((int) $cible->ordre !== $this->previousOrder((int) $actuelle->ordre)) {
            throw ValidationException::withMessages([
                'workflow_etape_id' => 'Un rejet doit cibler une étape antérieure du circuit.',
            ]);
        }
    }

    /**
     * Compatibilité historique : libellés d’étape pour from_status / to_status.
     */
    public function labelPourEtape(WorkflowEtape $etape, string $actionType = self::ACTION_FORWARD): string
    {
        if ($actionType === self::ACTION_REJECT) {
            return $etape->libelle.' (rejet)';
        }

        return $etape->libelle;
    }

    /**
     * SLA CCT : now + delai_jours en jours ouvrés (week-ends exclus).
     * Traitement terminé ou archivé : plus de date limite.
     */
    public function calculerDateLimite(WorkflowEtape $etape): ?CarbonInterface
    {
        if ((int) $etape->ordre >= 5) {
            return null;
        }

        $jours = max(0, (int) $etape->delai_jours);

        if ($jours === 0) {
            return Carbon::now()->endOfDay();
        }

        return Carbon::now()->addWeekdays($jours)->endOfDay();
    }
}
