<?php

namespace Tests\Feature;

use App\Models\Bureau;
use App\Models\Courrier;
use App\Models\User;
use App\Models\WorkflowEtape;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CourrierRegisterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::findOrCreate('super-admin', 'web');
        $role->syncPermissions(
            \Spatie\Permission\Models\Permission::query()->where('guard_name', 'web')->get()
        );

        $this->admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'locale' => 'fr',
        ]);
        $this->admin->assignRole($role);

        $bureau = Bureau::query()->create([
            'code' => 'ACC',
            'nom' => 'Accueil',
            'color' => '#17384b',
            'ordre' => 1,
            'is_active' => true,
        ]);

        $etape = WorkflowEtape::query()->create([
            'libelle' => 'Réception',
            'ordre' => Courrier::ETAPE_RECEPTION,
            'bureau_id' => $bureau->id,
            'ui_color' => '#17384b',
            'delai_jours' => 2,
        ]);

        Courrier::query()->create([
            'type' => 'entrant',
            'year' => (int) now()->format('Y'),
            'seq' => 1,
            'reference_interne' => 'ENT-2026-0001',
            'objet' => 'Dossier mutation test',
            'expediteur' => 'Notaire Demo',
            'created_by_user_id' => $this->admin->id,
            'bureau_actuel_id' => $bureau->id,
            'workflow_etape_id' => $etape->id,
            'is_late' => true,
            'date_limite' => now()->subDay(),
            'date_reception' => now()->subDays(3)->toDateString(),
        ]);

        Courrier::query()->create([
            'type' => 'sortant',
            'year' => (int) now()->format('Y'),
            'seq' => 2,
            'reference_interne' => 'SOR-2026-0002',
            'objet' => 'Réponse administrative',
            'created_by_user_id' => $this->admin->id,
            'bureau_actuel_id' => $bureau->id,
            'workflow_etape_id' => $etape->id,
            'is_late' => false,
            'date_reception' => now()->toDateString(),
        ]);
    }

    public function test_register_index_renders_modern_ui(): void
    {
        $this->actingAs($this->admin)
            ->get(route('courriers.index'))
            ->assertOk()
            ->assertSee(__('courrier.register_title'), false)
            ->assertSee('ENT-2026-0001', false)
            ->assertSee('SOR-2026-0002', false);
    }

    public function test_register_filters_by_type_and_late(): void
    {
        $this->actingAs($this->admin)
            ->get(route('courriers.index', ['type' => 'entrant']))
            ->assertOk()
            ->assertSee('ENT-2026-0001', false)
            ->assertDontSee('SOR-2026-0002', false);

        $this->actingAs($this->admin)
            ->get(route('courriers.index', ['is_late' => '1']))
            ->assertOk()
            ->assertSee('ENT-2026-0001', false)
            ->assertDontSee('SOR-2026-0002', false);
    }

    public function test_register_search_query(): void
    {
        $this->actingAs($this->admin)
            ->get(route('courriers.index', ['q' => 'mutation']))
            ->assertOk()
            ->assertSee('ENT-2026-0001', false)
            ->assertDontSee('SOR-2026-0002', false);
    }

    public function test_register_sort_and_per_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('courriers.index', ['sort' => 'oldest', 'per_page' => 10]))
            ->assertOk()
            ->assertSee('ENT-2026-0001', false);
    }
}
