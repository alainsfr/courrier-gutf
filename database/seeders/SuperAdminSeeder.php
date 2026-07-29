<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'web';

        // 1) Rôle super-admin (création si absent)
        $role = Role::findOrCreate('super-admin', $guard);

        // Accès total Spatie : toutes les permissions connues
        $role->syncPermissions(Permission::query()->where('guard_name', $guard)->get());

        // 2) Utilisateur Super Admin (bureau_id = null → non restreint)
        $user = User::query()->updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'bureau_id' => null,
                'email_verified_at' => now(),
                'is_active' => true,
                'locale' => 'fr',
            ]
        );

        // 3) Assignation du rôle
        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }
    }
}
