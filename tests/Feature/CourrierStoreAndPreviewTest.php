<?php

namespace Tests\Feature;

use App\Models\Bureau;
use App\Models\Courrier;
use App\Models\CourrierAttachment;
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

    public function test_store_new_courrier_with_pdf_saves_attachment_and_shows_preview(): void
    {
        $pdf = UploadedFile::fake()->createWithContent(
            'dossier-foncier.pdf',
            "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n"
        );

        $response = $this->actingAs($this->agent)->post(route('courriers.store'), [
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

        $this->assertSame(1, (int) $courrier->workflowEtape?->ordre);
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
        $pdf = UploadedFile::fake()->create('scan.pdf', 100, 'application/octet-stream');

        $response = $this->actingAs($this->agent)->post(route('courriers.store'), [
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
}
