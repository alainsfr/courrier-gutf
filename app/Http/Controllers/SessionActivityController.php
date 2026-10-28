<?php

namespace App\Http\Controllers;

use App\Services\IdleSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionActivityController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        return response()->json(['expires_at' => IdleSession::expiresAt($request), 'server_now' => now()->timestamp])
            ->header('Cache-Control', 'no-store, private');
    }

    public function update(Request $request): JsonResponse
    {
        IdleSession::touch($request);

        return $this->status($request);
    }
}
