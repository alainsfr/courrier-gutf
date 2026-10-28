<?php

namespace App\Http\Middleware;

use App\Services\IdleSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceIdleTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('web');
        if (! $guard->check()) {
            return $next($request);
        }

        // Refuser la restauration silencieuse par un ancien cookie « remember me ».
        if ($guard->viaRemember()) {
            return $this->expire($request);
        }

        // Transition pour une session déjà ouverte avant le déploiement.
        if (! $request->session()->has(IdleSession::LAST_ACTIVITY)) {
            IdleSession::start($request);
        }

        if (now()->timestamp >= IdleSession::expiresAt($request)) {
            return $this->expire($request);
        }

        // Les rafraîchissements automatiques JSON/XHR ne sont pas une activité humaine.
        // L'activité d'une page ouverte est signalée par le point d'entrée dédié.
        if (! $request->ajax() && ! $request->expectsJson()
            && ! $request->routeIs('session.*', 'broadcasting.auth', 'notifications.unread_count', 'chat.mini*', 'courriers.attachments.*')) {
            IdleSession::touch($request);
        }

        return $next($request);
    }

    private function expire(Request $request): Response
    {
        Auth::guard('web')->logout();
        IdleSession::forget($request);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['code' => 'SESSION_IDLE_EXPIRED', 'message' => __('session_ui.expired')], 401);
        }

        return redirect()->route('login', ['reason' => 'inactive']);
    }
}
