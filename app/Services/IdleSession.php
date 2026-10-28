<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class IdleSession
{
    public const LAST_ACTIVITY = 'idle.last_activity_at';

    public const KEY = 'idle.key';

    public static function start(Request $request): void
    {
        $request->session()->put(self::KEY, Str::random(24));
        self::touch($request);
    }

    public static function touch(Request $request): void
    {
        $request->session()->put(self::LAST_ACTIVITY, now()->timestamp);
        if ($key = $request->session()->get(self::KEY)) {
            // Un ancien appel de polling ne doit pas écraser une activité plus récente.
            Cache::put('session-human-activity:'.$key, now()->timestamp,
                max(1, (int) config('session.idle_timeout', 30)) * 60);
        }
    }

    public static function expiresAt(Request $request): int
    {
        $last = (int) $request->session()->get(self::LAST_ACTIVITY, now()->timestamp);
        if ($key = $request->session()->get(self::KEY)) {
            $last = max($last, (int) Cache::get('session-human-activity:'.$key, 0));
        }

        return $last + max(1, (int) config('session.idle_timeout', 30)) * 60;
    }

    public static function forget(Request $request): void
    {
        if ($key = $request->session()->get(self::KEY)) {
            Cache::forget('session-human-activity:'.$key);
        }
    }
}
