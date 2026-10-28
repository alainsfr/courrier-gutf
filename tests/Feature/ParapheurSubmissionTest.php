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

    public function test_read_only_user_cannot_request_a_visa_even_when_the_courrier_is_visible(): void
    {
        $this->agent->syncRoles(['consultation']);
        $this->actingAs($this->agent)->get(route('courriers.show', $this->courrier))->assertOk()
            ->assertDontSee('action="'.route('courriers.parapheur.submit', $this->courrier).'"', false);
        $this->post(route('courriers.parapheur.submit', $this->courrier), ['validator_id' => $this->directeur->id])->assertForbidden();
        $this->assertDatabaseCount('parapheur_visas', 0);
    }

    public function test_disabled_validator_is_rejected(): void
    {
        $this->directeur->update(['is_active' => false]);
        $this->actingAs($this->agent)->post(route('courriers.parapheur.submit', $this->courrier), ['validator_id' => $this->directeur->id])
            ->assertSessionHasErrors('validator_id');
        $this->assertDatabaseCount('parapheur_visas', 0);
    }

    public function test_repeated_submission_has_only_one_pending_visa(): void
    {
        $this->actingAs($this->agent)->post(route('courriers.parapheur.submit', $this->courrier), ['validator_id' => $this->directeur->id])->assertRedirect();
        $this->post(route('courriers.parapheur.submit', $this->courrier), ['validator_id' => $this->directeur->id])->assertSessionHasErrors('validator_id');
        $this->assertDatabaseCount('parapheur_visas', 1);
    }

    public function test_even_an_administrator_cannot_submit_an_archived_dossier(): void
    {
        $this->courrier->update(['archived_at' => now()]);
        $this->agent->assignRole('super-admin');
        $this->actingAs($this->agent)->post(route('courriers.parapheur.submit', $this->courrier), ['validator_id' => $this->directeur->id])->assertUnprocessable();
        $this->assertDatabaseCount('parapheur_visas', 0);
    }
}
