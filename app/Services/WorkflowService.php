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
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class WorkflowService
{
    public const ACTION_FORWARD = 'forward';
    public const ACTION_BACKWARD = 'backward';
    public const ACTION_REJECT = 'reject';

    /**
     * Transfert hybride CCT + UX SaaS.
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

            $this->assertActionValide($ancienneEtape, $nouvelleEtape, $actionType);

            $fromStatus = $courrier->status;
            $toStatus = $this->statusPourEtape($nouvelleEtape, $actionType);

            $dateLimite = $this->calculerDateLimite($nouvelleEtape);

            $courrier->forceFill([
                'workflow_etape_id' => $nouvelleEtape->id,
                'bureau_actuel_id' => $nouvelleEtape->bureau_id,
                'status' => $toStatus,
                'date_limite' => $dateLimite,
                'is_late' => false,
                'archived_at' => (int) $nouvelleEtape->ordre === 6 ? now() : null,
            ])->save();

            $history = CourrierHistory::create([
                'courrier_id' => $courrier->id,
                'action' => 'workflow_transfer',
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'ancienne_etape_id' => $ancienneEtape?->id,
                'nouvelle_etape_id' => $nouvelleEtape->id,
                'note_metier' => $note,
                'action_type' => $actionType,
                'actor_user_id' => $user->id,
                'metadata' => [
                    'bureau_id' => $nouvelleEtape->bureau_id,
                    'bureau_nom' => $nouvelleEtape->bureau?->nom,
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

        if ((int) $cible->ordre !== ((int) $actuelle->ordre + 1)) {
            throw ValidationException::withMessages([
                'workflow_etape_id' => "Avancement non séquentiel : depuis « {$actuelle->libelle} », seule l'étape suivante est autorisée.",
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

        if ((int) $cible->ordre !== ((int) $actuelle->ordre - 1) || (int) $cible->ordre < 1) {
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

        // Rejet CCT : retour strictement en arrière dans le circuit
        if ((int) $cible->ordre >= (int) $actuelle->ordre) {
            throw ValidationException::withMessages([
                'workflow_etape_id' => 'Un rejet doit cibler une étape antérieure du circuit.',
            ]);
        }
    }

    /**
     * Compatibilité avec les statuts V1 / policies existantes.
     */
    private function statusPourEtape(WorkflowEtape $etape, string $actionType): string
    {
        if ($actionType === self::ACTION_REJECT) {
            return 'rejete';
        }

        return match ((int) $etape->ordre) {
            1 => 'recu',
            2 => 'affecte',
            3 => 'en_traitement',
            4 => 'en_validation',
            5 => 'valide',
            6 => 'archive',
            default => 'recu',
        };
    }

    /**
     * SLA CCT : now + delai_jours en jours ouvrés (week-ends exclus).
     * Archivage (ordre 6) → plus de date limite.
     */
    public function calculerDateLimite(WorkflowEtape $etape): ?CarbonInterface
    {
        if ((int) $etape->ordre === 6) {
            return null;
        }

        $jours = max(0, (int) $etape->delai_jours);

        if ($jours === 0) {
            return Carbon::now()->endOfDay();
        }

        return Carbon::now()->addWeekdays($jours)->endOfDay();
    }
}
