<?php

// Operate on an already assembled release copy, never on the working repository.
require __DIR__.'/common.php';
$options = getopt('', ['target:', 'public-key:']);
$target = realpath($options['target'] ?? '');
$source = realpath(dirname(__DIR__, 2));
if (! $target || $target === $source || ! is_file($target.'/artisan') || ! is_dir($target.'/config')) {
    constructorFail('Indiquez une copie de livraison Laravel distincte avec --target.');
}
$configPath = realpath($target.'/config');
if (is_link($target.'/config') || is_link($target.'/config/distribution.php') || is_link($target.'/config/runtime-public.pem')
    || ! $configPath || ! str_starts_with(strtolower(str_replace('\\', '/', $configPath)).'/', strtolower(str_replace('\\', '/', $target)).'/')) {
    constructorFail('Dossier config hors de la copie.');
}
$pem = @file_get_contents($options['public-key'] ?? '');
$key = $pem ? @openssl_pkey_get_public($pem) : false;
$details = $key ? openssl_pkey_get_details($key) : false;
if (! $details || $details['type'] !== OPENSSL_KEYTYPE_RSA || $details['bits'] < 3072 || str_contains($pem, 'PRIVATE KEY')) {
    constructorFail('Clé publique RSA 3072 bits minimum requise.');
}
// Refuse incomplete or previously initialized copies: runtime identities and customer data never ship.
if (file_exists($target.'/.git') || is_file($target.'/.env') || is_file($target.'/public/hot')
    || is_file($target.'/bootstrap/cache/config.php') || is_dir($target.'/storage/app/private/.runtime')) {
    constructorFail('La copie doit être vierge de dépôt Git, .env, hot, cache de configuration et identité d’installation.');
}
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS)) as $entry) {
    if ($entry->isLink()) {
        continue;
    }
    $relative = str_replace('\\', '/', substr($entry->getPathname(), strlen($target) + 1));
    if (str_starts_with($relative, 'tools/constructor/') || str_starts_with($relative, 'tests/')) {
        continue;
    }
    $extension = strtolower($entry->getExtension());
    if (in_array($extension, ['p12', 'pfx'], true)
        || (in_array($extension, ['pem', 'key'], true) && ($entry->getSize() > 16384 || preg_match('/-----BEGIN (?:[A-Z ]+ )?PRIVATE KEY-----/', file_get_contents($entry->getPathname()))))) {
        constructorFail('Une clé privée se trouve dans la copie : préparation refusée.');
    }
}
$policy = "<?php\nreturn ['managed' => true];\n";
if (file_put_contents($target.'/config/runtime-public.pem', $pem) !== strlen($pem)
    || file_put_contents($target.'/config/distribution.php', $policy) !== strlen($policy)) {
    constructorFail('Préparation impossible.');
}
function removeConstructorFiles(string $path, string $root): void
{
    if (! file_exists($path) && ! is_link($path)) {
        return;
    }
    if (is_link($path)) {
        if (! unlink($path)) {
            constructorFail('Retrait impossible.');
        }

        return;
    }
    $resolved = realpath($path);
    $prefix = strtolower(str_replace('\\', '/', $root)).'/';
    if (! $resolved || ! str_starts_with(strtolower(str_replace('\\', '/', $resolved)), $prefix)) {
        constructorFail('Chemin de retrait hors de la copie.');
    }
    if (is_dir($resolved)) {
        foreach (new DirectoryIterator($resolved) as $entry) {
            if (! $entry->isDot()) {
                removeConstructorFiles($entry->getPathname(), $root);
            }
        }
        if (! rmdir($resolved)) {
            constructorFail('Retrait impossible.');
        }
    } elseif (! unlink($resolved)) {
        constructorFail('Retrait impossible.');
    }
}
removeConstructorFiles($target.'/tools/constructor', $target);
removeConstructorFiles($target.'/tests', $target);
echo "Copie agence préparée ; outils constructeur, manuel constructeur et tests retirés.\n";
