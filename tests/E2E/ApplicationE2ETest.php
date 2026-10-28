<?php

namespace Tests\E2E;

use App\Models\Bureau;
use App\Models\Conversation;
use App\Models\Courrier;
use App\Models\CourrierAttachment;
use App\Models\Message;
use App\Models\User;
use App\Models\WorkflowEtape;
use App\Services\MessagingService;
use App\Services\WorkflowService;
use Database\Seeders\BureauSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowEtapesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Parcours End-to-End HTTP de l'application COURRIER GUTF.
 *
 * Couvre : auth, dashboard, registre, création, PJ, affectation directe et traitement au sein du bureau,
 * kanban scoped, validation, archivage, messagerie, notifications, recherche,
 * profil, exports, administration, contrôle d'accès.
 *
 * Lancer :
 *   php artisan test --testsuite=E2E
 *   composer test:e2e
 *   .\scripts\e2e.ps1
 */
class ApplicationE2ETest extends TestCase
{
    use RefreshDatabase;

    private User $agentAccueil;

    private User $agentVisas;

    private User $agentTraitement;

    private User $validateur;

    private User $agentFiscal;

    private User $admin;

    private User $consultation;

    /** @var array<int, WorkflowEtape> */
    private array $etapes = [];

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(BureauSeeder::class);
        $this->seed(WorkflowEtapesSeeder::class);

        Role::findOrCreate('super-admin', 'web');

        $this->etapes = WorkflowEtape::query()
            ->orderBy('ordre')
            ->get()
            ->keyBy('ordre')
            ->all();

        $bureaux = Bureau::query()->pluck('id', 'code');

