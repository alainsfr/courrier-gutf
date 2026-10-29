<?php

namespace App\Console\Commands;

use App\Services\ManagedRuntime;
use Illuminate\Console\Command;
use Throwable;

class RuntimeRequestCommand extends Command
{
    protected $signature = 'app:installation-request {output : New JSON request file}';

    protected $description = 'Prépare une demande d’installation pour le constructeur, sans connexion réseau';

    public function handle(ManagedRuntime $runtime): int
    {
        try {
            $request = $runtime->request();
            $stream = @fopen($this->argument('output'), 'xb');
            if (! $stream) {
                throw new \RuntimeException;
            }
            $json = json_encode($request, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
            try {
                if (fwrite($stream, $json) !== strlen($json)) {
                    throw new \RuntimeException;
                }
            } finally {
                fclose($stream);
            }
            @chmod($this->argument('output'), 0600);
            $this->info('Demande créée. Transmettez ce fichier au constructeur.');

            return self::SUCCESS;
        } catch (Throwable) {
            $this->error('Impossible de préparer la demande. Contactez le constructeur.');

            return self::FAILURE;
        }
    }
}
