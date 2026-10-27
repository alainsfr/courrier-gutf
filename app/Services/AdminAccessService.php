<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminAccessService
{
    public function change(User $target, callable $operation, ?string $role = null, ?bool $active = null, bool $delete = false, bool $toggle = false): void
    {
        DB::transaction(function () use ($target, $operation, $role, $active, $delete, $toggle) {
            // Serialize account changes before counting administrators, including concurrent demotions.
            $users = User::with('roles')->orderBy('id')->lockForUpdate()->get();
            $current = $users->firstWhere('id', $target->id);
            abort_unless($current, 404);
            $losesAccess = $delete || $active === false || ($toggle && $current->is_active) || ($role !== null && $role !== 'super-admin');
            if ($current->is_active && $current->hasRole('super-admin') && $losesAccess
                && $users->filter(fn (User $user) => $user->is_active && $user->hasRole('super-admin'))->count() <= 1) {
                throw ValidationException::withMessages(['role' => __('users.last_super_admin')]);
            }
            $operation($current);
        }, 3);
    }
}
