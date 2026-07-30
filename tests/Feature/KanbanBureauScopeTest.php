<?php

namespace Tests\Feature;

use App\Models\Bureau;
use App\Models\Courrier;
use App\Models\User;
use App\Models\WorkflowEtape;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class KanbanBureauScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_traitement_agent_only_sees_traitement_column(): void
    {
        $accueil = Bureau::create(['code' => 'accueil', 'nom' => 'Accueil', 'is_active' => true, 'ordre' => 1]);
        $visas = Bureau::create(['code' => 'visas', 'nom' => 'Visas', 'is_active' => true, 'ordre' => 2]);
        $livre = Bureau::create(['code' => 'livre', 'nom' => 'Livre Foncier', 'is_active' => true, 'ordre' => 3]);

        $etapeReception = WorkflowEtape::create([
            'ordre' => 1,
            'libelle' => 'Réception',
            'bureau_id' => $accueil->id,
            'delai_jours' => 1,
            'is_rejectable' => false,
        ]);
        $etapeAnalyse = WorkflowEtape::create([
            'ordre' => 2,
            'libelle' => 'Analyse',
            'bureau_id' => $visas->id,
            'delai_jours' => 2,
            'is_rejectable' => true,
        ]);
        $etapeTraitement = WorkflowEtape::create([
            'ordre' => 3,
            'libelle' => 'Traitement',
            'bureau_id' => $livre->id,
            'delai_jours' => 5,
            'is_rejectable' => true,
        ]);

        $agent = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'bureau_id' => $livre->id,
        ]);
        $agent->assignRole('agent_traitement');

        $creator = User::factory()->create(['email_verified_at' => now(), 'is_active' => true]);
        $creator->assignRole('agent_courrier');

        Courrier::create([
            'type' => 'entrant',
            'year' => (int) now()->year,
            'seq' => 1,
            'reference_interne' => 'GUTF-ENT-'.now()->year.'-000001',
            'objet' => 'Pli traitement',
            'created_by_user_id' => $creator->id,
            'assigned_to_user_id' => $agent->id,
            'bureau_actuel_id' => $livre->id,
            'workflow_etape_id' => $etapeTraitement->id,
        ]);

        $response = $this->actingAs($agent)->get(route('courriers.kanban'));

        $response->assertOk();
        $response->assertSee('Traitement');
        $this->assertSame(1, $response->viewData('etapes')->count());
        $this->assertSame($etapeTraitement->id, $response->viewData('etapes')->first()->id);
        $this->assertFalse(
            $response->viewData('etapes')->contains(fn ($e) => $e->libelle === 'Réception')
        );
        $this->assertFalse(
            $response->viewData('etapes')->contains(fn ($e) => $e->libelle === 'Analyse')
        );
    }

    public function test_visas_agent_sees_analyse_and_validation_columns(): void
    {
        $visas = Bureau::create(['code' => 'visas2', 'nom' => 'Visas', 'is_active' => true, 'ordre' => 2]);
        $livre = Bureau::create(['code' => 'livre2', 'nom' => 'Livre', 'is_active' => true, 'ordre' => 3]);

        WorkflowEtape::create([
            'ordre' => 2,
            'libelle' => 'Analyse',
            'bureau_id' => $visas->id,
            'delai_jours' => 2,
            'is_rejectable' => true,
        ]);
        WorkflowEtape::create([
            'ordre' => 3,
            'libelle' => 'Traitement',
            'bureau_id' => $livre->id,
            'delai_jours' => 5,
            'is_rejectable' => true,
        ]);
        WorkflowEtape::create([
            'ordre' => 4,
            'libelle' => 'Validation',
            'bureau_id' => $visas->id,
            'delai_jours' => 2,
            'is_rejectable' => true,
        ]);

        $agent = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'bureau_id' => $visas->id,
        ]);
        $agent->assignRole('validateur');

        $response = $this->actingAs($agent)->get(route('courriers.kanban'));

        $response->assertOk();
        $this->assertSame(2, $response->viewData('etapes')->count());
        $this->assertEqualsCanonicalizing(
            ['Analyse', 'Validation'],
            $response->viewData('etapes')->pluck('libelle')->all()
        );
    }
}
