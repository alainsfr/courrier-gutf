<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__, 2).'/vendor/autoload.php';

function constructorFail(string $message): never
{
    fwrite(STDERR, $message.PHP_EOL);
    exit(1);
}

function constructorWriteNew(string $path, string $contents): void
{
    $stream = @fopen($path, 'xb');
    if (! $stream) {
        constructorFail('Fichier existant ou destination inaccessible : '.$path);
    }
    try {
        if (fwrite($stream, $contents) !== strlen($contents)) {
            constructorFail('Écriture incomplète.');
        }
    } finally {
        fclose($stream);
    }
    @chmod($path, 0600);
}

function constructorReadRequest(string $path): array
{
    if (! is_file($path) || filesize($path) > 4096) {
        constructorFail('Demande invalide.');
    }
    $data = json_decode(file_get_contents($path), true);
    if (! is_array($data) || ($data['product'] ?? null) !== 'courrier-gutf' || ($data['version'] ?? null) !== 1
        || ! is_string($data['installation_id'] ?? null) || ! preg_match('/^[a-f0-9]{32}$/D', $data['installation_id'])
        || ! is_string($data['fingerprint'] ?? null) || ! preg_match('/^[a-f0-9]{64}$/D', $data['fingerprint'])
        || ! is_int($data['next_generation'] ?? null) || $data['next_generation'] < 1) {
        constructorFail('Demande invalide.');
    }

    return $data;
}
