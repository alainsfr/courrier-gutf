<?php

namespace Database\Seeders;

use App\Models\Bureau;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        $bureauIds = Bureau::query()->pluck('id', 'code');

        $users = [
            [
                'email' => 'agent.courrier@gutf.local',
                'name' => 'Agent Courrier',
                'role' => 'agent_courrier',
                'bureau_code' => 'accueil',
            ],
            [
                'email' => 'agent.traitement@gutf.local',
                'name' => 'Agent Traitement',
                'role' => 'agent_traitement',
                'bureau_code' => 'livre_foncier',
            ],
            [
                'email' => 'validateur@gutf.local',
                'name' => 'Validateur',
                'role' => 'validateur',
                'bureau_code' => 'visas_controles',
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
                    'bureau_id' => $bureauIds[$u['bureau_code']] ?? null,
                ]
            );

            if (! $user->hasRole($u['role'])) {
                $user->assignRole($u['role']);
            }
        }
    }
}
