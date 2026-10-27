<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\BureauSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowEtapesSeeder;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ResetDemoCommand extends Command
{
    protected $signature = 'app:reset-demo
        {--execute= : Must equal DELETE_OLD_DEMO_DATA}
        {--expected-database= : Exact name of the database to reset}
        {--production : Explicitly allow the hosted demonstration}';

    protected $description = 'Supprime les données de démonstration sans sauvegarde et conserve un seul accès super-administrateur.';

    public function handle(Filesystem $files): int
    {
        if ($this->option('execute') !== 'DELETE_OLD_DEMO_DATA') {
            $this->error('Suppression refusée : confirmation explicite manquante.');
            return self::FAILURE;
        }

        if (app()->environment('production') && ! $this->option('production')) {
            $this->error('Suppression refusée : option de démonstration hébergée manquante.');
            return self::FAILURE;
        }

        $database = DB::connection()->getDatabaseName();
        if (! $this->option('expected-database') || $this->option('expected-database') !== $database) {
            $this->error('Suppression refusée : la base ne correspond pas à la cible indiquée.');
            return self::FAILURE;
        }

        if (! Schema::hasTable('courriers') || ! Schema::hasTable('users')) {
            $this->error('Suppression refusée : la base ne correspond pas à une installation de courriers.');
            return self::FAILURE;
        }

        $admin = User::role('super-admin')->where('is_active', true)->orderBy('id')->first()
            ?? User::role('admin')->where('is_active', true)->orderBy('id')->first();
        if (! $admin) {
            $this->error('Suppression refusée : aucun accès super-administrateur actif à conserver.');
            return self::FAILURE;
        }

        // Access is held only in memory; no archive, dump or temporary copy is written.
        $access = $admin->only(['name', 'email', 'password', 'locale']);
        $access['is_active'] = true;
        $access['email_verified_at'] = now();
        $storage = realpath(storage_path());
        if (! $storage) {
            throw new RuntimeException('Répertoire de stockage introuvable.');
        }

        $targets = [
            'app/private', 'app/public', 'app/backups', 'app/backup-temp', 'app/scanner_inbox',
            'framework/sessions', 'framework/cache/data', 'framework/views', 'logs',
        ];

        // Resolve every deletion target before touching files or the database.
        foreach ($targets as $target) {
            $path = storage_path($target);
            if (! is_dir($path)) {
                continue;
            }
            $resolved = realpath($path);
            if (! $resolved || ! str_starts_with($resolved, $storage.DIRECTORY_SEPARATOR) || ! is_writable($resolved)) {
                throw new RuntimeException('Cible de nettoyage hors du stockage autorisé ou non accessible.');
            }
        }

        Artisan::call('down', ['--retry' => 60]);
        foreach ($targets as $target) {
            $path = storage_path($target);
            if (! is_dir($path)) {
                continue;
            }
            foreach ($files->files($path, true) as $file) {
                if ($file->getFilename() !== '.gitignore' && ! $files->delete($file->getPathname())) {
                    throw new RuntimeException('Impossible de supprimer un ancien fichier.');
                }
            }
            foreach ($files->directories($path) as $directory) {
                // Never follow directory links into another application's files.
                $deleted = is_link($directory) ? unlink($directory) : $files->deleteDirectory($directory);
                if (! $deleted) {
                    throw new RuntimeException('Impossible de supprimer un ancien dossier.');
                }
            }
        }

        if (Artisan::call('migrate:fresh', ['--force' => true]) !== self::SUCCESS) {
            throw new RuntimeException('Recréation de la base échouée ; maintenance conservée.');
        }
        foreach ([BureauSeeder::class, WorkflowEtapesSeeder::class, RolesAndPermissionsSeeder::class] as $seeder) {
            if (Artisan::call('db:seed', ['--class' => $seeder, '--force' => true]) !== self::SUCCESS) {
                throw new RuntimeException('Initialisation échouée ; maintenance conservée.');
            }
        }
        User::forceCreate($access)->assignRole('super-admin');
        Artisan::call('cache:clear');
        Artisan::call('queue:restart');
        Artisan::call('up');

        $this->info('RESET_DEMO_OK : anciens dossiers, fichiers, échanges et comptes de test supprimés sans sauvegarde.');
        $this->info('Code actuel, configuration et un accès super-administrateur conservés.');

        return self::SUCCESS;
    }
}
