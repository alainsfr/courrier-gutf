<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'web';

        $permissions = [
            // Courrier
            'courrier.view',
            'courrier.create',
            'courrier.assign',
            'courrier.process',
            'courrier.attach',
            'courrier.detach',
            'courrier.validate',
            'courrier.archive',
            'courrier.export', // NEW

            // Admin / système
            'user.manage',
            'settings.manage',
        ];

        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm, $guard);
        }

        $roles = [
            'admin' => $permissions,

            'agent_courrier' => [
                'courrier.view',
                'courrier.create',
                'courrier.assign',
                'courrier.attach',
                'courrier.detach',
                'courrier.export',
            ],

            'agent_traitement' => [
                'courrier.view',
                'courrier.process',
                'courrier.attach',
                'courrier.detach',
            ],

            'validateur' => [
                'courrier.view',
                'courrier.validate',
            ],

            'consultation' => [
                'courrier.view',
                // 'courrier.export', // optionnel
            ],
        ];

        foreach ($roles as $roleName => $perms) {
            $role = Role::findOrCreate($roleName, $guard);
            $role->syncPermissions($perms);
        }
    }
}
