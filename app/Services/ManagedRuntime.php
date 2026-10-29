<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class ManagedRuntime
{
    public function __construct(private RuntimeStateStore $store, private RuntimeIdentity $identity) {}

    public function canWrite(): bool
    {
        if (! config('distribution.managed')) {
            return true;
        }

        return $this->inspect()['allowed'];
    }

    /** Constructor diagnostics only; never serialize this result into an HTTP response. */
    public function inspect(): array
    {
        try {
            return $this->store->locked(function (&$state) {
                $now = now()->timestamp;
                if ($now + config('runtime.clock_tolerance') < $state['last_seen']) {
                    return ['allowed' => false, 'reason' => 'clock_rollback'];
                }
                $data = $this->document($state['document'] ?? '');
                if (! hash_equals($data['installation_id'], $state['installation_id']) || ! hash_equals($data['fingerprint'], $this->identity->fingerprint())) {
                    return ['allowed' => false, 'reason' => 'binding'];
                }
                if ($data['generation'] !== $state['generation']) {
                    return ['allowed' => false, 'reason' => 'generation'];
                }
                // Record observed time even after expiry: rewinding the clock must not restore access.
                $state['last_seen'] = max($state['last_seen'], $now);
                if ($now + config('runtime.clock_tolerance') < $data['issued_at'] || $now < $data['not_before']) {
                    return ['allowed' => false, 'reason' => 'not_started'];
                }
                if ($now >= $data['expires_at']) {
                    $state['expired_generation'] = max($state['expired_generation'] ?? 0, $data['generation']);

                    return ['allowed' => false, 'reason' => 'expired'];
                }
                if (($state['expired_generation'] ?? 0) >= $data['generation']) {
                    return ['allowed' => false, 'reason' => 'expired'];
                }

                return ['allowed' => true, 'reason' => 'valid', 'expires_at' => $data['expires_at'], 'generation' => $data['generation']];
            });
        } catch (Throwable) {
            // No licensing reason, key, interval or exception detail reaches application logs or UI.
            return ['allowed' => false, 'reason' => 'unavailable'];
        }
    }

    public function request(): array
    {
        $fingerprint = $this->identity->fingerprint();

        return $this->store->locked(function (&$state) use ($fingerprint) {
            $state ??= ['installation_id' => bin2hex(random_bytes(16)), 'last_seen' => now()->timestamp, 'generation' => 0];

            return ['version' => 1, 'product' => 'courrier-gutf', 'installation_id' => $state['installation_id'],
                'fingerprint' => $fingerprint, 'next_generation' => $state['generation'] + 1, 'requested_at' => now()->timestamp];
        }, true);
    }

    public function install(string $document): void
    {
        $data = $this->document($document);
        $fingerprint = $this->identity->fingerprint();
        $this->store->locked(function (&$state) use ($data, $document, $fingerprint) {
            if (! hash_equals($data['installation_id'], $state['installation_id']) || ! hash_equals($data['fingerprint'], $fingerprint)
                || $data['generation'] <= $state['generation']) {
                throw new RuntimeException('Activation rejected.');
            }
            $now = now()->timestamp;
            if ($now + config('runtime.clock_tolerance') < max($data['issued_at'], $state['last_seen']) || $now >= $data['expires_at']) {
                throw new RuntimeException('Activation interval rejected.');
            }
            $state['document'] = $document;
            $state['generation'] = $data['generation'];
            $state['last_seen'] = max($now, $state['last_seen']);
        });
    }

    private function document(string $document): array
    {
        $key = @file_get_contents(config('runtime.public_key'));
        if (! $key) {
            throw new RuntimeException('Issuer unavailable.');
        }

        return RuntimeSignature::verify($document, $key);
    }
}
