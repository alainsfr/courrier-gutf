<?php

namespace Tests\Feature;

use App\Models\Courrier;
use App\Models\CourrierHistory;
use App\Models\User;
use App\Models\WorkflowEtape;
use App\Services\WorkflowService;
use Database\Seeders\BureauSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowEtapesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(BureauSeeder::class);
        $this->seed(WorkflowEtapesSeeder::class);

        $this->agent = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'bureau_id' => WorkflowEtape::query()->where('ordre', 1)->value('bureau_id'),
        ]);
        $this->agent->assignRole('agent_courrier');
    }

    public function test_store_assigns_reception_etape_and_bureau(): void
    {
        $etape1 = WorkflowEtape::query()->where('ordre', 1)->firstOrFail();

        $response = $this->actingAs($this->agent)->post(route('courriers.store'), [
            'type' => 'entrant',
            'objet' => 'Dossier workflow test',
            'tf' => 'TF-TEST-001',
            'proprietaire' => 'Jean Dupont',
            'date_reception' => now()->toDateString(),
        ]);

        $courrier = Courrier::query()->latest('id')->first();

        $this->assertNotNull($courrier);
        $response->assertRedirect(route('courriers.show', $courrier));

        $this->assertSame((int) $etape1->id, (int) $courrier->workflow_etape_id);
        $this->assertSame((int) $etape1->bureau_id, (int) $courrier->bureau_actuel_id);
        $this->assertFalse((bool) $courrier->is_late);
        $this->assertNotNull($courrier->date_limite);
        $this->assertSame('TF-TEST-001', $courrier->tf);
        $this->assertSame('Jean Dupont', $courrier->proprietaire);

        $this->assertDatabaseHas('courrier_histories', [
            'courrier_id' => $courrier->id,
            'action' => 'created',
            'nouvelle_etape_id' => $etape1->id,
        ]);
    }

    public function test_transfer_moves_courrier_to_etape_2_and_writes_history(): void
    {
        $etape1 = WorkflowEtape::query()->where('ordre', 1)->firstOrFail();
        $etape2 = WorkflowEtape::query()->where('ordre', 2)->firstOrFail();

        $courrier = Courrier::create([
            'type' => 'entrant',
            'year' => (int) now()->year,
            'seq' => 99,
            'reference_interne' => 'GUTF-ENT-'.now()->year.'-000099',
            'objet' => 'Transfert test',
            'created_by_user_id' => $this->agent->id,
            'workflow_etape_id' => $etape1->id,
            'bureau_actuel_id' => $etape1->bureau_id,
            'is_late' => false,
            'date_limite' => now()->addWeekday()->endOfDay(),
        ]);

        $response = $this->actingAs($this->agent)->postJson(
            route('courriers.transfer', $courrier),
            [
                'nouvelle_etape_id' => $etape2->id,
                'action_type' => WorkflowService::ACTION_FORWARD,
            ]
        );

        $response->assertOk()->assertJsonPath('ok', true);

        $courrier->refresh();
        $this->assertSame((int) $etape2->id, (int) $courrier->workflow_etape_id);
        $this->assertSame((int) $etape2->bureau_id, (int) $courrier->bureau_actuel_id);

        $history = CourrierHistory::query()
            ->where('courrier_id', $courrier->id)
            ->where('action', 'workflow_transfer')
            ->latest('id')
            ->first();

        $this->assertNotNull($history);
        $this->assertSame((int) $etape1->id, (int) $history->ancienne_etape_id);
        $this->assertSame((int) $etape2->id, (int) $history->nouvelle_etape_id);
        $this->assertSame(WorkflowService::ACTION_FORWARD, $history->action_type);
    }
}
