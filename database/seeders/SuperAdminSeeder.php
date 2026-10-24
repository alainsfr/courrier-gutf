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
        $role = Role::findOrCreate('super-admin', $guard);
        $role->syncPermissions(Permission::query()->where('guard_name', $guard)->get());

        $isLocal = app()->environment(['local', 'development', 'testing']);
        $email = (string) env('SUPER_ADMIN_EMAIL', $isLocal ? 'admin@test.com' : '');
        $password = (string) env('SUPER_ADMIN_PASSWORD', $isLocal ? 'password' : '');
        $name = (string) env('SUPER_ADMIN_NAME', 'Super Admin');

        if ($email === '' || $password === '') {
            $this->command?->warn(
                'Super-admin non créé : définissez SUPER_ADMIN_EMAIL et SUPER_ADMIN_PASSWORD dans le .env.'
            );

            return;
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'bureau_id' => null,
                'email_verified_at' => now(),
                'is_active' => true,
                'locale' => 'fr',
            ]);
        }

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }
    }
}
