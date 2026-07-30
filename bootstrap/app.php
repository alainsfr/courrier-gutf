<?php

use App\Http\Middleware\SetLocale;
use App\Http\Middleware\EnsureUserIsActive;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__.'/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        /**
         * Spatie + App route middleware aliases
         */
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'active' => EnsureUserIsActive::class,
        ]);

        /**
         * Invalide les sessions dont le hash mot de passe ne correspond plus
         * (changement de MDP, révocation implicite des autres appareils).
         */
        $middleware->authenticateSessions();

        /**
         * IMPORTANT:
         * SetLocale must run AFTER session is started,
         * therefore it is attached to the "web" group.
         */
        $middleware->appendToGroup('web', SetLocale::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();

/**
 * EN PRODUCTION : Redirige précocement le chemin public vers le dossier parent (/home/ydykrmdy/courrier/)
 * de façon à ce que le manifeste Vite soit résolu à la racine du sous-domaine.
 */
if (env('APP_ENV') === 'production') {
    $app->usePublicPath(dirname(__DIR__));
}

return $app;