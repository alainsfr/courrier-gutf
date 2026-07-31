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
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ParapheurTest extends TestCase
{
    use RefreshDatabase;

    private User $validator;

    private User $other;

    private Courrier $courrier;

    private ParapheurVisa $visa;

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

        $this->validator = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'bureau_id' => $accueilId,
        ]);
        $this->validator->assignRole('validateur');

        $this->other = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'bureau_id' => $accueilId,
        ]);
        $this->other->assignRole('agent_courrier');

        $this->courrier = Courrier::query()->create([
            'type' => 'sortant',
            'year' => (int) now()->format('Y'),
            'seq' => 42,
            'reference_interne' => 'GUTF-SOR-2026-000042',
            'objet' => 'Courrier départ à viser',
            'created_by_user_id' => $this->other->id,
            'bureau_actuel_id' => $accueilId,
            'workflow_etape_id' => $etape->id,
            'date_envoi' => now()->toDateString(),
        ]);

        $this->visa = ParapheurVisa::query()->create([
            'courrier_id' => $this->courrier->id,
            'validator_id' => $this->validator->id,
            'status' => ParapheurVisa::STATUS_PENDING,
        ]);
    }

    public function test_validator_sees_pending_visa_in_parapheur_index(): void
    {
        $this->actingAs($this->validator)
            ->get(route('parapheur.index'))
            ->assertOk()
            ->assertSee('GUTF-SOR-2026-000042', false)
            ->assertSee(__('parapheur.examine'), false);

        $this->actingAs($this->other)
            ->get(route('parapheur.index'))
            ->assertOk()
            ->assertDontSee('GUTF-SOR-2026-000042', false);
    }

    public function test_validator_can_approve_pending_visa(): void
    {
        $this->actingAs($this->validator)
            ->post(route('parapheur.approve', $this->visa))
            ->assertRedirect(route('courriers.show', $this->courrier));

        $this->visa->refresh();
        $this->assertSame(ParapheurVisa::STATUS_APPROVED, $this->visa->status);
        $this->assertNotNull($this->visa->signed_at);
    }

    public function test_validator_can_reject_pending_visa_with_comment(): void
    {
        $this->actingAs($this->validator)
            ->post(route('parapheur.reject', $this->visa), [
                'comment' => 'Pièce manquante',
            ])
            ->assertRedirect(route('courriers.show', $this->courrier));

        $this->visa->refresh();
        $this->assertSame(ParapheurVisa::STATUS_REJECTED, $this->visa->status);
        $this->assertSame('Pièce manquante', $this->visa->comment);
        $this->assertNotNull($this->visa->signed_at);
    }

    public function test_show_displays_visa_action_card_for_pending_validator(): void
    {
        $this->actingAs($this->validator)
            ->get(route('courriers.show', $this->courrier))
            ->assertOk()
            ->assertSee(__('parapheur.action_title'), false)
            ->assertSee(__('parapheur.approve'), false);
    }

    public function test_other_user_cannot_approve_visa(): void
    {
        $this->actingAs($this->other)
            ->post(route('parapheur.approve', $this->visa))
            ->assertForbidden();

        $this->assertSame(ParapheurVisa::STATUS_PENDING, $this->visa->fresh()->status);
    }
}
