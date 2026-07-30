<?php

namespace Tests\Feature;

use App\Models\Bureau;
use App\Models\Courrier;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CourrierViewAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_bureau_colleague_can_view_courrier_at_same_bureau(): void
    {
        $bureau = Bureau::create([
            'code' => 'accueil',
            'nom' => 'Accueil',
            'is_active' => true,
            'ordre' => 1,
        ]);

        $creator = User::factory()->create(['email_verified_at' => now(), 'is_active' => true]);
        $creator->assignRole('agent_courrier');

        $colleague = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'bureau_id' => $bureau->id,
        ]);
        $colleague->assignRole('agent_traitement');

        $outsider = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'bureau_id' => null,
        ]);
        $outsider->assignRole('consultation');

        $courrier = Courrier::create([
            'type' => 'entrant',
            'year' => (int) now()->year,
            'seq' => 27,
            'reference_interne' => 'GUTF-ENT-'.now()->year.'-000027',
            'objet' => 'Pli bureau',
            'created_by_user_id' => $creator->id,
            'assigned_to_user_id' => null,
            'bureau_actuel_id' => $bureau->id,
        ]);

        $this->actingAs($colleague)
            ->get(route('courriers.show', $courrier))
            ->assertOk();

        $this->actingAs($outsider)
            ->get(route('courriers.show', $courrier))
            ->assertForbidden();
    }
}
