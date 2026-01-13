<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // Option B: use user preference if authenticated
        $userLocale = $request->user()?->locale;

        if (is_string($userLocale) && in_array($userLocale, ['fr', 'en'], true)) {
            app()->setLocale($userLocale);
            return $next($request);
        }

        // Fallback: session choice (login page / guest)
        $sessionLocale = session('locale', config('app.locale', config('app.fallback_locale', 'fr')));

        if (is_string($sessionLocale) && in_array($sessionLocale, ['fr', 'en'], true)) {
            app()->setLocale($sessionLocale);
        }

        return $next($request);
    }
}
