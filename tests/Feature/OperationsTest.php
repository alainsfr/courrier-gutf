<?php

namespace Tests\Feature;

use App\Jobs\OperationsQueueProbe;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OperationsTest extends TestCase
{
    use RefreshDatabase;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = sys_get_temp_dir().'/courrier-operations-'.bin2hex(random_bytes(8));
        $this->app->useStoragePath($this->path);
        config(['queue.default' => 'database']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->path);
        parent::tearDown();
    }

    public function test_probe_is_consumed_by_the_real_database_worker(): void
    {
        $this->artisan('app:operations-heartbeat')->assertSuccessful();
        $this->assertDatabaseCount('jobs', 1);
        $this->assertFileDoesNotExist(storage_path('framework/operations/queue.json'));
        $this->artisan('queue:work', ['--once' => true, '--sleep' => 0, '--tries' => 1])->assertSuccessful();
        $this->assertDatabaseCount('jobs', 0);
        $probe = json_decode(File::get(storage_path('framework/operations/queue.json')), true);
        $this->assertSame('database', $probe['connection']);
        $this->assertNotEmpty($probe['completed_at']);
    }

    public function test_status_detects_missing_stale_and_failed_runs(): void
    {
        $this->artisan('app:operations-status', ['--json' => true])->assertFailed();
        File::ensureDirectoryExists(storage_path('framework/operations'));
        foreach (['scheduler', 'queue', 'scheduler-runner', 'queue-runner'] as $name) {
            File::put(storage_path('framework/operations/'.$name.'.json'), json_encode([
                'completed_at' => now()->toIso8601String(), 'exit_code' => 0,
            ]));
        }
        $this->artisan('app:operations-status', ['--json' => true])->assertSuccessful();
        File::put(storage_path('framework/operations/queue.json'), json_encode(['completed_at' => now()->subMinutes(11)->toIso8601String()]));
        $this->artisan('app:operations-status', ['--json' => true])->assertFailed();
        File::put(storage_path('framework/operations/queue.json'), json_encode(['completed_at' => now()->toIso8601String()]));
        File::put(storage_path('framework/operations/queue-runner.json'), json_encode(['completed_at' => now()->toIso8601String(), 'exit_code' => 1]));
        $this->artisan('app:operations-status', ['--json' => true])->assertFailed();
    }

    public function test_scheduler_dispatches_the_probe_at_its_due_time(): void
    {
        Queue::fake();
        $this->travelTo(now()->startOfHour()->addMinutes(10));
        $events = collect(app(Schedule::class)->events());
        $probe = $events->first(fn ($e) => str_contains($e->command ?? '', 'app:operations-heartbeat'));
        $this->assertTrue($probe->isDue($this->app));
        $this->artisan('app:operations-heartbeat')->assertSuccessful();
        Queue::assertPushed(OperationsQueueProbe::class);
        $this->assertFileExists(storage_path('framework/operations/scheduler.json'));
        $this->travel(1)->minutes();
        $this->assertFalse($probe->isDue($this->app));
        $deadline = $events->first(fn ($e) => str_contains($e->command ?? '', 'app:check-delais'));
        $this->assertSame('30 7 * * *', $deadline->expression);
        $this->assertSame('Africa/Douala', $deadline->timezone);
        $this->assertTrue($deadline->withoutOverlapping);
    }
}