        $this->agentAccueil = $this->makeUser('Agent Accueil E2E', 'e2e.accueil@test.com', 'agent_courrier', $bureaux['accueil']);
        $this->agentVisas = $this->makeUser('Agent Visas E2E', 'e2e.visas@test.com', 'validateur', $bureaux['visas_controles']);
        $this->agentTraitement = $this->makeUser('Agent Traitement E2E', 'e2e.traitement@test.com', 'agent_traitement', $bureaux['livre_foncier']);
        $this->validateur = $this->makeUser('Validateur E2E', 'e2e.validateur@test.com', 'validateur', $bureaux['visas_controles']);
        $this->agentFiscal = $this->makeUser('Agent Fiscal E2E', 'e2e.fiscal@test.com', 'agent_courrier', $bureaux['enregistrement_fiscal']);
        $this->admin = $this->makeUser('Admin E2E', 'e2e.admin@test.com', 'super-admin', null);
        $this->admin->givePermissionTo([
            'courrier.view', 'courrier.create', 'courrier.assign', 'courrier.process',
            'courrier.attach', 'courrier.detach', 'courrier.validate', 'courrier.archive',
            'courrier.export', 'chat.view', 'chat.send', 'chat.group.create', 'chat.group.manage',
            'user.manage', 'settings.manage', 'auditor.view', 'auditor.export',
        ]);
        $this->consultation = $this->makeUser('Consultation E2E', 'e2e.consultation@test.com', 'consultation', null);
    }

    private function makeUser(string $name, string $email, string $role, ?int $bureauId): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_active' => true,
            'bureau_id' => $bureauId,
            'locale' => 'fr',
        ]);
        $user->assignRole($role);

        return $user;
    }

    /**
     * actingAs + hash session (AuthenticateSession) pour changer d'utilisateur sans 401.
     */
    private function asUser(User $user): static
    {
        $this->actingAs($user);

        $guard = config('auth.defaults.guard', 'web');
        $this->withSession([
            'password_hash_'.$guard => $user->getAuthPassword(),
        ]);

        return $this;
    }

    private function etape(int $ordre): WorkflowEtape
    {
        return $this->etapes[$ordre];
    }

    public function test_e2e_login_and_logout(): void
    {
        $this->get(route('login'))->assertOk();

        $this->post(route('login'), [
            'email' => $this->agentAccueil->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($this->agentAccueil);

        $this->post(route('logout'))->assertRedirect();
        $this->assertGuest();
    }

    public function test_e2e_full_application_journey(): void
    {
        Storage::fake('local');

        // Uniquement asUser (hash session compatible AuthenticateSession)
        $this->asUser($this->agentAccueil);

        // ── 2. Navigation pages de base ──────────────────────────────────────
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('courriers.index'))->assertOk();
        $this->get(route('courriers.create'))->assertOk();
        $this->get(route('courriers.search'))->assertOk();
        $this->get(route('courriers.kanban'))->assertOk();
        $this->get(route('messages.index'))->assertOk();
        $this->get(route('notifications.index'))->assertOk();
        $this->get(route('profile.edit'))->assertOk();

        // Les colonnes représentent les états, indépendamment du bureau.
        $kanbanAccueil = $this->get(route('courriers.kanban'));
        $this->assertSame([2, 3, 5, 6], $kanbanAccueil->viewData('etapes')->pluck('ordre')->all());
        $this->assertSame('À traiter', $kanbanAccueil->viewData('etapes')->first()->libelle);

        // ── 3. Création courrier + PJ ────────────────────────────────────────
        $create = $this->post(route('courriers.store'), [
            'type' => 'entrant',
            'objet' => 'E2E — Requête d’enregistrement foncier',
            'bureau_actuel_id' => $this->agentTraitement->bureau_id,
            'tf' => 'TF-E2E-2026-001',
            'proprietaire' => 'Société Test E2E',
            'expediteur' => 'Notaire E2E',
            'date_reception' => now()->toDateString(),
            'document_file' => UploadedFile::fake()->createWithContent('dossier-e2e.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n"),
        ]);

        $courrier = Courrier::query()->latest('id')->first();
        $this->assertNotNull($courrier);
        $create->assertRedirect(route('courriers.show', $courrier));

        $this->assertSame((int) $this->etape(2)->id, (int) $courrier->workflow_etape_id);
        $this->assertSame((int) $this->agentTraitement->bureau_id, (int) $courrier->bureau_actuel_id);
        $this->assertDatabaseHas('courrier_histories', [
            'courrier_id' => $courrier->id,
            'action' => 'created',
        ]);

        $this->get(route('courriers.show', $courrier))->assertOk()->assertSee('E2E — Requête');

        $attachment = CourrierAttachment::query()->where('courrier_id', $courrier->id)->first();
        $this->assertNotNull($attachment);
        $this->get(route('courriers.attachments.preview', [$courrier, $attachment]))->assertOk();
        $this->get(route('courriers.attachments.download', [$courrier, $attachment]))->assertOk();

        // ── 4. Affectation à un agent du bureau destinataire ──────────────────
        $this->asUser($this->admin)->post(route('courriers.assign', $courrier), [
            'assigned_to_user_id' => $this->agentTraitement->id,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($this->agentTraitement->id, (int) $courrier->fresh()->assigned_to_user_id);

        // ── 5. Le bureau traite, termine et archive lui-même ──────────────────
        $this->asUser($this->agentAccueil)->postJson(route('courriers.transfer', $courrier), [
            'nouvelle_etape_id' => $this->etape(3)->id,
            'action_type' => WorkflowService::ACTION_FORWARD,
        ])->assertForbidden();
        $this->asUser($this->agentTraitement)
            ->post(route('courriers.start_processing', $courrier))->assertRedirect();
        $this->assertTrue($courrier->fresh()->isAtOrdre(3));
        $kanban = $this->get(route('courriers.kanban'))->assertOk();
        $this->assertSame([2, 3, 5, 6], $kanban->viewData('etapes')->pluck('ordre')->all());
        $this->assertTrue($kanban->viewData('courriersByEtape')->get($this->etape(3)->id, collect())->contains('id', $courrier->id));
        $this->post(route('courriers.complete', $courrier))->assertRedirect();
        $this->assertTrue($courrier->fresh()->isAtOrdre(5));
        $this->post(route('courriers.archive', $courrier))->assertRedirect();
        $courrier->refresh();
        $this->assertTrue($courrier->isArchived());
        $this->assertSame((int) $this->agentTraitement->bureau_id, (int) $courrier->bureau_actuel_id);

        // ── 6. Registre / recherche / exports ────────────────────────────────
        $this->asUser($this->agentAccueil)
            ->get(route('courriers.index', ['q' => 'TF-E2E-2026-001', 'archived' => 'all']))
            ->assertOk()
            ->assertSee('TF-E2E-2026-001');

        $this->asUser($this->agentAccueil)
            ->get(route('courriers.search', ['tf' => 'TF-E2E-2026-001', 'search' => 1]))
            ->assertOk()
            ->assertSee('TF-E2E-2026-001');

        $this->asUser($this->agentAccueil)
            ->get(route('courriers.export.excel'))
            ->assertOk();

        $this->asUser($this->agentAccueil)
            ->get(route('courriers.export.pdf'))
            ->assertOk();

        // ── 7. Messagerie interne ────────────────────────────────────────────
        $this->asUser($this->agentAccueil)
            ->post(route('messages.direct'), ['user_id' => $this->agentTraitement->id])
            ->assertRedirect();

        $direct = Conversation::query()->where('type', 'direct')->latest('id')->first();
        $this->assertNotNull($direct);

        $this->asUser($this->agentAccueil)
            ->postJson(route('messages.send', $direct), ['body' => 'Message E2E direct'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->asUser($this->agentTraitement)
            ->get(route('messages.show', $direct))
            ->assertOk()
            ->assertSee('Message E2E direct');

        $msg = Message::query()->where('conversation_id', $direct->id)->latest('id')->first();
        $this->assertNotNull($msg);

        $this->asUser($this->agentAccueil)
            ->post(route('messages.group.create'), [
                'title' => 'Cellule E2E',
                'members' => [$this->agentTraitement->id, $this->agentVisas->id],
            ])
            ->assertRedirect();

        $group = Conversation::query()->where('type', 'group')->where('title', 'Cellule E2E')->first();
        $this->assertNotNull($group);

        $this->asUser($this->agentAccueil)
            ->patch(route('messages.rename', $group), ['title' => 'Cellule E2E OK'])
            ->assertRedirect();

        $this->getJson(route('chat.mini'))
            ->assertOk()
            ->assertJsonStructure(['unread_total', 'conversations']);

        // Non-lus : messages d’autrui comptés
        $service = app(MessagingService::class);
        $forTraitement = $service->conversationsFor($this->agentTraitement);
        $this->assertGreaterThanOrEqual(0, (int) $forTraitement->sum('unread_count'));

        // ── 8. Notifications API ─────────────────────────────────────────────
        $this->asUser($this->validateur)
            ->getJson(route('notifications.unread_count'))
            ->assertOk()
            ->assertJsonStructure(['count']);

        $this->asUser($this->validateur)
            ->patch(route('notifications.read_all'))
            ->assertRedirect();

        // ── 9. Profil + langue ───────────────────────────────────────────────
        $this->asUser($this->agentAccueil)
            ->patch(route('profile.update'), [
                'name' => 'Agent Accueil E2E Modifié',
                'email' => $this->agentAccueil->email,
                'locale' => 'fr',
            ])
            ->assertRedirect();

        $this->assertSame('Agent Accueil E2E Modifié', $this->agentAccueil->fresh()->name);

        $this->get(route('lang.switch', ['locale' => 'en']))->assertRedirect();

        // ── 10. Admin / stats / audit ────────────────────────────────────────
        $this->asUser($this->admin)->get(route('admin.index'))->assertOk();
        $this->asUser($this->admin)->get(route('admin.settings.edit'))->assertOk();
        $this->asUser($this->admin)->get(route('statistics.index'))->assertOk();
        $this->asUser($this->admin)->get(route('audit.index'))->assertOk();

        // Consultation : pas d’admin, pas de création
        $this->asUser($this->consultation)->get(route('admin.index'))->assertForbidden();
        $this->asUser($this->consultation)->get(route('courriers.create'))->assertForbidden();

        // IDOR PJ : consultation hors périmètre → 403
        $this->asUser($this->consultation)
            ->get(route('courriers.attachments.download', [$courrier, $attachment]))
            ->assertForbidden();

        // ── 11. Rejet métier (nouveau pli court) ─────────────────────────────
        $this->asUser($this->agentAccueil)->post(route('courriers.store'), [
            'type' => 'interne',
            'objet' => 'E2E rejet',
            'bureau_actuel_id' => $this->agentTraitement->bureau_id,
            'date_reception' => now()->toDateString(),
        ])->assertRedirect();

        $toReject = Courrier::query()->where('objet', 'E2E rejet')->firstOrFail();

        $this->asUser($this->agentTraitement)->post(route('courriers.start_processing', $toReject))->assertRedirect();
        $this->postJson(route('courriers.transfer', $toReject), [
            'nouvelle_etape_id' => $this->etape(2)->id,
            'action_type' => WorkflowService::ACTION_REJECT,
            'note_metier' => 'Pièces incomplètes — E2E',
        ])->assertOk();
        $toReject->refresh();
        $this->assertTrue($toReject->isAtOrdre(2));
        $this->assertSame((int) $this->agentTraitement->bureau_id, (int) $toReject->bureau_actuel_id);

        // ── 12. Guest bloqué ─────────────────────────────────────────────────
        auth()->logout();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('courriers.index'))->assertRedirect(route('login'));
        $this->get(route('messages.index'))->assertRedirect(route('login'));
    }
}
