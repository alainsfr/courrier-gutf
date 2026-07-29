<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('app_locale')
            ?? $request->user()?->locale
            ?? session('locale')
            ?? config('app.locale', config('app.fallback_locale', 'fr'));

        if (is_string($locale) && in_array($locale, ['fr', 'en'], true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
