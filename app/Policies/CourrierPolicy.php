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
        if ($user->hasRole('admin')) {
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

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('courrier.create');
    }

    public function assign(User $user, Courrier $courrier): bool
    {
        return $user->can('courrier.assign');
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
        return $user->can('courrier.validate');
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
}
