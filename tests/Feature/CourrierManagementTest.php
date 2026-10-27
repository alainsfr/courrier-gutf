<?php

namespace Tests\Feature;

use App\Models\Bureau;
use App\Models\Courrier;
use App\Models\User;
use App\Models\WorkflowEtape;
use App\Services\CourrierManagementService;
use Database\Seeders\BureauSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowEtapesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourrierManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $registrar;
    private User $worker;
    private User $colleague;
    private Courrier $courrier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([BureauSeeder::class, WorkflowEtapesSeeder::class, RolesAndPermissionsSeeder::class]);
        $this->registrar = $this->actor('agent_courrier', 'accueil');
        $this->worker = $this->actor('agent_traitement', 'enregistrement_fiscal');
        $this->colleague = $this->actor('agent_traitement', 'enregistrement_fiscal');
        $this->courrier = Courrier::create([
            'type' => 'entrant', 'year' => 2026, 'seq' => 1, 'reference_interne' => 'GUTF-ENT-2026-000001',
            'objet' => 'Objet initial', 'created_by_user_id' => $this->registrar->id,
            'bureau_actuel_id' => $this->worker->bureau_id, 'workflow_etape_id' => WorkflowEtape::where('ordre', 2)->value('id'),
        ]);
    }

    private function actor(string $role, string $office): User
    {
        $user = User::factory()->create(['is_active' => true, 'locale' => 'fr', 'bureau_id' => Bureau::where('code', $office)->value('id')]);
        $user->assignRole($role);
        return $user;
    }

    private function version(): string
    {
        return app(CourrierManagementService::class)->version($this->courrier->fresh());
    }

    public function test_claim_is_traced_idempotent_and_cannot_be_stolen(): void
    {
        $this->actingAs($this->worker)->post(route('courriers.claim', $this->courrier))->assertRedirect();
        $this->post(route('courriers.claim', $this->courrier))->assertRedirect();
        $this->assertSame($this->worker->id, $this->courrier->fresh()->assigned_to_user_id);
        $this->assertSame(1, $this->courrier->histories()->where('action', 'claimed')->count());
        $this->actingAs($this->colleague)->withSession(['password_hash_web' => $this->colleague->getAuthPassword()])
            ->post(route('courriers.claim', $this->courrier))->assertForbidden();
    }

    public function test_starting_from_kanban_also_identifies_the_responsible_agent(): void
    {
        $pending = $this->actingAs($this->worker)->get(route('dashboard'))->assertOk();
        $this->assertSame(1, $pending->viewData('kpiPending'));
        $this->assertSame(0, $pending->viewData('kpiInProgress'));
        $this->postJson(route('courriers.transfer', $this->courrier), [
            'nouvelle_etape_id' => WorkflowEtape::where('ordre', 3)->value('id'), 'action_type' => 'forward',
        ])->assertOk();
        $this->assertSame($this->worker->id, $this->courrier->fresh()->assigned_to_user_id);
        $this->assertTrue($this->courrier->fresh()->isAtOrdre(3));
        $this->assertTrue($this->worker->can('attach', $this->courrier->fresh()));
        $processing = $this->get(route('dashboard'))->assertOk();
        $this->assertSame(0, $processing->viewData('kpiPending'));
        $this->assertSame(1, $processing->viewData('kpiInProgress'));
    }

    public function test_registrar_can_correct_unclaimed_mail_with_a_reason_and_an_audit_record(): void
    {
        $this->actingAs($this->registrar)->get(route('courriers.edit', $this->courrier))->assertOk()->assertSee('Motif du changement');
        $this->put(route('courriers.update', $this->courrier), [
            'objet' => 'Objet corrigé', 'reason' => 'Correction de saisie', 'version' => $this->version(),
            'type' => 'sortant', 'reference_interne' => 'FORGED', 'bureau_actuel_id' => $this->registrar->bureau_id,
        ])->assertRedirect(route('courriers.show', $this->courrier));
        $updated = $this->courrier->fresh();
        $this->assertSame('Objet corrigé', $updated->objet);
        $this->assertSame('entrant', $updated->type);
        $this->assertSame('GUTF-ENT-2026-000001', $updated->reference_interne);
        $this->assertSame($this->worker->bureau_id, $updated->bureau_actuel_id);
        $history = $updated->histories()->where('action', 'details_updated')->sole();
        $this->assertSame('Objet initial', $history->metadata['before']['objet']);
        $this->assertSame('Objet corrigé', $history->metadata['after']['objet']);
        $this->assertSame('Correction de saisie', $history->note_metier);
        $this->assertSame($this->registrar->id, $history->actor_user_id);
    }

    public function test_correction_requires_a_reason_and_refuses_stale_forms(): void
    {
        $version = $this->version();
        $this->actingAs($this->registrar)->put(route('courriers.update', $this->courrier), [
            'objet' => 'Invalid change', 'version' => $version,
        ])->assertSessionHasErrors('reason');
        $this->courrier->update(['objet' => 'Correction concurrente']);
        $this->put(route('courriers.update', $this->courrier), [
            'objet' => 'Stale change', 'reason' => 'Correction de saisie', 'version' => $version,
        ])->assertSessionHasErrors('version');
        $this->assertSame('Correction concurrente', $this->courrier->fresh()->objet);
    }

    public function test_claimed_mail_can_only_be_corrected_by_the_responsible_agent_or_admin(): void
    {
        $this->courrier->update(['assigned_to_user_id' => $this->worker->id]);
        foreach ([$this->registrar, $this->colleague, $this->actor('consultation', 'enregistrement_fiscal')] as $user) {
            $this->actingAs($user)->withSession(['password_hash_web' => $user->getAuthPassword()])
                ->get(route('courriers.edit', $this->courrier))->assertForbidden();
        }
        $this->actingAs($this->worker)->withSession(['password_hash_web' => $this->worker->getAuthPassword()])
            ->get(route('courriers.edit', $this->courrier))->assertOk();
    }

    public function test_wrong_office_can_be_corrected_before_claim_and_new_office_is_notified(): void
    {
        $newWorker = $this->actor('agent_traitement', 'livre_foncier');
        $this->actingAs($this->registrar)->get(route('courriers.reassignment', $this->courrier))->assertOk();
        $this->post(route('courriers.reassign', $this->courrier), [
            'bureau_actuel_id' => $newWorker->bureau_id, 'assigned_to_user_id' => $newWorker->id,
            'reason' => 'Bureau choisi par erreur', 'version' => $this->version(),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($newWorker->bureau_id, $this->courrier->fresh()->bureau_actuel_id);
        $this->assertSame($newWorker->id, $this->courrier->fresh()->assigned_to_user_id);
        $history = $this->courrier->histories()->where('action', 'reassigned')->sole();
        $this->assertSame('Enregistrement Fiscal', $history->metadata['before']['bureau']);
        $this->assertSame('Livre Foncier', $history->metadata['after']['bureau']);
        $this->assertSame('Bureau choisi par erreur', $history->note_metier);
        $this->assertSame(1, $newWorker->notifications()->count());
    }

    public function test_office_change_in_progress_restarts_pending_and_clears_the_previous_assignee(): void
    {
        $admin = $this->actor('super-admin', 'accueil');
        $this->courrier->update(['assigned_to_user_id' => $this->worker->id, 'workflow_etape_id' => WorkflowEtape::where('ordre', 3)->value('id')]);
        $this->actingAs($admin)->post(route('courriers.reassign', $this->courrier), [
            'bureau_actuel_id' => Bureau::where('code', 'livre_foncier')->value('id'),
            'reason' => 'Correction exceptionnelle', 'version' => $this->version(),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertTrue($this->courrier->fresh()->isAtOrdre(2));
        $this->assertNull($this->courrier->fresh()->assigned_to_user_id);
    }

    public function test_reassignment_rejects_read_only_agents_wrong_offices_and_stale_forms(): void
    {
        $reader = $this->actor('consultation', 'enregistrement_fiscal');
        $base = ['bureau_actuel_id' => $this->worker->bureau_id, 'reason' => 'Nouvelle affectation', 'version' => $this->version()];
        $this->actingAs($this->registrar)->post(route('courriers.reassign', $this->courrier), $base + ['assigned_to_user_id' => $reader->id])
            ->assertSessionHasErrors('assigned_to_user_id');
        $this->post(route('courriers.reassign', $this->courrier), $base + ['assigned_to_user_id' => $this->registrar->id])
            ->assertSessionHasErrors('assigned_to_user_id');
        $this->actingAs($this->worker)->withSession(['password_hash_web' => $this->worker->getAuthPassword()])
            ->post(route('courriers.reassign', $this->courrier), $base)->assertForbidden();
        $this->courrier->update(['objet' => 'Changé entre-temps']);
        $this->actingAs($this->registrar)->withSession(['password_hash_web' => $this->registrar->getAuthPassword()])
            ->post(route('courriers.reassign', $this->courrier), $base)->assertSessionHasErrors('version');
    }

    public function test_old_assignment_endpoint_cannot_silently_replace_an_agent_or_change_closed_mail(): void
    {
        $admin = $this->actor('super-admin', 'accueil');
        $this->courrier->update(['assigned_to_user_id' => $this->worker->id]);
        $this->actingAs($admin)->post(route('courriers.assign', $this->courrier), ['assigned_to_user_id' => $this->colleague->id])
            ->assertSessionHasErrors('reason');
        $this->courrier->update(['workflow_etape_id' => WorkflowEtape::where('ordre', 5)->value('id')]);
        $this->post(route('courriers.assign', $this->courrier), ['assigned_to_user_id' => $this->colleague->id, 'reason' => 'Changement tardif'])
            ->assertUnprocessable();
        $this->put(route('courriers.update', $this->courrier), ['objet' => 'Changed', 'reason' => 'Changement tardif', 'version' => $this->version()])
            ->assertSessionHasErrors('courrier');
    }
}
