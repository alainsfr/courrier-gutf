<?php

use App\Services\RuntimeSignature;

require __DIR__.'/common.php';
$options = getopt('', ['request:', 'key:', 'client:', 'agency:', 'starts:', 'expires:', 'output:']);
foreach (['request', 'key', 'client', 'agency', 'expires', 'output'] as $required) {
    if (empty($options[$required])) {
        constructorFail('Option requise : --'.$required);
    }
}
$request = constructorReadRequest($options['request']);
function licenseTime(string $value): int
{
    if (! preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d(?:Z|[+-](?:(?:0\d|1[0-3]):[0-5]\d|14:00))$/D', $value)) {
        constructorFail('Date attendue : ISO 8601 avec heure et fuseau, exemple 2027-10-29T23:59:59+01:00.');
    }
    try {
        $date = new DateTimeImmutable($value);
        $errors = DateTimeImmutable::getLastErrors();
        if ($errors && ($errors['warning_count'] || $errors['error_count'])) {
            constructorFail('Date invalide.');
        }

        return $date->getTimestamp();
    } catch (Throwable) {
        constructorFail('Date invalide.');
    }
}
$issued = time();
$payload = [
    'version' => 1, 'product' => 'courrier-gutf', 'installation_id' => $request['installation_id'], 'fingerprint' => $request['fingerprint'],
    'generation' => $request['next_generation'], 'client' => $options['client'], 'agency' => $options['agency'],
    'issued_at' => $issued, 'not_before' => isset($options['starts']) ? licenseTime($options['starts']) : $issued,
    'expires_at' => licenseTime($options['expires']),
];
try {
    RuntimeSignature::validate($payload);
    if ($payload['expires_at'] <= $issued) {
        constructorFail('La date de fin doit être future.');
    }
    $pem = @file_get_contents($options['key']);
    $key = $pem ? @openssl_pkey_get_private($pem) : false;
    $details = $key ? openssl_pkey_get_details($key) : false;
    if (! $details || $details['type'] !== OPENSSL_KEYTYPE_RSA || $details['bits'] < 3072) {
        constructorFail('Clé privée RSA 3072 bits minimum requise.');
    }
    $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    if (! openssl_sign(RuntimeSignature::CONTEXT.$body, $signature, $key, OPENSSL_ALGO_SHA256)) {
        constructorFail('Signature impossible.');
    }
    constructorWriteNew($options['output'], json_encode(['payload' => base64_encode($body), 'signature' => base64_encode($signature)], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL);
    echo "Licence créée pour cette installation uniquement.\n";
    echo 'Début UTC : '.gmdate('Y-m-d H:i:s', $payload['not_before']).PHP_EOL;
    echo 'Fin UTC exclusive : '.gmdate('Y-m-d H:i:s', $payload['expires_at']).PHP_EOL;
} catch (Throwable) {
    constructorFail('Création impossible. Vérifiez les paramètres et la clé.');
}
