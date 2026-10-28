<?php

namespace App\Console\Commands;

use App\Jobs\OperationsQueueProbe;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class OperationsHeartbeatCommand extends Command
{
    protected $signature = 'app:operations-heartbeat';

    protected $description = 'Enregistre le passage du planificateur et vérifie le traitement de la file';

    public function handle(): int
    {
        $timestamp = now()->toIso8601String();
        OperationsQueueProbe::dispatch($timestamp);
        File::ensureDirectoryExists(storage_path('framework/operations'));
        File::replace(storage_path('framework/operations/scheduler.json'), json_encode([
            'completed_at' => $timestamp,
        ], JSON_THROW_ON_ERROR));
        $this->info('OPERATIONS_HEARTBEAT_DISPATCHED');

        return self::SUCCESS;
    }
}
