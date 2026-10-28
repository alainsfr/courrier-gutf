<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SignaturePinController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pin_password' => ['required', 'current_password'],
            'signature_pin' => ['required', 'digits:4'],
            'signature_pin_confirmation' => ['required', 'same:signature_pin'],
        ], [
            'signature_pin.required' => __('profile.pin_required'),
            'signature_pin.digits' => __('profile.pin_digits'),
            'signature_pin_confirmation.same' => __('profile.pin_confirmation'),
        ]);

        DB::transaction(function () use ($request, $validated) {
            $user = User::lockForUpdate()->findOrFail($request->user()->id);
            // Recheck under the lock in case the password changed concurrently.
            abort_unless($user->is_active && Hash::check($validated['pin_password'], $user->password), 403);
            $user->forceFill([
                'signature_pin' => Hash::make($validated['signature_pin']),
                'signature_pin_attempts' => 0,
                'signature_pin_locked_until' => null,
            ])->save();
        }, 5);

        return redirect()
            ->route('profile.edit')
            ->with('status', 'signature-pin-updated');
    }
}
