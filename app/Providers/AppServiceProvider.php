<?php

namespace App\Providers;

use App\Models\Conversation;
use App\Models\Courrier;
use App\Policies\ConversationPolicy;
use App\Policies\CourrierPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force la longueur par défaut des index de chaînes à 191 caractères
        Schema::defaultStringLength(191);

        Gate::policy(Courrier::class, CourrierPolicy::class);
        Gate::policy(Conversation::class, ConversationPolicy::class);

        // Spatie : le rôle super-admin contourne toutes les autorisations Gate/Policy
        Gate::before(function ($user, string $ability) {
            return method_exists($user, 'hasRole') && $user->hasRole('super-admin') ? true : null;
        });
    }
}