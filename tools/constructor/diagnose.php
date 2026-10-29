<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$options = getopt('', ['application:']);
$root = realpath($options['application'] ?? '');
if (! $root || ! is_file($root.'/artisan')) {
    fwrite(STDERR, "Chemin d’application invalide.\n");
    exit(1);
}
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$runtime = $app->make(\App\Services\ManagedRuntime::class);
$report = ['managed_distribution' => (bool) config('distribution.managed')];
$report += $report['managed_distribution'] ? $runtime->inspect() : ['allowed' => true, 'reason' => 'unmanaged'];
if (isset($report['expires_at'])) {
    $report['expires_at_utc'] = gmdate('Y-m-d\TH:i:s\Z', $report['expires_at']);
}
echo json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
