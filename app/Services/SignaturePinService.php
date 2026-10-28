<?php

namespace App\Services;

use App\Models\ParapheurVisa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SignaturePinService
{
    public const MAX_ATTEMPTS = 5;

    public const LOCK_MINUTES = 10;

    public function approve(User $actor, ParapheurVisa $visa, string $pin): void
    {
        $error = DB::transaction(function () use ($actor, $visa, $pin) {
            $user = User::lockForUpdate()->findOrFail($actor->id);
            $current = ParapheurVisa::lockForUpdate()->findOrFail($visa->id);
            abort_unless($user->is_active && (int) $current->validator_id === (int) $user->id, 403);
            abort_unless($current->isPending(), 404);

            if ($user->signature_pin_locked_until?->isFuture()) {
                return __('parapheur.pin_locked');
            }
            if (! $user->signature_pin) {
                return __('parapheur.pin_not_set');
            }
            if (! Hash::check($pin, $user->signature_pin)) {
                $attempts = ($user->signature_pin_locked_until ? 0 : $user->signature_pin_attempts) + 1;
                $locked = $attempts >= self::MAX_ATTEMPTS;
                $user->forceFill([
                    'signature_pin_attempts' => $attempts,
                    'signature_pin_locked_until' => $locked ? now()->addMinutes(self::LOCK_MINUTES) : null,
                ])->save();

                return __($locked ? 'parapheur.pin_locked' : 'parapheur.pin_invalid');
            }

            $user->forceFill(['signature_pin_attempts' => 0, 'signature_pin_locked_until' => null])->save();
            $current->forceFill([
                'status' => ParapheurVisa::STATUS_APPROVED,
                'signed_at' => now(),
                'digital_hash' => hash('sha256', random_bytes(32)),
            ])->save();

            return null;
        }, 5);

        // Failed attempts must be committed before returning the validation error.
        if ($error !== null) {
            throw ValidationException::withMessages(['signature_pin' => $error]);
        }
    }
}
