<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SignaturePinController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'signature_pin' => ['required', 'digits:4'],
            'signature_pin_confirmation' => ['required', 'same:signature_pin'],
        ], [
            'signature_pin.required' => __('profile.pin_required'),
            'signature_pin.digits' => __('profile.pin_digits'),
            'signature_pin_confirmation.same' => __('profile.pin_confirmation'),
        ]);

        $user = $request->user();
        $user->forceFill([
            'signature_pin' => Hash::make($validated['signature_pin']),
        ])->save();

        return redirect()
            ->route('profile.edit')
            ->with('status', 'signature-pin-updated');
    }
}
