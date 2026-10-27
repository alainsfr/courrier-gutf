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
            'courrier.update',
            'courrier.reassign',
            'courrier.assign',
            'courrier.process',
            'courrier.attach',
            'courrier.detach',
            'courrier.validate',
            'courrier.archive',
            'courrier.export',
            
            // Chat
            'chat.view',
            'chat.send',
            'chat.group.create',
            'chat.group.manage',

            // Admin / système
            'user.manage',
            'settings.manage',

            // Audit
            'auditor.view',
            'auditor.export',
        ];

        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm, $guard);
        }

        $roles = [
            // Admin = tout
            'admin' => $permissions,

            // Super-admin (accès total Spatie ; utilisateur créé par SuperAdminSeeder)
            'super-admin' => $permissions,

            // Auditor = audit uniquement (lecture + export)
            'auditor' => [
                'auditor.view',
                'auditor.export',
            ],

            'agent_courrier' => [
                'courrier.view',
                'courrier.create',
                'courrier.update',
                'courrier.reassign',
                'courrier.assign',
                'courrier.attach',
                'courrier.detach',
                'courrier.export',
                'chat.view',
                'chat.send',
                'chat.group.create',
            ],

            'agent_traitement' => [
                'courrier.view',
                'courrier.process',
                'courrier.update',
                'courrier.archive',
                'courrier.attach',
                'courrier.detach',
                'chat.view',
                'chat.send',
                'chat.group.create',
            ],

            'validateur' => [
                'courrier.view',
                'courrier.validate',
                'chat.view',
                'chat.send',
                'chat.group.create',
            ],

            'consultation' => [
                'courrier.view',
                'chat.view',
                'chat.send',
                
                // 'courrier.export', // optionnel
            ],

            // Direction : lecture + pilotage (rapports)
            'directeur' => [
                'courrier.view',
                'courrier.export',
                'chat.view',
                'chat.send',
                'auditor.view',
            ],
        ];

        foreach ($roles as $roleName => $perms) {
            $role = Role::findOrCreate($roleName, $guard);
            $role->syncPermissions($perms);
        }
    }
}
