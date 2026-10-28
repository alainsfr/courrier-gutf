<?php

namespace App\Providers;

use App\Models\Conversation;
use App\Models\Courrier;
use App\Models\Message;
use App\Policies\ConversationPolicy;
use App\Policies\CourrierPolicy;
use App\Policies\MessagePolicy;
use App\Services\AppSettingsService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
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

        ResetPassword::toMailUsing(function ($user, string $token) {
            $locale = in_array($user->locale, ['fr', 'en'], true) ? $user->locale : config('app.locale');
            $url = rtrim(config('app.url'), '/').route('password.reset', [
                'token' => $token, 'email' => $user->getEmailForPasswordReset(),
            ], false);
            $translate = fn (string $key, array $replace = []) => __('auth_mail.'.$key, $replace, $locale);

            return (new MailMessage)
                ->subject($translate('subject'))
                ->markdown('emails.reset-password', [
                    'greeting' => $translate('greeting'), 'intro' => $translate('intro'),
                    'action' => $translate('action'), 'url' => $url,
                    'expires' => $translate('expires', ['minutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]),
                    'ignore' => $translate('ignore'), 'fallback' => $translate('fallback'),
                    'signature' => $translate('signature'),
                ]);
        });

        Gate::policy(Courrier::class, CourrierPolicy::class);
        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(Message::class, MessagePolicy::class);

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
                $brand = $settings->all();
                if (! empty($brand['app_name'])) {
                    config(['app.name' => $brand['app_name']]);
                }
                $view->with('brand', $brand);
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
