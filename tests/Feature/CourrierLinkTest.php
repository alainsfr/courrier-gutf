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
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CourrierLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    private Courrier $parent;

    private Courrier $child;

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

        $this->parent = Courrier::query()->create([
            'type' => 'entrant',
            'year' => (int) now()->format('Y'),
            'seq' => 10,
            'reference_interne' => 'GUTF-ENT-2026-000010',
            'objet' => 'Dossier parent',
            'created_by_user_id' => $this->agent->id,
            'bureau_actuel_id' => $accueilId,
            'workflow_etape_id' => $etape->id,
            'date_reception' => now()->toDateString(),
        ]);

        $this->child = Courrier::query()->create([
            'type' => 'sortant',
            'year' => (int) now()->format('Y'),
            'seq' => 11,
            'reference_interne' => 'GUTF-SOR-2026-000011',
            'objet' => 'Réponse liée',
            'created_by_user_id' => $this->agent->id,
            'bureau_actuel_id' => $accueilId,
            'workflow_etape_id' => $etape->id,
            'date_envoi' => now()->toDateString(),
        ]);
    }

    public function test_agent_can_link_two_courriers_by_reference(): void
    {
        $response = $this->actingAs($this->agent)->post(
            route('courriers.links.store', $this->parent),
            ['reference' => $this->child->reference_interne]
        );

        $response->assertRedirect(route('courriers.show', $this->parent))
            ->assertSessionHas('status');

        $this->assertTrue(
            DB::table('courrier_links')
                ->where('parent_id', $this->parent->id)
                ->where('child_id', $this->child->id)
                ->exists()
        );

        $this->assertTrue(
            $this->parent->fresh()->linkedCourriers->contains('id', $this->child->id)
        );
    }
}
