<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    public function test_unknown_and_disabled_accounts_receive_the_same_response_without_email(): void
    {
        Notification::fake();
        $disabled = User::factory()->create(['is_active' => false]);
        foreach (['unknown@example.test', $disabled->email] as $email) {
            $this->post('/forgot-password', ['email' => $email])
                ->assertSessionHasNoErrors()->assertSessionHas('status', __(Password::RESET_LINK_SENT));
        }
        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_reset_mail_uses_the_canonical_url_and_the_users_language(): void
    {
        config(['app.url' => 'https://courrier.example.test']);
        foreach (['fr' => 'Choisir un nouveau mot de passe', 'en' => 'Choose a new password'] as $locale => $action) {
            $user = User::factory()->create(['locale' => $locale]);
            $mail = (new ResetPassword('fictional-token'))->toMail($user);
            $this->assertStringStartsWith('https://courrier.example.test/reset-password/', $mail->viewData['url']);
            $this->assertStringContainsString('email='.urlencode($user->email), $mail->viewData['url']);
            $this->assertSame($action, $mail->viewData['action']);
            $html = (string) $mail->render();
            $this->assertStringContainsString($action, $html);
            $this->assertStringNotContainsString('help.syscom-sarl.com', $html);
        }
    }

    public function test_reset_token_expires_is_single_use_and_cannot_reset_a_disabled_account(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $token = Password::createToken($user);
        $payload = ['email' => $user->email, 'token' => $token, 'password' => 'NewPassword!2026', 'password_confirmation' => 'NewPassword!2026'];
        $this->travel(61)->minutes();
        $this->post('/reset-password', $payload)->assertSessionHasErrors('email');
        $this->travelBack();
        $token = Password::createToken($user);
        $payload['token'] = $token;
        $user->update(['is_active' => false]);
        $this->post('/reset-password', $payload)->assertSessionHasErrors('email');
        $user->update(['is_active' => true]);
        $this->post('/reset-password', $payload)->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('NewPassword!2026', $user->fresh()->password));
        $this->post('/reset-password', $payload)->assertSessionHasErrors('email');
    }

    public function test_mail_failure_is_reported_without_exposing_the_transport_error(): void
    {
        Password::shouldReceive('sendResetLink')->once()->andThrow(new TransportException('secret-transport-value'));
        $this->post('/forgot-password', ['email' => 'test@example.test'])
            ->assertSessionHasErrors(['email' => __('passwords.unavailable')]);
        $this->assertStringNotContainsString('secret-transport-value', json_encode(session()->all()));
    }

    public function test_reset_requests_are_rate_limited(): void
    {
        Notification::fake();
        for ($i = 0; $i < 6; $i++) {
            $this->post('/forgot-password', ['email' => 'unknown@example.test'])->assertRedirect();
        }
        $this->post('/forgot-password', ['email' => 'unknown@example.test'])->assertStatus(429);
    }
}
