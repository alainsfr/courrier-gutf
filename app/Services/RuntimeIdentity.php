<?php

namespace App\Services;

use RuntimeException;

class RuntimeIdentity
{
    public function fingerprint(): string
    {
        $id = trim((string) @file_get_contents(config('runtime.machine_id')));
        if (! preg_match('/^[a-f0-9]{32}$/iD', $id) || $id === str_repeat('0', 32)) {
            throw new RuntimeException('Machine identity unavailable.');
        }
        $uuid = trim((string) @file_get_contents(config('runtime.machine_uuid')));

        // Never transmit the raw OS identity; use an application-specific derivation.
        return hash_hmac('sha256', strtolower($id).'|'.strtolower($uuid), 'courrier-gutf:runtime-identity:v1');
    }
}
