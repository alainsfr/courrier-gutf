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
                'pin_password' => 'password',
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

    public function test_changing_pin_requires_the_current_password_and_does_not_flash_secrets(): void
    {
        $this->actingAs($this->directeur)->put(route('profile.signature-pin.update'), [
            'signature_pin' => '1234', 'signature_pin_confirmation' => '1234', 'pin_password' => 'incorrect',
        ])->assertSessionHasErrors('pin_password');
        $this->assertNull($this->directeur->fresh()->signature_pin);
        $this->assertArrayNotHasKey('signature_pin', session()->getOldInput());
        $this->assertArrayNotHasKey('signature_pin_confirmation', session()->getOldInput());
        $this->assertArrayNotHasKey('pin_password', session()->getOldInput());
    }

    public function test_five_failures_lock_the_account_across_visas_and_expire_after_ten_minutes(): void
    {
        $this->directeur->forceFill(['signature_pin' => Hash::make('1234')])->save();
        $otherVisa = ParapheurVisa::create(['courrier_id' => $this->courrier->id, 'validator_id' => $this->directeur->id, 'status' => 'pending']);
        $this->actingAs($this->directeur);
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('parapheur.approve', $i % 2 ? $otherVisa : $this->visa), ['signature_pin' => '9999'])
                ->assertSessionHasErrors('signature_pin');
        }
        $this->assertSame(5, $this->directeur->fresh()->signature_pin_attempts);
        $this->assertTrue($this->directeur->fresh()->signature_pin_locked_until->isFuture());
        $this->post(route('parapheur.approve', $this->visa), ['signature_pin' => '1234'])->assertSessionHasErrors('signature_pin');
        $this->assertSame('pending', $this->visa->fresh()->status);
        $this->assertArrayNotHasKey('signature_pin', session()->getOldInput());
        $this->travel(11)->minutes();
        $this->post(route('parapheur.approve', $this->visa), ['signature_pin' => '1234'])->assertSessionDoesntHaveErrors();
        $this->assertSame('approved', $this->visa->fresh()->status);
        $this->assertSame(0, $this->directeur->fresh()->signature_pin_attempts);
    }

    public function test_password_verified_pin_reset_unlocks_the_account(): void
    {
        $this->directeur->forceFill(['signature_pin_attempts' => 5, 'signature_pin_locked_until' => now()->addMinutes(10)])->save();
        $this->actingAs($this->directeur)->put(route('profile.signature-pin.update'), [
            'signature_pin' => '5678', 'signature_pin_confirmation' => '5678', 'pin_password' => 'password',
        ])->assertSessionDoesntHaveErrors();
        $this->assertSame(0, $this->directeur->fresh()->signature_pin_attempts);
        $this->assertNull($this->directeur->fresh()->signature_pin_locked_until);
    }

    public function test_approval_cannot_be_overwritten_by_a_later_rejection(): void
    {
        $this->directeur->forceFill(['signature_pin' => Hash::make('1234')])->save();
        $this->actingAs($this->directeur)->post(route('parapheur.approve', $this->visa), ['signature_pin' => '1234'])->assertRedirect();
        $this->post(route('parapheur.reject', $this->visa), ['comment' => 'Too late'])->assertNotFound();
        $this->assertSame('approved', $this->visa->fresh()->status);
    }
}
