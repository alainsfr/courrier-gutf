<?php

namespace Tests\Feature\Auth;

use App\Models\AccountRecovery;
use App\Models\User;
use App\Services\AccountRecoveryService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class RecoveryCodeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('super-admin');

        return $admin;
    }

    private function issue(User $user): array
    {
        return app(AccountRecoveryService::class)->issue($user);
    }

    private function resetPayload(User $user, string $code): array
    {
        return ['email' => $user->email, 'recovery_code' => $code, 'password' => 'NewSecurePassword!2026', 'password_confirmation' => 'NewSecurePassword!2026'];
    }

    public function test_recovery_page_and_email_alternative_are_available_without_login(): void
    {
        $this->get('/forgot-password')->assertOk()->assertSee(route('password.code'));
        $this->get('/recover-with-code')->assertOk()->assertSee('name="recovery_code"', false)->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_only_super_admin_can_issue_codes(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create(['is_active' => true]);
        $this->get(route('admin.users.recovery', $target))->assertRedirect(route('login'));
        $target->assignRole('admin');
        $this->actingAs($target)->get(route('admin.users.recovery', $admin))->assertForbidden();
        $this->post(route('admin.users.recovery.issue', $admin), ['admin_password' => 'password', 'identity_checked' => '1'])->assertForbidden();
        $this->assertDatabaseCount('account_recoveries', 0);
    }

    public function test_issuer_must_confirm_identity_and_their_own_password(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create();
        $url = route('admin.users.recovery.issue', $target);
        $this->actingAs($admin)->post($url, ['admin_password' => 'password'])->assertSessionHasErrors('identity_checked');
        $this->post($url, ['admin_password' => 'wrong', 'identity_checked' => '1'])->assertSessionHasErrors('admin_password');
        $this->assertDatabaseCount('account_recoveries', 0);
        $this->assertNull(session()->getOldInput('admin_password'));
        $response = $this->post($url, ['admin_password' => 'password', 'identity_checked' => '1'])->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $code = $response->viewData('code');
        $record = AccountRecovery::sole();
        $this->assertSame($admin->id, $record->issued_by);
        $this->assertNotSame(str_replace('-', '', $code), $record->code_hash);
        $this->assertStringNotContainsString($code, json_encode(session()->all()));
        $this->assertStringNotContainsString('code_hash', $record->toJson());
        $this->get(route('admin.users.recovery', $target))->assertOk()->assertDontSee($code);
    }

    public function test_disabled_accounts_cannot_receive_a_code(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create(['is_active' => false]);
        $this->actingAs($admin)->post(route('admin.users.recovery.issue', $target), ['admin_password' => 'password', 'identity_checked' => '1'])->assertSessionHasErrors('user');
        $this->assertDatabaseCount('account_recoveries', 0);
    }

    public function test_code_resets_once_without_sending_email_or_signing_in_and_invalidates_email_token(): void
    {
        Notification::fake();
        Event::fake([PasswordReset::class]);
        $user = User::factory()->create(['remember_token' => 'old-token']);
        $emailToken = Password::createToken($user);
        $issued = $this->issue($user);
        $payload = $this->resetPayload($user, strtolower(str_replace('-', ' ', $issued['code'])));
        $this->post('/recover-with-code', $payload)->assertRedirect(route('login'))->assertSessionHasNoErrors();
        $this->assertGuest();
        $this->assertTrue(Hash::check($payload['password'], $user->fresh()->password));
        $this->assertNotSame('old-token', $user->fresh()->remember_token);
        $this->assertNotNull($issued['grant']->fresh()->used_at);
        $this->assertFalse(Password::tokenExists($user->fresh(), $emailToken));
        $this->post('/recover-with-code', $payload)->assertSessionHasErrors('recovery_code');
        Notification::assertNothingSent();
        Event::assertDispatchedTimes(PasswordReset::class, 1);
    }

    public function test_incorrect_expired_disabled_unknown_and_used_codes_return_the_same_error(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $issued = $this->issue($user);
        $this->post('/recover-with-code', $this->resetPayload($user, 'wrong'))->assertSessionHasErrors(['recovery_code' => __('recovery.invalid')]);
        $user->update(['is_active' => false]);
        $this->post('/recover-with-code', $this->resetPayload($user, $issued['code']))->assertSessionHasErrors(['recovery_code' => __('recovery.invalid')]);
        $user->update(['is_active' => true]);
        $this->travel(15)->minutes();
        $this->post('/recover-with-code', $this->resetPayload($user, $issued['code']))->assertSessionHasErrors(['recovery_code' => __('recovery.invalid')]);
        $payload = $this->resetPayload($user, $issued['code']);
        $payload['email'] = 'unknown@example.test';
        $this->post('/recover-with-code', $payload)->assertSessionHasErrors(['recovery_code' => __('recovery.invalid')]);
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $this->assertNull(session()->getOldInput('recovery_code'));
        $this->assertNull(session()->getOldInput('password'));
    }

    public function test_five_wrong_attempts_invalidate_the_code_even_across_client_addresses_without_locking_login(): void
    {
        $user = User::factory()->create();
        $issued = $this->issue($user);
        for ($i = 1; $i <= 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.$i])->post('/recover-with-code', $this->resetPayload($user, 'wrong'))->assertSessionHasErrors('recovery_code');
        }
        $this->assertSame(5, $issued['grant']->fresh()->attempts);
        $this->assertNotNull($issued['grant']->fresh()->revoked_at);
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.20'])->post('/recover-with-code', $this->resetPayload($user, $issued['code']))->assertSessionHasErrors('recovery_code');
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_generating_a_new_code_invalidates_the_previous_one_and_retains_history(): void
    {
        $user = User::factory()->create();
        $first = $this->issue($user);
        $second = $this->issue($user);
        $this->assertNotSame($first['code'], $second['code']);
        $this->assertNotNull($first['grant']->fresh()->revoked_at);
        $this->post('/recover-with-code', $this->resetPayload($user, $first['code']))->assertSessionHasErrors('recovery_code');
        $this->post('/recover-with-code', $this->resetPayload($user, $second['code']))->assertRedirect(route('login'));
        $this->assertDatabaseCount('account_recoveries', 2);
    }

    public function test_email_reset_invalidates_an_outstanding_code(): void
    {
        $user = User::factory()->create();
        $issued = $this->issue($user);
        $token = Password::createToken($user);
        $payload = $this->resetPayload($user, $issued['code']);
        $this->post('/reset-password', $payload + ['token' => $token])->assertRedirect(route('login'));
        $this->post('/recover-with-code', $payload)->assertSessionHasErrors('recovery_code');
    }

    public function test_direct_password_or_email_change_invalidates_a_code(): void
    {
        $user = User::factory()->create();
        $issued = $this->issue($user);
        $user->update(['password' => Hash::make('AnotherPassword!2026')]);
        $this->post('/recover-with-code', $this->resetPayload($user, $issued['code']))->assertSessionHasErrors('recovery_code');
        $issued = $this->issue($user->fresh());
        $user->update(['email' => 'changed@example.test']);
        $this->post('/recover-with-code', $this->resetPayload($user, $issued['code']))->assertSessionHasErrors('recovery_code');
    }

    public function test_password_confirmation_is_required_before_consuming_code(): void
    {
        $user = User::factory()->create();
        $issued = $this->issue($user);
        $payload = $this->resetPayload($user, $issued['code']);
        $payload['password_confirmation'] = 'different';
        $this->post('/recover-with-code', $payload)->assertSessionHasErrors('password');
        $this->assertNull($issued['grant']->fresh()->used_at);
        $this->assertNull(session()->getOldInput('recovery_code'));
    }

    public function test_public_attempts_are_rate_limited(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 6; $i++) $this->post('/recover-with-code', $this->resetPayload($user, 'wrong'))->assertRedirect();
        $this->post('/recover-with-code', $this->resetPayload($user, 'wrong'))->assertTooManyRequests();
    }

    public function test_recovery_revokes_existing_authenticated_sessions_on_the_next_request(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $oldHash = $user->password;
        $issued = $this->issue($user);
        $this->assertTrue(app(AccountRecoveryService::class)->redeem($user->email, $issued['code'], 'NewSecurePassword!2026'));
        $this->actingAs($user->fresh())->withSession(['password_hash_web' => $oldHash])->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_server_console_can_issue_a_code_for_the_only_super_admin_and_cancellation_changes_nothing(): void
    {
        $admin = $this->admin();
        $prompt = 'Avez-vous vérifié l’identité du titulaire et souhaitez-vous invalider ses précédents codes ?';
        $this->artisan('app:recovery-code', ['email' => $admin->email])->expectsConfirmation($prompt, 'no')->assertFailed();
        $this->assertDatabaseCount('account_recoveries', 0);
        $this->artisan('app:recovery-code', ['email' => $admin->email])->expectsConfirmation($prompt, 'yes')->assertSuccessful();
        $this->assertSame('console', AccountRecovery::sole()->source);
        $this->assertTrue(Hash::check('password', $admin->fresh()->password));
    }
}
