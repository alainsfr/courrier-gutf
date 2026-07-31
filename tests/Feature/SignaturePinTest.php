<?php

namespace Tests\Feature;

use App\Models\Bureau;
use App\Models\Courrier;
use App\Models\ParapheurVisa;
use App\Models\User;
use App\Models\WorkflowEtape;
use Database\Seeders\BureauSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowEtapesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SignaturePinTest extends TestCase
{
    use RefreshDatabase;

    private User $directeur;

    private Courrier $courrier;

    private ParapheurVisa $visa;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(BureauSeeder::class);
        $this->seed(WorkflowEtapesSeeder::class);

        $accueilId = WorkflowEtape::query()->where('ordre', 1)->value('bureau_id')
            ?: Bureau::query()->where('code', 'accueil')->value('id');
        $etape = WorkflowEtape::query()->where('ordre', 1)->firstOrFail();

        $this->directeur = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'bureau_id' => $accueilId,
            'signature_pin' => null,
        ]);
        $this->directeur->assignRole('directeur');

        $agent = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
            'bureau_id' => $accueilId,
        ]);
        $agent->assignRole('agent_courrier');

        $this->courrier = Courrier::query()->create([
            'type' => 'sortant',
            'year' => (int) now()->format('Y'),
            'seq' => 55,
            'reference_interne' => 'GUTF-SOR-2026-000055',
            'objet' => 'Visa avec PIN',
            'created_by_user_id' => $agent->id,
            'bureau_actuel_id' => $accueilId,
            'workflow_etape_id' => $etape->id,
            'date_envoi' => now()->toDateString(),
        ]);

        $this->visa = ParapheurVisa::query()->create([
            'courrier_id' => $this->courrier->id,
            'validator_id' => $this->directeur->id,
            'status' => ParapheurVisa::STATUS_PENDING,
        ]);
    }

    public function test_user_can_create_signature_pin_from_profile(): void
    {
        $response = $this->actingAs($this->directeur)->put(
            route('profile.signature-pin.update'),
            [
                'signature_pin' => '1234',
                'signature_pin_confirmation' => '1234',
            ]
        );

        $response->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'signature-pin-updated');

        $this->directeur->refresh();
        $this->assertNotNull($this->directeur->signature_pin);
        $this->assertTrue(Hash::check('1234', $this->directeur->signature_pin));
    }

    public function test_directeur_cannot_approve_visa_with_wrong_pin(): void
    {
        $this->directeur->forceFill([
            'signature_pin' => Hash::make('1234'),
        ])->save();

        $this->actingAs($this->directeur)
            ->from(route('courriers.show', $this->courrier))
            ->post(route('parapheur.approve', $this->visa), [
                'signature_pin' => '9999',
            ])
            ->assertRedirect(route('courriers.show', $this->courrier))
            ->assertSessionHasErrors('signature_pin');

        $this->visa->refresh();
        $this->assertSame(ParapheurVisa::STATUS_PENDING, $this->visa->status);
        $this->assertNull($this->visa->digital_hash);
    }

    public function test_successful_approval_generates_digital_hash(): void
    {
        $this->directeur->forceFill([
            'signature_pin' => Hash::make('1234'),
        ])->save();

        $this->actingAs($this->directeur)
            ->post(route('parapheur.approve', $this->visa), [
                'signature_pin' => '1234',
            ])
            ->assertRedirect(route('courriers.show', $this->courrier));

        $this->visa->refresh();
        $this->assertSame(ParapheurVisa::STATUS_APPROVED, $this->visa->status);
        $this->assertNotNull($this->visa->signed_at);
        $this->assertNotNull($this->visa->digital_hash);
        $this->assertSame(64, strlen($this->visa->digital_hash));
    }
}
