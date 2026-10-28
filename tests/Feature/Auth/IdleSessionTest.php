<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\IdleSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class IdleSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
    }

    private function signIn(): User
    {
        $user = User::factory()->create(['is_active' => true, 'locale' => 'fr']);
        $this->post('/login', ['email' => $user->email, 'password' => 'password', 'remember' => true])->assertRedirect();

        return $user;
    }

    public function test_login_starts_an_idle_deadline_without_a_persistent_login_cookie(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $cookie = Auth::guard('web')->getRecallerName();
        $this->post('/login', ['email' => $user->email, 'password' => 'password', 'remember' => true])
            ->assertSessionHas(IdleSession::LAST_ACTIVITY, now()->timestamp)
            ->assertSessionHas(IdleSession::KEY)->assertCookieMissing($cookie);
        $this->get('/login')->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_background_polling_cannot_extend_the_session_even_without_ajax_headers(): void
    {
        $this->signIn();
        $start = session(IdleSession::LAST_ACTIVITY);
        $this->travel(10)->minutes();
        $this->getJson('/notifications/unread-count')->assertOk();
        $this->assertSame($start, session(IdleSession::LAST_ACTIVITY));
        $this->travel(10)->minutes();
        $this->get('/notifications/unread-count')->assertOk();
        $this->assertSame($start, session(IdleSession::LAST_ACTIVITY));
        $this->travel(10)->minutes();
        $this->getJson('/notifications/unread-count')->assertUnauthorized()->assertJsonPath('code', 'SESSION_IDLE_EXPIRED');
        $this->assertGuest();
    }

    public function test_user_activity_and_navigation_extend_the_deadline_but_status_checks_do_not(): void
    {
        $this->signIn();
        $this->travel(29)->minutes();
        $this->get('/profile')->assertOk();
        $this->getJson('/session/status')->assertOk()->assertJsonPath('expires_at', now()->timestamp + 1800);
        $this->travel(29)->minutes();
        $this->postJson('/session/activity')->assertOk()->assertJsonPath('expires_at', now()->timestamp + 1800);
        $last = session(IdleSession::LAST_ACTIVITY);
        $this->travel(29)->minutes();
        $this->getJson('/session/status')->assertOk();
        $this->assertSame($last, session(IdleSession::LAST_ACTIVITY));
        $this->travel(2)->minutes();
        $this->postJson('/session/activity')->assertUnauthorized();
        $this->assertGuest();
    }

    public function test_expired_post_is_rejected_before_any_business_data_changes(): void
    {
        $user = $this->signIn();
        $name = $user->name;
        $this->travel(31)->minutes();
        $this->patch('/profile', ['name' => 'Should not be saved', 'email' => $user->email])
            ->assertRedirect(route('login', ['reason' => 'inactive']));
        $this->assertSame($name, $user->fresh()->name);
        $this->assertGuest();
        $this->get('/login?reason=inactive')->assertOk()->assertSee(__('session_ui.expired'));
    }

    public function test_a_late_polling_session_write_cannot_roll_back_newer_user_activity(): void
    {
        $this->signIn();
        $old = session(IdleSession::LAST_ACTIVITY);
        $this->travel(29)->minutes();
        $this->postJson('/session/activity')->assertOk();
        $this->travel(2)->minutes();
        $this->withSession([IdleSession::LAST_ACTIVITY => $old])->getJson('/session/status')
            ->assertOk()->assertJsonPath('expires_at', now()->subMinutes(2)->timestamp + 1800);
    }

    public function test_a_legacy_remember_cookie_cannot_reconnect_silently(): void
    {
        $token = Str::random(60);
        $user = User::factory()->create(['is_active' => true, 'remember_token' => $token]);
        $name = Auth::guard('web')->getRecallerName();
        Auth::forgetGuards();
        $this->withCredentials()->withCookie($name, $user->id.'|'.$token.'|'.$user->password)
            ->getJson('/session/status')->assertUnauthorized()->assertJsonPath('code', 'SESSION_IDLE_EXPIRED');
        $this->assertGuest();
    }

    public function test_logout_clears_the_human_activity_marker(): void
    {
        $this->signIn();
        $key = session(IdleSession::KEY);
        $this->assertNotNull(Cache::get('session-human-activity:'.$key));
        $this->post('/logout')->assertRedirect();
        $this->assertNull(Cache::get('session-human-activity:'.$key));
        $this->assertGuest();
    }
}
