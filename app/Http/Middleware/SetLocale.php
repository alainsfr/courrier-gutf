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
        $locale = session('locale', config('app.locale'));
        if (is_string($locale) && in_array($locale, ['fr', 'en'], true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
