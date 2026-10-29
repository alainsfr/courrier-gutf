<?php

require __DIR__.'/common.php';
$options = getopt('', ['directory:']);
$directory = realpath($options['directory'] ?? '');
if (! $directory) {
    constructorFail('Créez un dossier privé hors du projet, puis utilisez --directory=CHEMIN.');
}
$root = realpath(dirname(__DIR__, 2));
$normalized = strtolower(str_replace('\\', '/', $directory));
if ($normalized === strtolower(str_replace('\\', '/', $root)) || str_starts_with($normalized.'/', strtolower(str_replace('\\', '/', $root)).'/')) {
    constructorFail('Les clés privées doivent rester hors du projet et des paquets clients.');
}
if (file_exists($directory.'/issuer-private.pem') || file_exists($directory.'/issuer-public.pem')) {
    constructorFail('Clés déjà présentes : aucune modification.');
}
$cryptoOptions = \App\Support\SigningKeyOptions::rsa();
$key = openssl_pkey_new($cryptoOptions);
if (! $key || ! openssl_pkey_export($key, $private, null, $cryptoOptions)) {
    constructorFail('OpenSSL ne peut pas créer la clé.');
}
$details = openssl_pkey_get_details($key);
constructorWriteNew($directory.'/issuer-private.pem', $private);
constructorWriteNew($directory.'/issuer-public.pem', $details['key']);
if (extension_loaded('sodium')) {
    sodium_memzero($private);
}
unset($private);
echo "Clés créées. Conservez la clé privée hors des paquets et limitez ses droits d’accès.\n";
