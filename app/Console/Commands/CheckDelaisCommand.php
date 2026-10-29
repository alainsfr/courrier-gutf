<?php

namespace App\Console\Commands;

use App\Models\Courrier;
use App\Models\User;
use App\Notifications\CourrierEnRetardNotification;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CheckDelaisCommand extends Command
{
    protected $signature = 'app:check-delais
                            {--dry-run : Liste les courriers concernés sans les marquer ni notifier}';

    protected $description = 'Détecte les dépassements de délai SLA, marque is_late et notifie le bureau concerné';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        if (! $dryRun && ! app(\App\Services\ManagedRuntime::class)->canWrite()) {
            $this->line(__('ui.operation_unavailable'));

            return self::SUCCESS;
        }

        $query = $this->overdue()->with(['workflowEtape:id,libelle', 'bureauActuel:id,nom']);

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

            $notified += DB::transaction(function () use ($courrier) {
                // Recontrôler après verrouillage : traitement/archivage ou autre passage concurrent.
                $courrier = $this->overdue()->whereKey($courrier->id)->lockForUpdate()->first();
                if (! $courrier) {
                    return 0;
                }
                $courrier->forceFill(['is_late' => true])->save();
                if (! $courrier->bureau_actuel_id) {
                    return 0;
                }

                $agents = User::query()
                    ->where('bureau_id', $courrier->bureau_actuel_id)
                    ->where(function ($q) {
                        $q->whereNull('is_active')->orWhere('is_active', true);
                    })
                    ->get();

                if ($courrier->assigned_to_user_id) {
                    $assignee = User::query()
                        ->whereKey($courrier->assigned_to_user_id)
                        ->where(function ($q) {
                            $q->whereNull('is_active')->orWhere('is_active', true);
                        })
                        ->first();

                    if ($assignee && ! $agents->contains('id', $assignee->id)) {
                        $agents->push($assignee);
                    }
                }

                if ($agents->isEmpty()) {
                    $this->warn("   → aucun agent actif pour le bureau #{$courrier->bureau_actuel_id}");

                    return 0;
                }

                // Le canal database et le marquage sont validés dans la même transaction.
                Notification::send($agents, new CourrierEnRetardNotification($courrier));

                return $agents->count();
            }, 3);
        }

        if (! $dryRun) {
            $this->info("Notifications envoyées : {$notified}");
        }

        return self::SUCCESS;
    }

    private function overdue(): Builder
    {
        return Courrier::query()->whereNull('archived_at')->where('is_late', false)
            ->whereNotNull('date_limite')->where('date_limite', '<', now())
            ->whereHas('workflowEtape', fn (Builder $q) => $q->whereNotIn('ordre', [5, 6]));
    }
}
