<?php

namespace App\Services;

use App\Models\Bureau;
use App\Models\Courrier;
use App\Models\CourrierHistory;
use App\Models\User;
use App\Models\WorkflowEtape;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CourrierManagementService
{
    public const FIELDS = [
        'objet', 'description', 'reference_externe', 'tf', 'expediteur', 'destinataire',
        'proprietaire', 'requerant', 'vendeur', 'etude_notariale', 'valeur_immeuble',
        'numero_acte', 'numero_repertoire', 'date_courrier', 'date_reception', 'date_envoi',
    ];

    public function version(Courrier $courrier): string
    {
        $fields = array_merge(self::FIELDS, ['id', 'bureau_actuel_id', 'assigned_to_user_id', 'workflow_etape_id', 'updated_at']);
        return hash('sha256', json_encode(Arr::only($courrier->getAttributes(), $fields), JSON_THROW_ON_ERROR));
    }

    public function claim(Courrier $courrier, User $user): Courrier
    {
        return DB::transaction(function () use ($courrier, $user) {
            $current = Courrier::lockForUpdate()->findOrFail($courrier->id);
            Gate::forUser($user)->authorize('claim', $current);
            $this->assertMutable($current);
            abort_unless($current->isAtOrdre(2) && $user->bureau_id
                && (int) $user->bureau_id === (int) $current->bureau_actuel_id
                && $user->can('courrier.process'), 403);
            if ($current->assigned_to_user_id) {
                abort_unless((int) $current->assigned_to_user_id === (int) $user->id, 403);
                return $current;
            }
            $current->update(['assigned_to_user_id' => $user->id]);
            $this->history($current, $user, 'claimed', null, ['assigned_to' => $user->name]);
            return $current;
        }, 3);
    }

    public function correct(Courrier $courrier, User $user, array $details, string $reason, string $version): Courrier
    {
        return DB::transaction(function () use ($courrier, $user, $details, $reason, $version) {
            $current = Courrier::lockForUpdate()->findOrFail($courrier->id);
            Gate::forUser($user)->authorize('update', $current);
            $this->assertMutable($current);
            $this->assertVersion($current, $version);
            $this->assertReason($reason);
            $current->fill(Arr::only($details, self::FIELDS));
            $changes = Arr::only($current->getDirty(), self::FIELDS);
            if ($changes !== []) {
                $before = Arr::only($current->getRawOriginal(), array_keys($changes));
                $current->save();
                $this->history($current, $user, 'details_updated', $reason, ['before' => $before, 'after' => $changes]);
            }
            return $current;
        }, 3);
    }

    public function reassign(Courrier $courrier, User $user, Bureau $office, ?User $agent, string $reason, ?string $version = null): Courrier
    {
        return DB::transaction(function () use ($courrier, $user, $office, $agent, $reason, $version) {
            $current = Courrier::lockForUpdate()->findOrFail($courrier->id);
            Gate::forUser($user)->authorize('reassign', $current);
            $this->assertMutable($current);
            if ($version !== null) {
                $this->assertVersion($current, $version);
            }
            $this->assertReason($reason);
            $destination = Bureau::where('is_active', true)->lockForUpdate()->findOrFail($office->id);
            $agent = $agent ? User::lockForUpdate()->findOrFail($agent->id) : null;
            if ($agent && (! $agent->is_active || (int) $agent->bureau_id !== (int) $destination->id || ! $agent->can('courrier.process'))) {
                throw ValidationException::withMessages(['assigned_to_user_id' => __('courrier.agent_invalid')]);
            }
            $officeChanged = (int) $current->bureau_actuel_id !== (int) $destination->id;
            $before = ['bureau' => $current->bureauActuel?->nom, 'agent' => $current->assignedTo?->name];
            if (! $officeChanged && (int) $current->assigned_to_user_id === (int) $agent?->id) {
                return $current;
            }
            $current->bureau_actuel_id = $destination->id;
            $current->assigned_to_user_id = $agent?->id;
            if ($officeChanged) {
                $pending = WorkflowEtape::where('ordre', 2)->firstOrFail();
                $current->workflow_etape_id = $pending->id;
                $current->date_limite = app(WorkflowService::class)->calculerDateLimite($pending);
                $current->is_late = false;
            }
            $current->save();
            $this->history($current, $user, 'reassigned', $reason, [
                'before' => $before, 'after' => ['bureau' => $destination->nom, 'agent' => $agent?->name],
            ]);
            return $current;
        }, 3);
    }

    private function assertMutable(Courrier $courrier): void
    {
        if ($courrier->isArchived() || ! in_array($courrier->etapeOrdre(), [2, 3], true)) {
            throw ValidationException::withMessages(['courrier' => __('courrier.closed_readonly')]);
        }
    }

    private function assertVersion(Courrier $courrier, string $version): void
    {
        if (! hash_equals($this->version($courrier), $version)) {
            throw ValidationException::withMessages(['version' => __('courrier.changed_reload')]);
        }
    }

    private function assertReason(string $reason): void
    {
        if (mb_strlen(trim($reason)) < 5 || mb_strlen($reason) > 2000) {
            throw ValidationException::withMessages(['reason' => __('courrier.reason_required')]);
        }
    }

    private function history(Courrier $courrier, User $user, string $action, ?string $reason, array $metadata): void
    {
        CourrierHistory::create([
            'courrier_id' => $courrier->id, 'action' => $action, 'actor_user_id' => $user->id,
            'note_metier' => $reason, 'metadata' => $metadata, 'created_at' => now(),
        ]);
    }
}
