<?php

namespace App\Services;

use RuntimeException;

class RuntimeStateStore
{
    /** Run under one stable file lock; updates are authenticated and persisted before returning. */
    public function locked(callable $operation, bool $initialize = false): mixed
    {
        $directory = config('runtime.directory');
        $path = $directory.'/state.json';
        if ($initialize && ! is_dir($directory) && ! @mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Storage unavailable.');
        }
        if (is_link($directory) || is_link($path) || (! $initialize && ! is_file($path))) {
            throw new RuntimeException('State unavailable.');
        }
        $stream = @fopen($path, $initialize ? 'c+b' : 'r+b');
        if (! $stream) {
            throw new RuntimeException('State unavailable.');
        }
        try {
            if (! flock($stream, LOCK_EX)) {
                throw new RuntimeException('State unavailable.');
            }
            @chmod($path, 0600);
            $raw = stream_get_contents($stream, 32769);
            $state = null;
            if ($raw !== '') {
                if (strlen($raw) > 32768) {
                    throw new RuntimeException('Invalid state.');
                }
                $envelope = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
                $body = $envelope['body'] ?? '';
                if (! is_string($body) || ! is_string($envelope['mac'] ?? null)
                    || ! hash_equals($this->mac($body), $envelope['mac'])) {
                    throw new RuntimeException('Invalid state.');
                }
                $state = json_decode($body, true, 8, JSON_THROW_ON_ERROR);
                if (! is_array($state) || ! preg_match('/^[a-f0-9]{32}$/D', $state['installation_id'] ?? '')
                    || ! is_int($state['last_seen'] ?? null) || ! is_int($state['generation'] ?? null)) {
                    throw new RuntimeException('Invalid state.');
                }
            } elseif (! $initialize) {
                throw new RuntimeException('State unavailable.');
            }
            $before = $state;
            $result = $operation($state);
            if ($state !== $before) {
                $body = json_encode($state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
                $output = json_encode(['body' => $body, 'mac' => $this->mac($body)], JSON_THROW_ON_ERROR);
                rewind($stream);
                if (! ftruncate($stream, 0) || fwrite($stream, $output) !== strlen($output) || ! fflush($stream)) {
                    throw new RuntimeException('State persistence failed.');
                }
            }

            return $result;
        } finally {
            flock($stream, LOCK_UN);
            fclose($stream);
        }
    }

    private function mac(string $body): string
    {
        $key = config('app.key');
        if (! is_string($key) || $key === '') {
            throw new RuntimeException('Application key unavailable.');
        }

        return hash_hmac('sha256', $body, 'courrier-gutf:runtime-state:v1|'.$key);
    }
}
