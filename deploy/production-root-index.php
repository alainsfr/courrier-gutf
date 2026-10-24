<?php

declare(strict_types=1);

define('LARAVEL_START', microtime(true));

$basePath = __DIR__ . '/app';

// 1. Charger l'autoloader de Composer
require $basePath . '/vendor/autoload.php';

// 2. Démarrer Laravel 12 et traiter la requête entrante
$app = require_once $basePath . '/bootstrap/app.php';
$app->usePublicPath(__DIR__);
$app->handleRequest(Illuminate\Http\Request::capture());
