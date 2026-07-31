<?php

namespace Tests\Feature;

use App\Models\Bureau;
use App\Models\Courrier;
use App\Models\ParapheurVisa;
use App\Models\User;
use App\Models\WorkflowEtape;
use Database\Seeders\BureauSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowEtapesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ParapheurSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    private User $directeur;

    private Courrier $courrier;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(BureauSeeder::class);
        $this->seed(WorkflowEtapesSeeder::class);
        Role::findOrCreate('super-admin', 'web');

        $accueilId = WorkflowEtape::query()->where('ordre', 1)->value('bureau_id')
            ?: Bureau::query()->where('code', 'accueil')->value('id');
        $etape = WorkflowEtape::query()->where('ordre', 1)->firstOrFail();

        $this->agent = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'bureau_id' => $accueilId,
        ]);
        $this->agent->assignRole('agent_courrier');

        $this->directeur = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'bureau_id' => $accueilId,
        ]);
        $this->directeur->assignRole('directeur');

        $this->courrier = Courrier::query()->create([
            'type' => 'sortant',
            'year' => (int) now()->format('Y'),
            'seq' => 77,
            'reference_interne' => 'GUTF-SOR-2026-000077',
            'objet' => 'Soumission parapheur',
            'created_by_user_id' => $this->agent->id,
            'bureau_actuel_id' => $accueilId,
            'workflow_etape_id' => $etape->id,
            'date_envoi' => now()->toDateString(),
        ]);
    }

    public function test_agent_can_submit_courrier_to_parapheur(): void
    {
        $response = $this->actingAs($this->agent)->post(
            route('courriers.parapheur.submit', $this->courrier),
            ['validator_id' => $this->directeur->id]
        );

        $response->assertRedirect(route('courriers.show', $this->courrier))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('parapheur_visas', [
            'courrier_id' => $this->courrier->id,
            'validator_id' => $this->directeur->id,
            'status' => ParapheurVisa::STATUS_PENDING,
        ]);
    }
}
