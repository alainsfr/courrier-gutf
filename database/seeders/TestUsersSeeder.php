<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'email' => 'agent.courrier@gutf.local',
                'name' => 'Agent Courrier',
                'role' => 'agent_courrier',
            ],
            [
                'email' => 'agent.traitement@gutf.local',
                'name' => 'Agent Traitement',
                'role' => 'agent_traitement',
            ],
            [
                'email' => 'validateur@gutf.local',
                'name' => 'Validateur',
                'role' => 'validateur',
            ],
        ];

        foreach ($users as $u) {
            $user = User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make('Password@123'),
                    'locale' => 'fr',
                    'email_verified_at' => now(),
                ]
            );

            if (! $user->hasRole($u['role'])) {
                $user->assignRole($u['role']);
            }
        }
    }
}
