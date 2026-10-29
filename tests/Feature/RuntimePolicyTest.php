<?php

namespace Tests\Feature;

use App\Models\Courrier;
use App\Models\User;
use App\Models\WorkflowEtape;
use App\Services\ManagedRuntime;
use App\Services\RuntimeSignature;
use Database\Seeders\BureauSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowEtapesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class RuntimePolicyTest extends TestCase
{
    use RefreshDatabase;

    private string $directory;

    private static string $privateKey;

    private static string $publicKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        $this->directory = sys_get_temp_dir().'/courrier-runtime-test-'.Str::random(12);
        File::makeDirectory($this->directory);
        if (! isset(self::$privateKey)) {
            $options = \App\Support\SigningKeyOptions::rsa();
            $key = openssl_pkey_new($options);
            $this->assertNotFalse($key);
            openssl_pkey_export($key, $exported, null, $options);
            self::$privateKey = $exported;
            self::$publicKey = openssl_pkey_get_details($key)['key'];
        }
        file_put_contents($this->directory.'/public.pem', self::$publicKey);
        file_put_contents($this->directory.'/machine-id', str_repeat('a', 32));
        file_put_contents($this->directory.'/machine-uuid', 'test-hardware-uuid');
        config(['distribution.managed' => true, 'runtime.directory' => $this->directory.'/state',
            'runtime.public_key' => $this->directory.'/public.pem', 'runtime.machine_id' => $this->directory.'/machine-id',
            'runtime.machine_uuid' => $this->directory.'/machine-uuid']);
    }

    protected function tearDown(): void
    {
        if (isset($this->directory) && str_starts_with($this->directory, sys_get_temp_dir().'/courrier-runtime-test-')) {
            File::deleteDirectory($this->directory);
        }
        parent::tearDown();
    }

    private function runtime(): ManagedRuntime
    {
        return app(ManagedRuntime::class);
    }

    private function document(array $overrides = []): string
    {
        $request = $this->runtime()->request();
        $data = array_replace(['version' => 1, 'product' => 'courrier-gutf', 'installation_id' => $request['installation_id'],
            'fingerprint' => $request['fingerprint'], 'generation' => $request['next_generation'], 'client' => 'Fictional Client', 'agency' => 'Fictional Agency',
            'issued_at' => now()->timestamp, 'not_before' => now()->timestamp, 'expires_at' => now()->addHour()->timestamp], $overrides);
        $body = json_encode($data, JSON_THROW_ON_ERROR);
        openssl_sign(RuntimeSignature::CONTEXT.$body, $signature, self::$privateKey, OPENSSL_ALGO_SHA256);

        return json_encode(['payload' => base64_encode($body), 'signature' => base64_encode($signature)], JSON_THROW_ON_ERROR);
    }

    private function admin(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create(['is_active' => true, 'locale' => 'fr']);
        $user->assignRole('super-admin');

        return $user;
    }

    public function test_source_and_existing_demo_are_not_managed(): void
    {
        config(['distribution.managed' => false]);
        $this->assertTrue($this->runtime()->canWrite());
        $this->assertDirectoryDoesNotExist($this->directory.'/state');
    }

    public function test_missing_state_or_uninitialized_copy_is_read_only(): void
    {
        $this->assertFalse($this->runtime()->canWrite());
        $this->runtime()->request();
        $this->assertFalse($this->runtime()->canWrite());
    }

    public function test_request_has_a_stable_installation_identifier_without_disclosing_os_identifiers(): void
    {
        $first = $this->runtime()->request();
        $this->assertSame($first, $this->runtime()->request());
        $this->assertStringNotContainsString(str_repeat('a', 32), json_encode($first));
        $this->assertStringNotContainsString('test-hardware-uuid', json_encode($first));
        config(['runtime.directory' => $this->directory.'/second-state']);
        $this->assertNotSame($first['installation_id'], $this->runtime()->request()['installation_id']);
    }

    public function test_signed_bound_document_allows_operation_until_the_exact_expiration(): void
    {
        $expires = now()->addHour()->timestamp;
        $this->runtime()->install($this->document(['expires_at' => $expires]));
        $this->assertTrue($this->runtime()->canWrite());
        $this->travelTo(now()->setTimestamp($expires - 1));
        $this->assertTrue($this->runtime()->canWrite());
        $this->travelTo(now()->setTimestamp($expires));
        $this->assertFalse($this->runtime()->canWrite());
    }

    public function test_start_time_is_enforced(): void
    {
        $this->runtime()->install($this->document(['not_before' => now()->addMinutes(10)->timestamp]));
        $this->assertFalse($this->runtime()->canWrite());
        $this->travel(10)->minutes();
        $this->assertTrue($this->runtime()->canWrite());
    }

    public function test_modifying_signed_dates_or_forging_signature_is_rejected(): void
    {
        $document = json_decode($this->document(), true);
        $data = json_decode(base64_decode($document['payload']), true);
        $data['expires_at'] += 86400;
        $document['payload'] = base64_encode(json_encode($data));
        try {
            $this->runtime()->install(json_encode($document));
            $this->fail('Tampered interval accepted.');
        } catch (\RuntimeException) {
        }
        $this->assertFalse($this->runtime()->canWrite());
        $document = json_decode($this->document(), true);
        $document['signature'] = base64_encode(random_bytes(384));
        try {
            $this->runtime()->install(json_encode($document));
            $this->fail('Forged signature accepted.');
        } catch (\RuntimeException) {
        }
    }

    public function test_wrong_product_or_wrong_server_or_wrong_installation_is_rejected(): void
    {
        foreach ([['product' => 'other-product'], ['fingerprint' => str_repeat('b', 64)], ['installation_id' => str_repeat('b', 32)]] as $changes) {
            try {
                $this->runtime()->install($this->document($changes));
                $this->fail('Invalid binding accepted.');
            } catch (\RuntimeException) {
            }
        }
        $this->assertFalse($this->runtime()->canWrite());
    }

    public function test_copying_a_valid_installation_to_a_different_server_stays_read_only(): void
    {
        $this->runtime()->install($this->document());
        file_put_contents($this->directory.'/machine-id', str_repeat('b', 32));
        $this->assertFalse($this->runtime()->canWrite());
    }

    public function test_bad_or_unavailable_machine_or_issuer_does_not_allow_writes(): void
    {
        $this->runtime()->install($this->document());
        file_put_contents($this->directory.'/public.pem', 'invalid');
        $this->assertFalse($this->runtime()->canWrite());
        file_put_contents($this->directory.'/public.pem', self::$publicKey);
        file_put_contents($this->directory.'/machine-id', '');
        $this->assertFalse($this->runtime()->canWrite());
    }

    public function test_state_tampering_is_rejected(): void
    {
        $this->runtime()->install($this->document());
        $path = $this->directory.'/state/state.json';
        $state = json_decode(file_get_contents($path), true);
        $body = json_decode($state['body'], true);
        $body['last_seen'] -= 86400;
        $state['body'] = json_encode($body);
        file_put_contents($path, json_encode($state));
        $this->assertFalse($this->runtime()->canWrite());
    }

    public function test_clock_rollback_and_expired_document_cannot_restore_operation(): void
    {
        $this->runtime()->install($this->document());
        $this->travel(20)->minutes();
        $this->assertTrue($this->runtime()->canWrite());
        $this->travelBack();
        $this->assertFalse($this->runtime()->canWrite());
        $this->travel(61)->minutes();
        $this->assertFalse($this->runtime()->canWrite());
        $this->travel(-2)->minutes(); // within the NTP tolerance, but expiration was already observed
        $this->assertFalse($this->runtime()->canWrite());
    }

    public function test_renewal_restores_operation_but_old_documents_cannot_be_replayed(): void
    {
        $old = $this->document();
        $this->runtime()->install($old);
        $this->travel(61)->minutes();
        $this->assertFalse($this->runtime()->canWrite());
        $this->runtime()->install($this->document());
        $this->assertTrue($this->runtime()->canWrite());
        try {
            $this->runtime()->install($old);
            $this->fail('Old generation accepted.');
        } catch (\RuntimeException) {
        }
        $this->assertTrue($this->runtime()->canWrite());
    }

    public function test_read_only_http_allows_consultation_and_returns_a_neutral_response_for_business_writes(): void
    {
        $user = $this->admin();
        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->get('/courriers')->assertOk();
        $this->post('/courriers', ['objet' => 'Must not be created'])->assertForbidden()->assertSee(__('ui.operation_unavailable'))->assertDontSee('licence')->assertDontSee('expired');
        $this->postJson('/courriers', ['objet' => 'Must not be created'])->assertForbidden()->assertExactJson(['message' => __('ui.operation_unavailable')]);
        $this->assertDatabaseCount('courriers', 0);
        $this->get('/courriers/create')->assertForbidden();
        $this->get('/courriers/export/excel')->assertForbidden();
        $this->get('/courriers/export/pdf')->assertForbidden();
        $this->get('/admin/users/create')->assertForbidden();
        $this->get('/admin/users/'.$user->id.'/edit')->assertForbidden();
        $this->get('/profile')->assertOk();
    }

    public function test_authentication_and_password_recovery_remain_available(): void
    {
        Notification::fake();
        $user = $this->admin();
        $this->get('/recover-with-code')->assertOk();
        $this->post('/recover-with-code', ['email' => $user->email, 'recovery_code' => 'invalid', 'password' => 'NewPassword!2026', 'password_confirmation' => 'NewPassword!2026'])->assertSessionHasErrors('recovery_code');
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->post('/session/activity')->assertOk();
        $this->post('/logout')->assertRedirect();
        $this->assertGuest();
    }

    public function test_valid_document_preserves_normal_business_requests(): void
    {
        $this->runtime()->install($this->document());
        $user = $this->admin();
        $this->actingAs($user)->get('/courriers/create')->assertOk();
        $this->post('/courriers', [])->assertSessionHasErrors()->assertStatus(302);
    }

    public function test_deadline_automation_does_not_modify_business_data_after_expiry(): void
    {
        Notification::fake();
        $this->seed([BureauSeeder::class, WorkflowEtapesSeeder::class]);
        $user = User::factory()->create();
        $courrier = Courrier::create(['type' => 'entrant', 'year' => 2026, 'seq' => 1, 'reference_interne' => 'GUTF-ENT-2026-000001', 'objet' => 'Late fixture',
            'created_by_user_id' => $user->id, 'workflow_etape_id' => WorkflowEtape::where('ordre', 2)->value('id'), 'date_limite' => now()->subDay(), 'is_late' => false]);
        $this->artisan('app:check-delais')->assertSuccessful();
        $this->assertFalse($courrier->fresh()->is_late);
        Notification::assertNothingSent();
    }

    public function test_console_request_does_not_overwrite_an_existing_export(): void
    {
        $path = $this->directory.'/request.json';
        $this->artisan('app:installation-request', ['output' => $path])->assertSuccessful();
        $before = file_get_contents($path);
        $this->artisan('app:installation-request', ['output' => $path])->assertFailed();
        $this->assertSame($before, file_get_contents($path));
        $path = $this->directory.'/document.json';
        file_put_contents($path, $this->document());
        $this->artisan('app:installation-import', ['file' => $path])->assertSuccessful();
        $this->assertTrue($this->runtime()->canWrite());
        $this->artisan('app:installation-import', ['file' => $path])->assertFailed();
    }
}
