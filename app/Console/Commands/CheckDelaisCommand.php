<?php

namespace App\Console\Commands;

use App\Models\Courrier;
use App\Models\User;
use App\Notifications\CourrierEnRetardNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckDelaisCommand extends Command
{
    protected $signature = 'app:check-delais
                            {--dry-run : Liste les courriers concernés sans les marquer ni notifier}';

    protected $description = 'Détecte les dépassements de délai SLA, marque is_late et notifie le bureau concerné';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $query = Courrier::query()
            ->with(['workflowEtape:id,libelle', 'bureauActuel:id,nom'])
            ->whereNull('archived_at')
            ->where('is_late', false)
            ->whereNotNull('date_limite')
            ->where('date_limite', '<', now());

        $courriers = $query->get();

        if ($courriers->isEmpty()) {
            $this->info('Aucun nouveau dépassement de délai.');

            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[dry-run] ' : '')."{$courriers->count()} courrier(s) en retard détecté(s).");

        $notified = 0;

        foreach ($courriers as $courrier) {
            $this->line(sprintf(
                ' • %s | étape=%s | limite=%s | bureau=#%s',
                $courrier->reference_interne,
                $courrier->workflowEtape?->libelle ?? '—',
                optional($courrier->date_limite)->format('Y-m-d H:i'),
                $courrier->bureau_actuel_id ?? '—'
            ));

            if ($dryRun) {
                continue;
            }

            $courrier->forceFill(['is_late' => true])->save();

            if (! $courrier->bureau_actuel_id) {
                continue;
            }

            $agents = User::query()
                ->where('bureau_id', $courrier->bureau_actuel_id)
                ->where(function ($q) {
                    $q->whereNull('is_active')->orWhere('is_active', true);
                })
                ->get();

            if ($agents->isEmpty()) {
                $this->warn("   → aucun agent actif pour le bureau #{$courrier->bureau_actuel_id}");
                continue;
            }

            Notification::send($agents, new CourrierEnRetardNotification($courrier));
            $notified += $agents->count();
        }

        if (! $dryRun) {
            $this->info("Notifications envoyées : {$notified}");
        }

        return self::SUCCESS;
    }
}
