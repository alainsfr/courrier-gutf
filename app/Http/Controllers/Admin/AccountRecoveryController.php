<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountRecovery;
use App\Models\User;
use App\Services\AccountRecoveryService;
use Illuminate\Http\Request;

class AccountRecoveryController extends Controller
{
    public function show(User $user)
    {
        return $this->page($user);
    }

    public function store(Request $request, User $user, AccountRecoveryService $recovery)
    {
        $data = $request->validate(['admin_password' => ['required', 'string'], 'identity_checked' => ['accepted']]);
        $issued = $recovery->issue($user, $request->user(), $data['admin_password']);

        return $this->page($user->fresh(), $issued);
    }

    private function page(User $user, array $issued = [])
    {
        return response()->view('admin.users.recovery', [
            'user' => $user, 'code' => $issued['code'] ?? null, 'grant' => $issued['grant'] ?? null,
            'history' => AccountRecovery::with('issuer:id,name')->where('user_id', $user->id)->latest('id')->limit(20)->get(),
        ])->header('Cache-Control', 'no-store, private')->header('Pragma', 'no-cache')->header('Referrer-Policy', 'no-referrer');
    }
}
