<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Bureau;
use App\Models\Courrier;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\WorkflowEtape;
use App\Models\ParapheurVisa;
use Database\Seeders\BureauSeeder;
use Database\Seeders\WorkflowEtapesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UiHarmonizationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::findOrCreate('super-admin', 'web');
        $role->syncPermissions(
            \Spatie\Permission\Models\Permission::query()->where('guard_name', 'web')->get()
        );

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'locale' => 'fr',
        ]);
        $this->user->assignRole($role);
    }

    public function test_main_ui_pages_return_ok_without_exceptions(): void
    {
        $this->seed([BureauSeeder::class, WorkflowEtapesSeeder::class]);
        $bureau = Bureau::firstOrFail();
        $this->user->update(['bureau_id' => $bureau->id]);
        $courrier = Courrier::create([
            'type' => 'entrant', 'year' => 2026, 'seq' => 1,
            'reference_interne' => 'GUTF-ENT-2026-000001', 'objet' => 'Dossier de contrôle des écrans',
            'created_by_user_id' => $this->user->id, 'bureau_actuel_id' => $bureau->id,
            'workflow_etape_id' => WorkflowEtape::where('ordre', 2)->value('id'),
        ]);
        $conversation = Conversation::create(['type' => 'group', 'title' => 'Équipe', 'created_by_user_id' => $this->user->id]);
        ParapheurVisa::create(['courrier_id' => $courrier->id, 'validator_id' => $this->user->id, 'status' => 'pending']);
        ConversationParticipant::create(['conversation_id' => $conversation->id, 'user_id' => $this->user->id, 'role' => 'owner']);
        $paths = [
            '/dashboard',
            '/courriers',
            '/courriers/create',
            '/courriers/kanban',
            '/courriers/'.$courrier->id,
            '/courriers/'.$courrier->id.'/edit',
            '/courriers/'.$courrier->id.'/reassign',
            '/search',
            '/statistics',
            '/parapheur', '/notifications', '/profile', '/audit', '/messages',
            '/messages/'.$conversation->id,
            '/admin', '/admin/settings', '/admin/backups', '/admin/users',
            '/admin/users/create', '/admin/users/'.$this->user->id.'/edit',
            '/admin/bureaux', '/admin/bureaux/create', '/admin/bureaux/'.$bureau->id.'/edit',
        ];

        foreach ($paths as $path) {
            $response = $this->actingAs($this->user)->get($path);

            $response->assertOk();
            // Duplicate IDs or broken labels make keyboard and assistive navigation ambiguous.
            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
            $xpath = new \DOMXPath($dom);
            $ids = [];
            foreach ($xpath->query('//*[@id]') as $element) {
                $id = $element->getAttribute('id');
                $this->assertArrayNotHasKey($id, $ids, $path.': duplicate id '.$id);
                $ids[$id] = true;
            }
            foreach ($xpath->query('//label[@for]') as $label) {
                $this->assertArrayHasKey($label->getAttribute('for'), $ids, $path.': missing label target');
            }
        }
    }
}
