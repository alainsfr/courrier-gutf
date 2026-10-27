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
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;

class DirectOfficeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $registrar;
    private User $worker;
    private Bureau $office;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesAndPermissionsSeeder::class, BureauSeeder::class, WorkflowEtapesSeeder::class]);
        $this->office = Bureau::where('code', 'enregistrement_fiscal')->firstOrFail();
        $this->registrar = User::factory()->create([
            'is_active' => true, 'bureau_id' => Bureau::where('code', 'accueil')->value('id'),
        ]);
        $this->registrar->assignRole('agent_courrier');
        $this->worker = User::factory()->create(['is_active' => true, 'bureau_id' => $this->office->id]);
        $this->worker->assignRole('agent_traitement');
    }

    private function register(array $extra = []): Courrier
    {
        $this->actingAs($this->registrar)->withSession(['password_hash_web' => $this->registrar->getAuthPassword()])
            ->post(route('courriers.store'), array_merge([
                'type' => 'entrant', 'objet' => 'Affectation directe', 'bureau_actuel_id' => $this->office->id,
            ], $extra))->assertSessionHasNoErrors()->assertRedirect();

        return Courrier::latest('id')->firstOrFail();
    }

    public function test_registration_sends_mail_directly_to_the_chosen_office_and_notifies_its_agents(): void
    {
        $courrier = $this->register();
        $this->assertSame($this->office->id, $courrier->bureau_actuel_id);
        $this->assertTrue($courrier->isAtOrdre(2));
        $this->assertNull($courrier->assigned_to_user_id);
        $this->assertNotEmpty($this->worker->unreadNotifications);
        $this->assertSame($this->office->id, $courrier->histories()->first()->metadata['bureau_id']);
        $this->actingAs($this->worker)->get(route('courriers.show', $courrier))->assertOk()->assertSee('Prendre en charge');
    }

    public function test_the_same_office_processes_completes_and_archives_without_a_validation_detour(): void
    {
        $courrier = $this->register(['assigned_to_user_id' => $this->worker->id]);
        $this->actingAs($this->worker)->withSession(['password_hash_web' => $this->worker->getAuthPassword()]);
        $this->post(route('courriers.start_processing', $courrier))->assertRedirect();
        $this->assertTrue($courrier->fresh()->isAtOrdre(3));
        $this->assertSame($this->office->id, $courrier->fresh()->bureau_actuel_id);

        $this->post(route('courriers.complete', $courrier))->assertRedirect();
        $courrier->refresh();
        $this->assertTrue($courrier->isAtOrdre(5));
        $this->assertSame($this->office->id, $courrier->bureau_actuel_id);
        $this->assertSame($this->worker->id, $courrier->validated_by_user_id);
        $this->assertNull($courrier->date_limite);

        $this->post(route('courriers.archive', $courrier))->assertRedirect();
        $courrier->refresh();
        $this->assertTrue($courrier->isArchived());
        $this->assertSame($this->office->id, $courrier->bureau_actuel_id);
        $this->assertSame([2, 3, 5, 6], $courrier->histories()->whereNotNull('nouvelle_etape_id')->orderBy('id')->get()->pluck('nouvelleEtape.ordre')->all());
        $this->postJson(route('courriers.transfer', $courrier), [
            'nouvelle_etape_id' => WorkflowEtape::where('ordre', 5)->value('id'), 'action_type' => 'backward',
        ])->assertForbidden();
    }

    public function test_registration_rejects_missing_or_inactive_offices_and_agents_outside_the_office(): void
    {
        $this->actingAs($this->registrar);
        $payload = ['type' => 'entrant', 'objet' => 'Invalid assignment'];
        $this->post(route('courriers.store'), $payload)->assertSessionHasErrors('bureau_actuel_id');
        $inactive = Bureau::create(['code' => 'inactive', 'nom' => 'Inactive', 'is_active' => false]);
        $this->post(route('courriers.store'), $payload + ['bureau_actuel_id' => $inactive->id])
            ->assertSessionHasErrors('bureau_actuel_id');
        $this->post(route('courriers.store'), $payload + [
            'bureau_actuel_id' => $this->office->id, 'assigned_to_user_id' => $this->registrar->id,
        ])->assertSessionHasErrors('assigned_to_user_id');
        $this->assertSame(0, Courrier::count());
    }

    public function test_readers_and_agents_from_another_office_cannot_change_progress(): void
    {
        $courrier = $this->register();
        $reader = User::factory()->create(['is_active' => true, 'bureau_id' => $this->office->id]);
        $reader->assignRole('consultation');
        $outsider = User::factory()->create(['is_active' => true, 'bureau_id' => $this->registrar->bureau_id]);
        $outsider->assignRole('agent_traitement');
        foreach ([$reader, $outsider] as $user) {
            $this->actingAs($user)->withSession(['password_hash_web' => $user->getAuthPassword()])
                ->postJson(route('courriers.transfer', $courrier), [
                    'nouvelle_etape_id' => WorkflowEtape::where('ordre', 3)->value('id'), 'action_type' => 'forward',
                ])->assertForbidden();
        }
        $this->assertTrue($courrier->fresh()->isAtOrdre(2));
    }

    public function test_kanban_shows_progress_stages_for_the_responsible_office_only(): void
    {
        $courrier = $this->register();
        $response = $this->actingAs($this->worker)->get(route('courriers.kanban'))->assertOk();
        $this->assertSame([2, 3, 5, 6], $response->viewData('etapes')->pluck('ordre')->all());
        $this->assertTrue($response->viewData('courriersByEtape')->flatten()->contains('id', $courrier->id));
        $this->assertFalse($response->viewData('etapes')->contains('ordre', 4));
        $dashboard = $this->get(route('dashboard'))->assertOk();
        $this->assertSame(1, $dashboard->viewData('kpiPending'));
        $this->assertSame([2, 3, 5, 6], $dashboard->viewData('byEtape')->pluck('ordre')->values()->all());
    }

    public function test_the_responsible_agent_cannot_skip_processing_or_complete_another_agents_assignment(): void
    {
        $courrier = $this->register(['assigned_to_user_id' => $this->worker->id]);
        $this->actingAs($this->worker)->postJson(route('courriers.transfer', $courrier), [
            'nouvelle_etape_id' => WorkflowEtape::where('ordre', 5)->value('id'), 'action_type' => 'forward',
        ])->assertUnprocessable();
        $this->post(route('courriers.start_processing', $courrier))->assertRedirect();
        $this->postJson(route('courriers.transfer', $courrier), [
            'nouvelle_etape_id' => WorkflowEtape::where('ordre', 6)->value('id'), 'action_type' => 'forward',
        ])->assertUnprocessable();
        $colleague = User::factory()->create(['is_active' => true, 'bureau_id' => $this->office->id]);
        $colleague->assignRole('agent_traitement');
        $this->actingAs($colleague)->withSession(['password_hash_web' => $colleague->getAuthPassword()])
            ->post(route('courriers.complete', $courrier))->assertForbidden();
        $this->assertTrue($courrier->fresh()->isAtOrdre(3));
    }

    public function test_office_and_user_broadcast_channels_enforce_the_same_boundaries(): void
    {
        require base_path('routes/channels.php');
        $channels = Broadcast::getChannels();
        $this->assertTrue((bool) $channels['bureaux.{bureauId}']($this->worker, $this->office->id));
        $this->assertFalse((bool) $channels['bureaux.{bureauId}']($this->worker, $this->registrar->bureau_id));
        $this->assertFalse((bool) $channels['users.{userId}']($this->worker, $this->registrar->id));
        $this->worker->is_active = false;
        $this->assertFalse((bool) $channels['bureaux.{bureauId}']($this->worker, $this->office->id));
    }
}
