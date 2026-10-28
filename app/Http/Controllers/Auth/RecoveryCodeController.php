<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AccountRecoveryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class RecoveryCodeController extends Controller
{
    public function create()
    {
        return response()->view('auth.recovery-code')->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }

    public function store(Request $request, AccountRecoveryService $recovery)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'recovery_code' => ['required', 'string', 'max:80'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        if (! $recovery->redeem($data['email'], $data['recovery_code'], $data['password'])) {
            return back()->withInput($request->only('email'))->withErrors(['recovery_code' => __('recovery.invalid')]);
        }

        return redirect()->route('login')->with('status', __('recovery.success'));
    }
}
