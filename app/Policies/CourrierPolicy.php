<?php

namespace App\Policies;

use App\Models\Courrier;
use App\Models\User;

class CourrierPolicy
{
    /**
     * Admin bypasses all checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('courrier.view');
    }

    public function view(User $user, Courrier $courrier): bool
    {
        if (! $user->can('courrier.view')) {
            return false;
        }

        // Créateur ou assigné
        if ($this->isStakeholder($user, $courrier)) {
            return true;
        }

        // Validateur : accès aux plis en attente de validation (étape 4)
        if ($user->can('courrier.validate') && $courrier->isEnValidation()) {
            return true;
        }

        // Validateur parapheur : accès aux dossiers où un visa lui est assigné
        if ($courrier->visas()
            ->where('validator_id', $user->id)
            ->exists()) {
            return true;
        }

        // Même bureau que le pli (aligné sur CourrierQueryService / Kanban)
        if ($this->isInCurrentBureau($user, $courrier)) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('courrier.create');
    }

    public function assign(User $user, Courrier $courrier): bool
    {
        if (! $user->can('courrier.assign')) {
            return false;
        }

        return $this->isInCurrentBureau($user, $courrier);
    }

    /**
     * Transfert Kanban / fiche : réservé au bureau qui détient le pli.
     */
    public function transfer(User $user, Courrier $courrier): bool
    {
        if (! $user->can('courrier.view')) {
            return false;
        }

        return $this->isInCurrentBureau($user, $courrier);
    }

    public function process(User $user, Courrier $courrier): bool
    {
        if (! $user->can('courrier.process')) {
            return false;
        }

        // Seul l'assigné peut démarrer / soumettre le traitement
        return (int) $courrier->assigned_to_user_id === (int) $user->id;
    }

    public function validate(User $user, Courrier $courrier): bool
    {
        if (! $user->can('courrier.validate')) {
            return false;
        }

        // Validateur uniquement sur les plis en validation de son bureau (ou sans bureau)
        if (! $courrier->isEnValidation()) {
            return false;
        }

        if ($user->bureau_id && $courrier->bureau_actuel_id) {
            return (int) $user->bureau_id === (int) $courrier->bureau_actuel_id;
        }

        return true;
    }

    public function archive(User $user, Courrier $courrier): bool
    {
        return $user->can('courrier.archive');
    }

    public function attach(User $user, Courrier $courrier): bool
    {
        if (! $user->can('courrier.attach')) {
            return false;
        }

        return $this->isStakeholder($user, $courrier);
    }

    public function detach(User $user, Courrier $courrier): bool
    {
        if (! $user->can('courrier.detach')) {
            return false;
        }

        return $this->isStakeholder($user, $courrier);
    }

    public function downloadAttachment(User $user, Courrier $courrier): bool
    {
        return $this->view($user, $courrier);
    }

    public function export(User $user): bool
    {
        return $user->can('courrier.export');
    }

    private function isStakeholder(User $user, Courrier $courrier): bool
    {
        return (int) $courrier->created_by_user_id === (int) $user->id
            || (int) $courrier->assigned_to_user_id === (int) $user->id;
    }

    private function isInCurrentBureau(User $user, Courrier $courrier): bool
    {
        return $user->bureau_id
            && $courrier->bureau_actuel_id
            && (int) $user->bureau_id === (int) $courrier->bureau_actuel_id;
    }
}
