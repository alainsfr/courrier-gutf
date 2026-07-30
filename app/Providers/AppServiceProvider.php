<?php

namespace App\Providers;

use App\Models\Conversation;
use App\Models\Courrier;
use App\Policies\ConversationPolicy;
use App\Policies\CourrierPolicy;
use App\Services\AppSettingsService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AppSettingsService::class);
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Gate::policy(Courrier::class, CourrierPolicy::class);
        Gate::policy(Conversation::class, ConversationPolicy::class);

        Gate::before(function ($user, string $ability) {
            return method_exists($user, 'hasRole') && $user->hasRole('super-admin') ? true : null;
        });

        View::composer('*', function ($view) {
            $defaults = [
                'app_name' => config('app.name', 'COURRIER GUTF'),
                'app_tagline' => '',
                'ministry' => (string) config('institution.ministry', ''),
                'primary_color' => '#17384b',
                'accent_color' => '#dc633a',
            ];

            if (! Schema::hasTable('app_settings')) {
                $view->with('brand', $defaults);
                $view->with('brandLogoUrl', null);
                $view->with('brandFaviconUrl', null);

                return;
            }

            try {
                /** @var AppSettingsService $settings */
                $settings = app(AppSettingsService::class);
                $view->with('brand', $settings->all());
                $view->with('brandLogoUrl', $settings->logoUrl());
                $view->with('brandFaviconUrl', $settings->faviconUrl());
            } catch (\Throwable $e) {
                $view->with('brand', $defaults);
                $view->with('brandLogoUrl', null);
                $view->with('brandFaviconUrl', null);
            }
        });
    }
}
