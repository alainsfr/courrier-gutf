<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Enregistrer les services applicatifs
     */
    public function register(): void
    {
        // En production (sur PlanetHoster), on redirige dynamiquement le dossier public
        // vers le dossier parent de l'application (/home/ydykrmdy/courrier/)
        if (config('app.env') === 'production') {
            $this->app->usePublicPath(dirname(base_path()));
        }
    }

    /**
     * Initialiser les services applicatifs au démarrage
     */
    public function boot(): void
    {
        // Force la longueur par défaut des index de chaînes à 191 caractères
        Schema::defaultStringLength(191);
    }
}