<?php

namespace App\Services;

use App\Models\Courrier;
use App\Models\CourrierHistory;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public function log(
        Courrier $courrier,
        string $action,
        ?string $from,
        ?string $to,
        array $metadata = []
    ): void {
        CourrierHistory::create([
            'courrier_id' => $courrier->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'actor_user_id' => Auth::id(),
            'metadata' => $metadata ?: null,
            'created_at' => now(),
        ]);
    }
}
