<?php

namespace App\Http\Middleware;

use App\Services\ManagedRuntime;
use Closure;
use Illuminate\Http\Request;

class EnforceRuntimePolicy
{
    public function handle(Request $request, Closure $next)
    {
        if (! config('distribution.managed')) {
            return $next($request);
        }
        // Authentication, session safety and account recovery remain available.
        if ($request->routeIs('login', 'login.store', 'logout', 'password.*', 'verification.*', 'session.*', 'locale.set', 'lang.switch',
            'broadcasting.auth', 'profile.signature-pin.update', 'admin.users.recovery', 'admin.users.recovery.issue')) {
            return $next($request);
        }
        $canWrite = app(ManagedRuntime::class)->canWrite();
        $modifies = ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
        $restrictedPage = $request->routeIs('*.create', '*.edit', '*.reassignment', '*.export.*', 'courriers.bordereau', 'admin.backups.download')
            && ! $request->routeIs('profile.edit');
        if (($modifies || $restrictedPage) && ! $canWrite) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => __('ui.operation_unavailable')], 403);
            }

            return response()->view('errors.operation-unavailable', [], 403);
        }

        return $next($request);
    }
}
