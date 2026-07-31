<?php

namespace Tests\Feature;

use App\Models\Bureau;
use App\Models\Courrier;
use App\Models\User;
use App\Models\WorkflowEtape;
use Database\Seeders\BureauSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowEtapesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BordereauPrintTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    private Courrier $courrier;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(BureauSeeder::class);
        $this->seed(WorkflowEtapesSeeder::class);

        $accueilId = WorkflowEtape::query()->where('ordre', 1)->value('bureau_id')
            ?: Bureau::query()->where('code', 'accueil')->value('id');
        $etape = WorkflowEtape::query()->where('ordre', 1)->firstOrFail();

        $this->agent = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'bureau_id' => $accueilId,
        ]);
        $this->agent->assignRole('agent_courrier');

        $this->courrier = Courrier::query()->create([
            'type' => 'entrant',
            'year' => (int) now()->format('Y'),
            'seq' => 88,
            'reference_interne' => 'GUTF-ENT-2026-000088',
            'objet' => 'Bordereau de circulation',
            'expediteur' => 'MINDCAF',
            'created_by_user_id' => $this->agent->id,
            'bureau_actuel_id' => $accueilId,
            'workflow_etape_id' => $etape->id,
            'date_reception' => now()->toDateString(),
        ]);
    }

    public function test_authenticated_agent_can_access_bordereau(): void
    {
        $response = $this->actingAs($this->agent)
            ->get(route('courriers.bordereau', $this->courrier));

        $response->assertOk()
            ->assertSee($this->courrier->reference_interne, false)
            ->assertSee(__('courrier.bordereau_circuit'), false);
    }
}
