<?php

namespace Tests\Feature;

use App\Models\Bureau;
use App\Models\Courrier;
use App\Models\CourrierComment;
use App\Models\User;
use App\Models\WorkflowEtape;
use Database\Seeders\BureauSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowEtapesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CourrierCommentTest extends TestCase
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

        $this->agent = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'bureau_id' => $accueilId,
        ]);
        $this->agent->assignRole('agent_courrier');

        $etape = WorkflowEtape::query()->where('ordre', 1)->firstOrFail();

        $this->courrier = Courrier::query()->create([
            'type' => 'entrant',
            'year' => (int) now()->format('Y'),
            'seq' => 1,
            'reference_interne' => 'GUTF-ENT-2026-000099',
            'objet' => 'Dossier notes internes',
            'created_by_user_id' => $this->agent->id,
            'bureau_actuel_id' => $accueilId,
            'workflow_etape_id' => $etape->id,
            'date_reception' => now()->toDateString(),
        ]);
    }

    public function test_agent_can_post_internal_comment_on_courrier(): void
    {
        $response = $this->actingAs($this->agent)->post(
            route('courriers.comments.store', $this->courrier),
            ['body' => 'Contrôler le TF avant transfert.']
        );

        $response->assertRedirect(route('courriers.show', $this->courrier))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('courrier_comments', [
            'courrier_id' => $this->courrier->id,
            'user_id' => $this->agent->id,
            'body' => 'Contrôler le TF avant transfert.',
        ]);

        $this->actingAs($this->agent)
            ->get(route('courriers.show', $this->courrier))
            ->assertOk()
            ->assertSee(__('courrier.comments_title'), false)
            ->assertSee('Contrôler le TF avant transfert.', false);
    }

    public function test_comment_body_is_required(): void
    {
        $this->actingAs($this->agent)
            ->from(route('courriers.show', $this->courrier))
            ->post(route('courriers.comments.store', $this->courrier), ['body' => ''])
            ->assertRedirect(route('courriers.show', $this->courrier))
            ->assertSessionHasErrors('body');

        $this->assertSame(0, CourrierComment::query()->count());
    }
}
