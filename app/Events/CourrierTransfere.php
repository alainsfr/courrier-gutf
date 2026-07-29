<?php

namespace App\Events;

use App\Models\Courrier;
use App\Models\CourrierHistory;
use App\Models\User;
use App\Models\WorkflowEtape;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Déclenché après un transfert workflow réussi.
 * Prépare les notifications temps réel (Reverb / Echo).
 */
class CourrierTransfere implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Courrier $courrier,
        public CourrierHistory $history,
        public User $acteur,
        public string $actionType,
        public ?WorkflowEtape $ancienneEtape,
        public WorkflowEtape $nouvelleEtape,
    ) {
        $this->courrier->loadMissing(['bureauActuel:id,nom,code,icon,color', 'assignedTo:id,name']);
        $this->nouvelleEtape->loadMissing('bureau:id,nom,code,icon,color');
        $this->ancienneEtape?->loadMissing('bureau:id,nom,code,icon,color');
    }

    /**
     * Canal privé du courrier + canal bureau pour les agents concernés.
     *
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('courriers.'.$this->courrier->id),
        ];

        if ($this->courrier->bureau_actuel_id) {
            $channels[] = new PrivateChannel('bureaux.'.$this->courrier->bureau_actuel_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'courrier.transfere';
    }

    /**
     * Payload léger pour l’UX GED (timeline / toasts / stepper).
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'courrier_id' => $this->courrier->id,
            'reference_interne' => $this->courrier->reference_interne,
            'action_type' => $this->actionType,
            'etape_libelle' => $this->nouvelleEtape->libelle,
            'history_id' => $this->history->id,
            'note_metier' => $this->history->note_metier,
            'acteur' => [
                'id' => $this->acteur->id,
                'name' => $this->acteur->name,
            ],
            'ancienne_etape' => $this->ancienneEtape ? [
                'id' => $this->ancienneEtape->id,
                'libelle' => $this->ancienneEtape->libelle,
                'ordre' => $this->ancienneEtape->ordre,
                'ui_color' => $this->ancienneEtape->ui_color,
                'ui_icon' => $this->ancienneEtape->ui_icon,
            ] : null,
            'nouvelle_etape' => [
                'id' => $this->nouvelleEtape->id,
                'libelle' => $this->nouvelleEtape->libelle,
                'ordre' => $this->nouvelleEtape->ordre,
                'ui_color' => $this->nouvelleEtape->ui_color,
                'ui_icon' => $this->nouvelleEtape->ui_icon,
                'delai_jours' => $this->nouvelleEtape->delai_jours,
                'is_rejectable' => $this->nouvelleEtape->is_rejectable,
            ],
            'bureau' => $this->courrier->bureauActuel ? [
                'id' => $this->courrier->bureauActuel->id,
                'nom' => $this->courrier->bureauActuel->nom,
                'code' => $this->courrier->bureauActuel->code,
                'icon' => $this->courrier->bureauActuel->icon,
                'color' => $this->courrier->bureauActuel->color,
            ] : null,
            'transfere_at' => optional($this->history->created_at)?->toISOString(),
        ];
    }
}
