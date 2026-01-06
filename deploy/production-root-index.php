<?php

declare(strict_types=1);

define('LARAVEL_START', microtime(true));

$basePath = __DIR__ . '/app';

require $basePath . '/vendor/autoload.php';
$app = require_once $basePath . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request)->send();

$kernel->terminate($request, $response);
