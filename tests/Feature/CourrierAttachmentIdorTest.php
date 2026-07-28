<?php

namespace Tests\Feature;

use App\Models\Courrier;
use App\Models\CourrierAttachment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CourrierAttachmentIdorTest extends TestCase
{
    use RefreshDatabase;

    private User $assignee;
    private User $outsider;
    private Courrier $courrier;
    private CourrierAttachment $attachment;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);

        Storage::fake('local');

        $creator = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $creator->forceFill(['is_active' => true])->save();
        $creator->assignRole('agent_courrier');

        $this->assignee = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->assignee->forceFill(['is_active' => true])->save();
        $this->assignee->assignRole('agent_traitement');

        $this->outsider = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->outsider->forceFill(['is_active' => true])->save();
        // Même permission globale, mais aucun lien avec le courrier → IDOR attendu
        $this->outsider->assignRole('consultation');

        $this->courrier = Courrier::create([
            'type' => 'entrant',
            'year' => (int) now()->year,
            'seq' => 1,
            'reference_interne' => 'GUTF-ENT-' . now()->year . '-000001',
            'objet' => 'Courrier test IDOR',
            'status' => 'affecte',
            'created_by_user_id' => $creator->id,
            'assigned_to_user_id' => $this->assignee->id,
        ]);

        $path = "courriers/{$this->courrier->id}/test_idor.pdf";
        Storage::disk('local')->put($path, '%PDF-1.4 fake content for idor test');

        $this->attachment = CourrierAttachment::create([
            'courrier_id' => $this->courrier->id,
            'uploaded_by_user_id' => $creator->id,
            'source' => 'upload',
            'original_name' => 'test_idor.pdf',
            'stored_name' => 'test_idor.pdf',
            'mime' => 'application/pdf',
            'size' => 32,
            'path' => $path,
        ]);
    }

    public function test_unassigned_user_receives_403_when_downloading_attachment(): void
    {
        $response = $this->actingAs($this->outsider)->get(
            route('courriers.attachments.download', [$this->courrier, $this->attachment])
        );

        $response->assertForbidden();
    }

    public function test_unassigned_user_receives_403_when_previewing_attachment(): void
    {
        $response = $this->actingAs($this->outsider)->get(
            route('courriers.attachments.preview', [$this->courrier, $this->attachment])
        );

        $response->assertForbidden();
    }

    public function test_assigned_agent_receives_200_when_downloading_attachment(): void
    {
        $response = $this->actingAs($this->assignee)->get(
            route('courriers.attachments.download', [$this->courrier, $this->attachment])
        );

        $response->assertOk();
        $response->assertDownload('test_idor.pdf');
    }

    public function test_assigned_agent_receives_200_when_previewing_attachment(): void
    {
        $response = $this->actingAs($this->assignee)->get(
            route('courriers.attachments.preview', [$this->courrier, $this->attachment])
        );

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_attachment_bound_to_another_courrier_returns_404(): void
    {
        $other = Courrier::create([
            'type' => 'sortant',
            'year' => (int) now()->year,
            'seq' => 2,
            'reference_interne' => 'GUTF-SOR-' . now()->year . '-000002',
            'objet' => 'Autre courrier',
            'status' => 'affecte',
            'created_by_user_id' => $this->assignee->id,
            'assigned_to_user_id' => $this->assignee->id,
        ]);

        $response = $this->actingAs($this->assignee)->get(
            route('courriers.attachments.download', [$other, $this->attachment])
        );

        $response->assertNotFound();
    }
}
