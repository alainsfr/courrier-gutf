<?php

namespace App\Services;

use RuntimeException;

class RuntimeSignature
{
    public const CONTEXT = "courrier-gutf:runtime:v1\n";

    public static function verify(string $document, string $publicKey): array
    {
        if (strlen($document) > 16384) {
            throw new RuntimeException('Document too large.');
        }
        $envelope = json_decode($document, true, 8, JSON_THROW_ON_ERROR);
        if (! is_array($envelope) || ! is_string($envelope['payload'] ?? null) || ! is_string($envelope['signature'] ?? null)) {
            throw new RuntimeException('Invalid envelope.');
        }
        $payload = base64_decode($envelope['payload'], true);
        $signature = base64_decode($envelope['signature'], true);
        $key = @openssl_pkey_get_public($publicKey);
        $details = $key ? openssl_pkey_get_details($key) : false;
        if ($payload === false || $signature === false || ! $details || $details['type'] !== OPENSSL_KEYTYPE_RSA || $details['bits'] < 3072
            || strlen($signature) !== (int) ceil($details['bits'] / 8)
            || @openssl_verify(self::CONTEXT.$payload, $signature, $key, OPENSSL_ALGO_SHA256) !== 1) {
            throw new RuntimeException('Signature rejected.');
        }
        $data = json_decode($payload, true, 8, JSON_THROW_ON_ERROR);
        self::validate($data);

        return $data;
    }

    public static function validate(mixed $data): void
    {
        if (! is_array($data) || ($data['version'] ?? null) !== 1 || ($data['product'] ?? null) !== 'courrier-gutf') {
            throw new RuntimeException('Invalid product.');
        }
        foreach (['installation_id' => 32, 'fingerprint' => 64] as $name => $length) {
            if (! is_string($data[$name] ?? null) || ! preg_match('/^[a-f0-9]{'.$length.'}$/D', $data[$name])) {
                throw new RuntimeException('Invalid binding.');
            }
        }
        foreach (['issued_at', 'not_before', 'expires_at', 'generation'] as $name) {
            if (! is_int($data[$name] ?? null) || $data[$name] <= 0) {
                throw new RuntimeException('Invalid interval.');
            }
        }
        if ($data['expires_at'] <= $data['not_before'] || $data['expires_at'] <= $data['issued_at']) {
            throw new RuntimeException('Invalid interval.');
        }
        foreach (['client', 'agency'] as $name) {
            if (! is_string($data[$name] ?? null) || trim($data[$name]) === '' || strlen($data[$name]) > 200) {
                throw new RuntimeException('Invalid recipient.');
            }
        }
    }
}
