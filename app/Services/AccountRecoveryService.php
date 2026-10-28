<?php

namespace App\Services;

use App\Models\AccountRecovery;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccountRecoveryService
{
    public const MINUTES = 15;

    public const MAX_ATTEMPTS = 5;

    public function fingerprint(User $user): string
    {
        return hash('sha256', $user->getAuthPassword()."\0".$user->email);
    }

    /** The plaintext code is returned once; never persist it in logs or session flash. */
    public function issue(User $target, ?User $issuer = null, ?string $adminPassword = null): array
    {
        return DB::transaction(function () use ($target, $issuer, $adminPassword) {
            // Same lock order as administration; serializes concurrent code generation/redemption.
            $users = User::whereIn('id', array_filter([$target->id, $issuer?->id]))->orderBy('id')->lockForUpdate()->get();
            $current = $users->firstWhere('id', $target->id);
            abort_unless($current, 404);
            if ($issuer) {
                $actor = $users->firstWhere('id', $issuer->id);
                abort_unless($actor?->is_active && $actor->hasRole('super-admin'), 403);
                if (! Hash::check($adminPassword ?? '', $actor->getAuthPassword())) {
                    throw ValidationException::withMessages(['admin_password' => __('recovery.admin_password_invalid')]);
                }
            }
            if (! $current->is_active) {
                throw ValidationException::withMessages(['user' => __('recovery.inactive')]);
            }
            AccountRecovery::where('user_id', $current->id)->whereNull('used_at')->whereNull('revoked_at')->update(['revoked_at' => now()]);
            $raw = strtoupper(bin2hex(random_bytes(16)));
            $grant = AccountRecovery::create([
                'user_id' => $current->id, 'issued_by' => $issuer?->id, 'source' => $issuer ? 'admin' : 'console',
                'code_hash' => hash('sha256', $raw), 'credential_fingerprint' => $this->fingerprint($current),
                'expires_at' => now()->addMinutes(self::MINUTES),
            ]);

            return ['code' => implode('-', str_split($raw, 4)), 'grant' => $grant];
        }, 3);
    }

    public function redeem(string $email, string $code, string $password): bool
    {
        $raw = preg_replace('/[\s-]+/', '', strtoupper($code));
        $result = DB::transaction(function () use ($email, $raw, $password) {
            $user = User::where('email', $email)->lockForUpdate()->first();
            $grant = $user ? AccountRecovery::where('user_id', $user->id)->latest('id')->lockForUpdate()->first() : null;
            $matches = hash_equals($grant?->code_hash ?? str_repeat('0', 64), hash('sha256', $raw));
            if (! $user?->is_active || ! $grant || $grant->used_at || $grant->revoked_at
                || now()->greaterThanOrEqualTo($grant->expires_at) || $grant->attempts >= self::MAX_ATTEMPTS
                || ! hash_equals($grant->credential_fingerprint, $this->fingerprint($user))) {
                return null;
            }
            if (! $matches) {
                $grant->attempts++;
                if ($grant->attempts >= self::MAX_ATTEMPTS) $grant->revoked_at = now();
                $grant->save();

                return null; // Commit failed-attempt accounting rather than rolling it back with an exception.
            }
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            Password::broker()->deleteToken($user);
            $grant->forceFill(['used_at' => now()])->save();

            return $user;
        }, 3);
        if ($result) event(new PasswordReset($result));

        return $result !== null;
    }
}
