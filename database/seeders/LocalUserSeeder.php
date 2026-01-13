<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LocalUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'it@syscom-sarl.com'],
            [
                'name' => 'Admin SYSCOM',
                'password' => Hash::make('123@Webpowerlab'),
                'locale' => 'fr',
                'email_verified_at' => now(),
            ]
        );

        // Spatie role assign (idempotent)
        if (! $user->hasRole('admin')) {
            $user->assignRole('admin');
        }
    }
}
