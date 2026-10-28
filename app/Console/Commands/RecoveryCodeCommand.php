<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AccountRecoveryService;
use Illuminate\Console\Command;

class RecoveryCodeCommand extends Command
{
    protected $signature = 'app:recovery-code {email}';

    protected $description = 'Génère un code temporaire depuis la console locale, sans e-mail ni modification directe du mot de passe';

    public function handle(AccountRecoveryService $recovery): int
    {
        $user = User::where('email', $this->argument('email'))->where('is_active', true)->first();
        if (! $user) {
            $this->error('Compte actif introuvable.');

            return self::FAILURE;
        }
        $this->line($user->name.' — '.$user->email);
        if (! $this->confirm('Avez-vous vérifié l’identité du titulaire et souhaitez-vous invalider ses précédents codes ?')) return self::FAILURE;
        $issued = $recovery->issue($user);
        $this->warn('Secret affiché une seule fois. Remettez-le directement au titulaire ; ne le conservez pas dans un journal.');
        $this->line($issued['code']);
        $this->info('Valable 15 minutes, une seule utilisation. Ouvrir /recover-with-code sur cette installation.');

        return self::SUCCESS;
    }
}
