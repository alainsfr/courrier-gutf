<?php

namespace Tests\Feature;

use App\Models\Bureau;
use App\Models\User;
use Database\Seeders\BureauSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_agent_created_by_admin_can_access_the_application_with_the_assigned_role_and_office(): void
    {
        $this->seed([RolesAndPermissionsSeeder::class, BureauSeeder::class]);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('super-admin');
        $office = Bureau::where('code', 'livre_foncier')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Nouvel agent', 'email' => 'new.agent@example.test', 'locale' => 'fr',
            'role' => 'agent_traitement', 'bureau_id' => $office->id, 'is_active' => '1',
            'password' => 'AccountTest!2026', 'password_confirmation' => 'AccountTest!2026',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));
        $agent = User::where('email', 'new.agent@example.test')->sole();
        $this->assertNotNull($agent->email_verified_at);
        $this->assertTrue($agent->is_active);
        $this->assertTrue($agent->hasRole('agent_traitement'));
        $this->assertSame($office->id, $agent->bureau_id);
        $this->assertTrue(Hash::check('AccountTest!2026', $agent->getAuthPassword()));
        $this->actingAs($agent)->withSession(['password_hash_web' => $agent->getAuthPassword()])
            ->get(route('dashboard'))->assertOk();
    }
}
