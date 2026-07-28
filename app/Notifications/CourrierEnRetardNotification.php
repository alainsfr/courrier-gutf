<?php

namespace App\Notifications;

use App\Models\Courrier;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CourrierEnRetardNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Courrier $courrier
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $etape = $this->courrier->workflowEtape?->libelle ?? '—';
        $limite = $this->courrier->date_limite?->format('d/m/Y H:i') ?? '—';

        return [
            'title' => __('notifications.courrier_late_title'),
            'message' => __('notifications.courrier_late_msg', [
                'ref' => $this->courrier->reference_interne,
                'etape' => $etape,
                'limite' => $limite,
            ]),
            'url' => route('courriers.show', $this->courrier),
            'type' => 'sla_late',
            'courrier_id' => $this->courrier->id,
            'reference_interne' => $this->courrier->reference_interne,
            'date_limite' => $this->courrier->date_limite?->toIso8601String(),
            'workflow_etape_id' => $this->courrier->workflow_etape_id,
            'bureau_actuel_id' => $this->courrier->bureau_actuel_id,
        ];
    }
}
