<?php

namespace Tests\Feature;

use App\Models\Bureau;
use App\Models\Courrier;
use App\Models\CourrierAttachment;
use App\Models\CourrierHistory;
use App\Models\User;
use App\Models\WorkflowEtape;
use Database\Seeders\BureauSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowEtapesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CourrierStoreAndPreviewTest extends TestCase
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

        Storage::fake('local');

        $accueilId = WorkflowEtape::query()->where('ordre', 1)->value('bureau_id')
            ?: Bureau::query()->where('code', 'accueil')->value('id');

        $this->agent = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'bureau_id' => $accueilId,
        ]);
        $this->agent->assignRole('agent_courrier');
    }

    public function test_create_form_is_accessible_for_agent_courrier(): void
    {
        $this->actingAs($this->agent)
            ->get(route('courriers.create'))
            ->assertOk()
            ->assertSee('name="objet"', false)
            ->assertSee('name="type"', false)
            ->assertSee('name="document_file"', false);
    }

    public function test_store_rejects_missing_required_fields(): void
    {
        $this->actingAs($this->agent)
            ->from(route('courriers.create'))
            ->post(route('courriers.store'), [
                'bureau_actuel_id' => $this->agent->bureau_id,
                'type' => 'entrant',
            ])
            ->assertRedirect(route('courriers.create'))
            ->assertSessionHasErrors(['objet']);
    }

    public function test_store_new_courrier_with_pdf_saves_attachment_and_shows_preview(): void
    {
        $pdf = UploadedFile::fake()->createWithContent(
            'dossier-foncier.pdf',
            "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n"
        );

        $response = $this->actingAs($this->agent)->post(route('courriers.store'), [
            'bureau_actuel_id' => $this->agent->bureau_id,
            'type' => 'entrant',
            'objet' => 'Test aperçu document',
            'tf' => 'TF-PREVIEW-001',
            'proprietaire' => 'Client Test',
            'date_reception' => now()->toDateString(),
            'document_file' => $pdf,
        ]);

        $courrier = Courrier::query()->where('objet', 'Test aperçu document')->first();
        $this->assertNotNull($courrier);
        $response->assertRedirect(route('courriers.show', $courrier));

        $this->assertSame(2, (int) $courrier->workflowEtape?->ordre);
        $this->assertNotNull($courrier->bureau_actuel_id);
        $this->assertSame($this->agent->id, (int) $courrier->created_by_user_id);

        $attachment = CourrierAttachment::query()->where('courrier_id', $courrier->id)->first();
        $this->assertNotNull($attachment);
        $this->assertSame('application/pdf', $attachment->mime);
        $this->assertNotNull($attachment->absolutePath());
        Storage::disk('local')->assertExists($attachment->path);

        $show = $this->actingAs($this->agent)->get(route('courriers.show', $courrier));
        $show->assertOk()
            ->assertSee('document-viewer', false)
            ->assertSee('data-pdf-viewer', false)
            ->assertSee(route('courriers.attachments.preview', [$courrier, $attachment], false), false)
            ->assertSee('dossier-foncier.pdf');

        $preview = $this->actingAs($this->agent)->get(
            route('courriers.attachments.preview', [$courrier, $attachment])
        );

        $preview->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $preview->headers->get('content-type'));
        $this->assertStringContainsString('inline', (string) $preview->headers->get('content-disposition'));

        $embed = $this->actingAs($this->agent)->get(
            route('courriers.attachments.preview', [$courrier, $attachment]).'?embed=1',
            ['X-PDF-Embed' => '1', 'Accept' => 'application/json']
        );

        $embed->assertOk()
            ->assertJsonStructure(['mime', 'name', 'data']);
        $this->assertSame('application/pdf', $embed->json('mime'));
        $this->assertNotEmpty($embed->json('data'));
        $decoded = base64_decode((string) $embed->json('data'), true);
        $this->assertNotFalse($decoded);
        $this->assertStringStartsWith('%PDF', $decoded);
    }

    public function test_store_accepts_pdf_even_when_browser_sends_octet_stream_mime(): void
    {
        $pdf = UploadedFile::fake()->createWithContent('scan.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n")->mimeType('application/octet-stream');

        $response = $this->actingAs($this->agent)->post(route('courriers.store'), [
            'bureau_actuel_id' => $this->agent->bureau_id,
            'type' => 'entrant',
            'objet' => 'Upload mime souple',
            'date_reception' => now()->toDateString(),
            'document_file' => $pdf,
        ]);

        $courrier = Courrier::query()->where('objet', 'Upload mime souple')->first();
        $this->assertNotNull($courrier);
        $response->assertRedirect(route('courriers.show', $courrier));

        $attachment = CourrierAttachment::query()->where('courrier_id', $courrier->id)->first();
        $this->assertNotNull($attachment);
        $this->assertSame('application/pdf', $attachment->mime);
    }

    public function test_store_full_cct_fields_sets_reference_history_and_sla(): void
    {
        $assignee = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'bureau_id' => $this->agent->bureau_id,
        ]);
        $assignee->assignRole('agent_traitement');

        $payload = [
            'bureau_actuel_id' => $this->agent->bureau_id,
            'type' => 'entrant',
            'objet' => 'Mutation complète CCT',
            'description' => 'Dossier de test enregistrement complet',
            'reference_externe' => 'NOT-2026-88',
            'tf' => 'TF-CCT-7788',
            'expediteur' => 'Étude Me Test',
            'destinataire' => 'GUTF',
            'proprietaire' => 'Propriétaire Demo',
            'requerant' => 'Requérant Demo',
            'vendeur' => 'Vendeur Demo',
            'etude_notariale' => 'Étude Demo',
            'valeur_immeuble' => '95000000.50',
            'numero_acte' => 'ACT-9901',
            'numero_repertoire' => 'REP-4402',
            'date_courrier' => now()->subDay()->toDateString(),
            'date_reception' => now()->toDateString(),
            'assigned_to_user_id' => $assignee->id,
        ];

        $response = $this->actingAs($this->agent)->post(route('courriers.store'), $payload);

        $courrier = Courrier::query()->where('objet', 'Mutation complète CCT')->first();
        $this->assertNotNull($courrier);
        $response->assertRedirect(route('courriers.show', $courrier));

        $year = (int) now()->year;
        $this->assertSame('entrant', $courrier->type);
        $this->assertSame($year, (int) $courrier->year);
        $this->assertSame(1, (int) $courrier->seq);
        $this->assertSame(sprintf('GUTF-ENT-%d-000001', $year), $courrier->reference_interne);
        $this->assertSame('TF-CCT-7788', $courrier->tf);
        $this->assertSame('Propriétaire Demo', $courrier->proprietaire);
        $this->assertSame('Vendeur Demo', $courrier->vendeur);
        $this->assertSame('ACT-9901', $courrier->numero_acte);
        $this->assertSame('95000000.50', (string) $courrier->valeur_immeuble);
        $this->assertSame($assignee->id, (int) $courrier->assigned_to_user_id);
        $this->assertNotNull($courrier->date_limite);
        $this->assertFalse((bool) $courrier->is_late);

        $etapeReception = WorkflowEtape::query()->where('ordre', 2)->first();
        $this->assertSame($etapeReception?->id, (int) $courrier->workflow_etape_id);
        $this->assertSame($this->agent->bureau_id, (int) $courrier->bureau_actuel_id);

        $history = CourrierHistory::query()
            ->where('courrier_id', $courrier->id)
            ->where('action', 'created')
            ->first();
        $this->assertNotNull($history);
        $this->assertSame($etapeReception?->id, (int) $history->nouvelle_etape_id);
        $this->assertSame($this->agent->id, (int) $history->actor_user_id);
        $this->assertSame($courrier->reference_interne, data_get($history->metadata, 'reference_interne'));
    }

    public function test_store_generates_distinct_references_per_type(): void
    {
        foreach (['entrant' => 'ENT', 'sortant' => 'SOR', 'interne' => 'INT'] as $type => $prefix) {
            $response = $this->actingAs($this->agent)->post(route('courriers.store'), [
                'bureau_actuel_id' => $this->agent->bureau_id,
                'type' => $type,
                'objet' => "Courrier {$type}",
                'date_reception' => now()->toDateString(),
            ]);

            $courrier = Courrier::query()->where('objet', "Courrier {$type}")->first();
            $this->assertNotNull($courrier, "Courrier {$type} non créé");
            $response->assertRedirect(route('courriers.show', $courrier));

            $year = (int) now()->year;
            $this->assertSame(sprintf('GUTF-%s-%d-000001', $prefix, $year), $courrier->reference_interne);
            $this->assertSame(1, (int) $courrier->seq);
        }

        // Second entrant → seq 2
        $this->actingAs($this->agent)->post(route('courriers.store'), [
            'bureau_actuel_id' => $this->agent->bureau_id,
            'type' => 'entrant',
            'objet' => 'Second entrant',
            'date_reception' => now()->toDateString(),
        ]);

        $second = Courrier::query()->where('objet', 'Second entrant')->first();
        $this->assertNotNull($second);
        $this->assertSame(2, (int) $second->seq);
        $this->assertSame(sprintf('GUTF-ENT-%d-000002', (int) now()->year), $second->reference_interne);
    }
}
