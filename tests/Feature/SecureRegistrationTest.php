<?php

namespace Tests\Feature;

use App\Models\Bureau;
use App\Models\Courrier;
use App\Models\User;
use App\Models\WorkflowEtape;
use App\Services\CourrierReferenceService;
use Database\Seeders\BureauSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowEtapesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecureRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([BureauSeeder::class, WorkflowEtapesSeeder::class, RolesAndPermissionsSeeder::class]);
        Storage::fake('local');
        $this->agent = User::factory()->create(['is_active' => true, 'bureau_id' => Bureau::where('code', 'accueil')->value('id')]);
        $this->agent->assignRole('agent_courrier');
        $this->actingAs($this->agent);
    }

    private function payload(): array
    {
        return ['submission_key' => (string) Str::uuid(), 'type' => 'entrant', 'objet' => 'Demande test', 'bureau_actuel_id' => $this->agent->bureau_id];
    }

    public function test_repeating_the_same_form_creates_only_one_dossier_history_and_attachment(): void
    {
        $payload = $this->payload();
        foreach ([1, 2] as $attempt) {
            $this->post(route('courriers.store'), [...$payload,
                'document_file' => UploadedFile::fake()->createWithContent('scan.pdf', "%PDF-1.4\n%%EOF\n"),
            ])->assertRedirect();
        }
        $this->assertDatabaseCount('courriers', 1);
        $this->assertDatabaseCount('courrier_attachments', 1);
        $this->assertSame(1, Courrier::first()->histories()->where('action', 'created')->count());
        $this->assertSame(1, DB::table('courrier_sequences')->value('last_seq'));
    }

    public function test_reusing_a_form_for_different_data_does_not_silently_drop_the_change(): void
    {
        $payload = $this->payload();
        $this->post(route('courriers.store'), $payload)->assertRedirect();
        $this->post(route('courriers.store'), [...$payload, 'objet' => 'Autre demande'])
            ->assertSessionHasErrors('submission_key');
        $this->assertDatabaseCount('courriers', 1);
        $this->assertSame('Demande test', Courrier::first()->objet);
    }

    public function test_two_new_forms_may_legitimately_have_identical_fields(): void
    {
        $this->post(route('courriers.store'), $this->payload())->assertRedirect();
        $this->post(route('courriers.store'), $this->payload())->assertRedirect();
        $this->assertSame([1, 2], Courrier::orderBy('seq')->pluck('seq')->all());
    }

    public function test_another_user_cannot_replay_the_submission_key(): void
    {
        $payload = $this->payload();
        $this->post(route('courriers.store'), $payload)->assertRedirect();
        $other = User::factory()->create(['is_active' => true, 'bureau_id' => $this->agent->bureau_id]);
        $other->assignRole('agent_courrier');
        $this->actingAs($other)->withSession(['password_hash_web' => $other->getAuthPassword()])
            ->post(route('courriers.store'), $payload)->assertForbidden();
        $this->assertDatabaseCount('courriers', 1);
    }

    public function test_a_script_renamed_pdf_is_rejected_at_creation_and_addition(): void
    {
        $payload = $this->payload();
        $file = fn () => UploadedFile::fake()->createWithContent('scan.pdf', '<?php echo "unsafe";');
        $this->post(route('courriers.store'), [...$payload, 'document_file' => $file()])->assertSessionHasErrors('document_file');
        $this->assertDatabaseCount('courriers', 0);
        $this->post(route('courriers.store'), $payload)->assertRedirect();
        $this->post(route('courriers.attachments.upload', Courrier::first()), ['file' => $file()])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('courrier_attachments', 0);
    }

    public function test_image_bytes_cannot_be_disguised_as_pdf_but_a_real_image_is_accepted(): void
    {
        $this->post(route('courriers.store'), [...$this->payload(), 'document_file' => UploadedFile::fake()->image('wrong.pdf')])
            ->assertSessionHasErrors('document_file');
        $this->post(route('courriers.store'), [...$this->payload(), 'document_file' => UploadedFile::fake()->image('scan.png')])->assertRedirect();
        $this->assertDatabaseCount('courrier_attachments', 1);
    }

    public function test_reference_counter_resumes_above_existing_data_and_does_not_reuse_a_reservation(): void
    {
        Courrier::create(['type' => 'entrant', 'year' => now()->year, 'seq' => 70, 'reference_interne' => 'IMPORT-70',
            'objet' => 'Import', 'created_by_user_id' => $this->agent->id, 'bureau_actuel_id' => $this->agent->bureau_id,
            'workflow_etape_id' => WorkflowEtape::where('ordre', 2)->value('id')]);
        $service = app(CourrierReferenceService::class);
        $first = DB::transaction(fn () => $service->next('entrant', now()->year));
        $second = DB::transaction(fn () => $service->next('entrant', now()->year));
        $this->assertSame(71, $first['seq']);
        $this->assertSame(72, $second['seq']);
        $this->assertSame(1, DB::transaction(fn () => $service->next('sortant', now()->year))['seq']);
        $this->assertSame(1, DB::transaction(fn () => $service->next('entrant', now()->year + 1))['seq']);
    }

    public function test_failed_creation_rolls_back_the_counter(): void
    {
        try {
            DB::transaction(function () {
                app(CourrierReferenceService::class)->next('entrant', now()->year);
                throw new \RuntimeException('Simulated failure');
            });
        } catch (\RuntimeException) {
        }
        $this->assertSame(1, DB::transaction(fn () => app(CourrierReferenceService::class)->next('entrant', now()->year))['seq']);
    }

    public function test_an_archived_document_cannot_be_replaced_or_deleted_even_by_an_admin(): void
    {
        $this->post(route('courriers.store'), [...$this->payload(), 'document_file' => UploadedFile::fake()->image('original.png')])->assertRedirect();
        $courrier = Courrier::first();
        $attachment = $courrier->attachments()->firstOrFail();
        $courrier->update(['archived_at' => now()]);
        $this->post(route('courriers.attachments.upload', $courrier), ['file' => UploadedFile::fake()->image('new.png')])->assertForbidden();
        $this->delete(route('courriers.attachments.delete', [$courrier, $attachment]))->assertForbidden();
        $this->agent->assignRole('super-admin');
        $this->post(route('courriers.attachments.upload', $courrier), ['file' => UploadedFile::fake()->image('new.png')])->assertUnprocessable();
        $this->delete(route('courriers.attachments.delete', [$courrier, $attachment]))->assertUnprocessable();
        $this->assertDatabaseCount('courrier_attachments', 1);
        Storage::disk('local')->assertExists($attachment->path);
    }
}
