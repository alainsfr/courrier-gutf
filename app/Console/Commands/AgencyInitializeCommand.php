<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\BureauSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowEtapesSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AgencyInitializeCommand extends Command
{
    protected $signature = 'app:agency-initialize {--email=} {--name=Super Administrateur} {--password-stdin}';

    protected $description = 'Initialise une agence vierge et son premier administrateur, sans données de démonstration';

    public function handle(): int
    {
        if (! config('distribution.managed') || ! $this->option('password-stdin') || User::query()->exists()) {
            $this->error('Initialisation réservée à une nouvelle installation agence sans utilisateur.');

            return self::FAILURE;
        }
        $password = $this->initialPassword();
        $data = ['email' => $this->option('email'), 'name' => $this->option('name'), 'password' => $password];
        if (Validator::make($data, ['email' => ['required', 'email', 'max:255'], 'name' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:12', 'max:128', 'regex:/^[^\x00-\x1F\x7F]+$/u']])->fails()) {
            $this->error('Adresse ou nom invalide, ou mot de passe hors limites (12 à 128 caractères sans contrôle).');

            return self::FAILURE;
        }
        DB::transaction(function () use ($data) {
            $this->callSilent('db:seed', ['--class' => RolesAndPermissionsSeeder::class, '--force' => true]);
            $this->callSilent('db:seed', ['--class' => BureauSeeder::class, '--force' => true]);
            $this->callSilent('db:seed', ['--class' => WorkflowEtapesSeeder::class, '--force' => true]);
            $user = new User;
            $user->forceFill(['email' => $data['email'], 'name' => $data['name'], 'password' => Hash::make($data['password']),
                'locale' => 'fr', 'is_active' => true, 'email_verified_at' => now()])->save();
            $user->assignRole('super-admin');
        });
        $this->info('Agence initialisée. Aucun courrier de démonstration ajouté.');

        return self::SUCCESS;
    }

    protected function initialPassword(): string
    {
        return rtrim((string) fgets(STDIN, 260), "\r\n");
    }
}
