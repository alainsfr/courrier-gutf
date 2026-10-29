<?php

namespace App\Console\Commands;

use App\Services\ManagedRuntime;
use Illuminate\Console\Command;
use Throwable;

class RuntimeInstallCommand extends Command
{
    protected $signature = 'app:installation-import {file : File supplied by the constructor}';

    protected $description = 'Applique le fichier de configuration signé fourni par le constructeur';

    public function handle(ManagedRuntime $runtime): int
    {
        try {
            $path = $this->argument('file');
            if (! is_file($path) || filesize($path) > 16384) {
                throw new \RuntimeException;
            }
            $runtime->install(file_get_contents($path));
            $this->info('Configuration appliquée.');

            return self::SUCCESS;
        } catch (Throwable) {
            $this->error('Configuration non applicable. Contactez le constructeur.');

            return self::FAILURE;
        }
    }
}
