<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class OperationsStatusCommand extends Command
{
    protected $signature = 'app:operations-status {--json}';

    protected $description = 'Contrôle le planificateur et la file sans divulguer les secrets';

    public function handle(): int
    {
        $report = ['checked_at' => now()->toIso8601String(), 'url' => config('app.url'), 'mail_driver' => config('mail.default')];
        $healthy = true;
        foreach (['scheduler', 'queue', 'scheduler-runner', 'queue-runner'] as $name) {
            $path = storage_path('framework/operations/'.$name.'.json');
            try {
                $data = File::exists($path) ? json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR) : [];
                $timestamp = Carbon::parse($data['completed_at'] ?? throw new \RuntimeException('Missing timestamp'));
                $age = $timestamp->diffInSeconds(now(), false);
                $ok = $age >= 0 && $age <= 600 && ($data['exit_code'] ?? 0) === 0;
                $report[$name] = ['ok' => $ok, 'completed_at' => $timestamp->toIso8601String(), 'exit_code' => $data['exit_code'] ?? null];
            } catch (\Throwable) {
                $ok = false;
                $report[$name] = ['ok' => false, 'completed_at' => null];
            }
            $healthy = $healthy && $ok;
        }
        if (config('queue.default') === 'database') {
            $report['pending_jobs'] = DB::connection(config('queue.connections.database.connection'))
                ->table(config('queue.connections.database.table'))->count();
        }
        if (config('queue.failed.driver') === 'database-uuids') {
            $report['failed_jobs'] = DB::connection(config('queue.failed.database'))
                ->table(config('queue.failed.table'))->count();
            $healthy = $healthy && $report['failed_jobs'] === 0;
        }
        $report['ok'] = $healthy;
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return $healthy ? self::SUCCESS : self::FAILURE;
    }
}
