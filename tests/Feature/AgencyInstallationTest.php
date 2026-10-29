<?php

namespace Tests\Feature;

use App\Console\Commands\AgencyInitializeCommand;
use App\Models\Courrier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class AgencyInstallationTest extends TestCase
{
    use RefreshDatabase;

    private function prepareCommand(string $password = 'Agency-Initial-Password!2026'): void
    {
        config(['distribution.managed' => true]);
        $command = Mockery::mock(AgencyInitializeCommand::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $command->__construct();
        $command->shouldReceive('initialPassword')->andReturn($password);
        $this->app->instance(AgencyInitializeCommand::class, $command);
    }

    public function test_fresh_agency_has_only_a_verified_owner_and_no_customer_records(): void
    {
        $this->prepareCommand();
        $this->artisan('app:agency-initialize', ['--email' => 'owner@example.test', '--password-stdin' => true])->assertSuccessful();
        $owner = User::sole();
        $this->assertTrue($owner->hasRole('super-admin'));
        $this->assertTrue($owner->hasVerifiedEmail());
        $this->assertTrue($owner->is_active);
        $this->assertTrue(Hash::check('Agency-Initial-Password!2026', $owner->password));
        $this->assertSame(0, Courrier::count());
        $this->assertDatabaseHas('bureaux', ['code' => 'accueil']);
    }

    public function test_initialization_never_overwrites_an_existing_user(): void
    {
        $this->prepareCommand();
        $owner = User::factory()->create();
        $before = $owner->getRawOriginal('password');
        $this->artisan('app:agency-initialize', ['--email' => $owner->email, '--password-stdin' => true])->assertFailed();
        $this->assertSame(1, User::count());
        $this->assertSame($before, $owner->fresh()->getRawOriginal('password'));
    }

    public function test_weak_password_and_unmanaged_distribution_are_rejected(): void
    {
        $this->prepareCommand('short');
        $this->artisan('app:agency-initialize', ['--email' => 'owner@example.test', '--password-stdin' => true])->assertFailed();
        $this->assertSame(0, User::count());
        config(['distribution.managed' => false]);
        $this->artisan('app:agency-initialize', ['--email' => 'owner@example.test', '--password-stdin' => true])->assertFailed();
        $this->assertSame(0, User::count());
    }

    public function test_forwarded_https_is_accepted_only_from_an_explicit_proxy(): void
    {
        config(['trustedproxy.proxies' => ['192.0.2.10']]);
        Route::get('/_test/proxy-scheme', fn () => response()->json(['secure' => request()->isSecure()]));
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.11'])->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/_test/proxy-scheme')->assertJson(['secure' => false]);
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])->get('/_test/proxy-scheme')->assertJson(['secure' => true]);
    }
}
