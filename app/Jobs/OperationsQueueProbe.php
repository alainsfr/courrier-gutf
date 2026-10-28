<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;

class OperationsQueueProbe implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $requestedAt) {}

    public function handle(): void
    {
        File::ensureDirectoryExists(storage_path('framework/operations'));
        File::replace(storage_path('framework/operations/queue.json'), json_encode([
            'requested_at' => $this->requestedAt,
            'completed_at' => now()->toIso8601String(),
            'connection' => config('queue.default'),
        ], JSON_THROW_ON_ERROR));
    }
}
