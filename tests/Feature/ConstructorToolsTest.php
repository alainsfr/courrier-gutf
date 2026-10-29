<?php

namespace Tests\Feature;

use App\Services\ManagedRuntime;
use App\Services\RuntimeSignature;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConstructorToolsTest extends TestCase
{
    private string $directory;

    private string $publicKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/courrier-constructor-test-'.Str::random(12);
        File::makeDirectory($this->directory.'/keys', 0700, true);
        [$exit, $output, $error] = $this->tool('generate-keys.php', ['--directory='.$this->directory.'/keys']);
        $this->assertSame(0, $exit, $output.$error);
        $this->publicKey = file_get_contents($this->directory.'/keys/issuer-public.pem');
        file_put_contents($this->directory.'/machine-id', str_repeat('c', 32));
        config(['runtime.directory' => $this->directory.'/state', 'runtime.public_key' => $this->directory.'/keys/issuer-public.pem',
            'runtime.machine_id' => $this->directory.'/machine-id', 'runtime.machine_uuid' => $this->directory.'/absent-uuid', 'distribution.managed' => true]);
    }

    protected function tearDown(): void
    {
        if (isset($this->directory) && str_starts_with($this->directory, sys_get_temp_dir().'/courrier-constructor-test-')) {
            File::deleteDirectory($this->directory);
        }
        parent::tearDown();
    }

    private function tool(string $script, array $arguments): array
    {
        $process = proc_open([PHP_BINARY, base_path('tools/constructor/'.$script), ...$arguments], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $output, $error];
    }

    public function test_key_generation_never_overwrites_an_existing_identity(): void
    {
        $before = hash_file('sha256', $this->directory.'/keys/issuer-private.pem');
        [$exit] = $this->tool('generate-keys.php', ['--directory='.$this->directory.'/keys']);
        $this->assertSame(1, $exit);
        $this->assertSame($before, hash_file('sha256', $this->directory.'/keys/issuer-private.pem'));
    }

    public function test_issuer_honors_precise_client_dates_and_the_generated_file_installs_offline(): void
    {
        $runtime = app(ManagedRuntime::class);
        $request = $runtime->request();
        file_put_contents($this->directory.'/request.json', json_encode($request));
        $end = now()->addDays(30)->setTimezone('Africa/Douala')->format('Y-m-d\TH:i:sP');
        [$exit, $output, $error] = $this->tool('issue-license.php', ['--request='.$this->directory.'/request.json', '--key='.$this->directory.'/keys/issuer-private.pem',
            '--client=Fictional Customer', '--agency=Fictional Branch', '--expires='.$end, '--output='.$this->directory.'/document.json']);
        $this->assertSame(0, $exit, $output.$error);
        $document = file_get_contents($this->directory.'/document.json');
        $data = RuntimeSignature::verify($document, $this->publicKey);
        $this->assertSame((new \DateTimeImmutable($end))->getTimestamp(), $data['expires_at']);
        $this->assertSame('Fictional Customer', $data['client']);
        $this->assertSame('Fictional Branch', $data['agency']);
        $this->assertStringNotContainsString('PRIVATE KEY', $document);
        $runtime->install($document);
        $this->assertTrue($runtime->canWrite());
    }

    public function test_agency_copy_requires_activation_and_does_not_contain_constructor_material(): void
    {
        $target = $this->directory.'/agency';
        File::makeDirectory($target.'/config', 0755, true);
        File::makeDirectory($target.'/tools/constructor', 0755, true);
        File::makeDirectory($target.'/tests', 0755, true);
        file_put_contents($target.'/artisan', '<?php');
        file_put_contents($target.'/tools/constructor/MANUEL-CONSTRUCTEUR.md', 'Constructor-only material');
        file_put_contents($target.'/tests/example.php', '<?php');
        [$exit, $output, $error] = $this->tool('prepare-agency.php', ['--target='.$target, '--public-key='.$this->directory.'/keys/issuer-public.pem']);
        $this->assertSame(0, $exit, $output.$error);
        $this->assertSame(['managed' => true], require $target.'/config/distribution.php');
        $this->assertSame($this->publicKey, file_get_contents($target.'/config/runtime-public.pem'));
        $this->assertDirectoryDoesNotExist($target.'/tools/constructor');
        $this->assertDirectoryDoesNotExist($target.'/tests');
        $this->assertFileDoesNotExist($target.'/config/issuer-private.pem');
    }

    public function test_preparation_refuses_runtime_secrets_and_private_keys(): void
    {
        $target = $this->directory.'/agency';
        File::makeDirectory($target.'/config', 0755, true);
        file_put_contents($target.'/artisan', '<?php');
        file_put_contents($target.'/.env', 'SECRET=test-only');
        [$exit] = $this->tool('prepare-agency.php', ['--target='.$target, '--public-key='.$this->directory.'/keys/issuer-public.pem']);
        $this->assertSame(1, $exit);
        $this->assertFileDoesNotExist($target.'/config/distribution.php');
        unlink($target.'/.env');
        copy($this->directory.'/keys/issuer-private.pem', $target.'/accidental-private.pem');
        [$exit] = $this->tool('prepare-agency.php', ['--target='.$target, '--public-key='.$this->directory.'/keys/issuer-public.pem']);
        $this->assertSame(1, $exit);
        $this->assertFileDoesNotExist($target.'/config/distribution.php');
        if (PHP_OS_FAMILY !== 'Windows') {
            unlink($target.'/accidental-private.pem');
            $external = $this->directory.'/outside-policy.php';
            file_put_contents($external, '<?php return ["managed" => false];');
            symlink($external, $target.'/config/distribution.php');
            $before = file_get_contents($external);
            [$exit] = $this->tool('prepare-agency.php', ['--target='.$target, '--public-key='.$this->directory.'/keys/issuer-public.pem']);
            $this->assertSame(1, $exit);
            $this->assertSame($before, file_get_contents($external));
        }
    }
}
