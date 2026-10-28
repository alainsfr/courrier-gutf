<?php

namespace Tests\Feature;

use App\Models\Bureau;
use App\Models\Courrier;
use App\Models\User;
use App\Models\WorkflowEtape;
use Database\Seeders\BureauSeeder;
use Database\Seeders\WorkflowEtapesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeadlineAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_deadline_check_is_idempotent_and_ignores_completed_archived_and_future_cases(): void
    {
        $this->seed([BureauSeeder::class, WorkflowEtapesSeeder::class]);
        $office = Bureau::where('code', 'enregistrement_fiscal')->firstOrFail();
        $worker = User::factory()->create(['is_active' => true, 'bureau_id' => $office->id]);
        $inactive = User::factory()->create(['is_active' => false, 'bureau_id' => $office->id]);
        $other = User::factory()->create(['is_active' => true, 'bureau_id' => Bureau::where('code', 'accueil')->value('id')]);
        $base = [
            'type' => 'entrant', 'year' => 2026, 'objet' => 'Dossier fictif de vérification des délais',
            'created_by_user_id' => $worker->id, 'bureau_actuel_id' => $office->id,
            'workflow_etape_id' => WorkflowEtape::where('ordre', 3)->value('id'),
            'date_limite' => now()->subDay(), 'is_late' => false,
        ];
        $create = fn ($seq, $extra = []) => Courrier::create(array_merge($base, ['seq' => $seq, 'reference_interne' => 'TEST-'.$seq], $extra));
        $late = $create(1);
        $complete = $create(2, ['workflow_etape_id' => WorkflowEtape::where('ordre', 5)->value('id')]);
        $archived = $create(3, ['archived_at' => now()]);
        $future = $create(4, ['date_limite' => now()->addDay()]);
        $this->artisan('app:check-delais', ['--dry-run' => true])->assertSuccessful();
        $this->assertFalse($late->fresh()->is_late);
        $this->assertDatabaseCount('notifications', 0);
        $this->artisan('app:check-delais')->assertSuccessful();
        $this->assertTrue($late->fresh()->is_late);
        foreach ([$complete, $archived, $future] as $case) {
            $this->assertFalse($case->fresh()->is_late);
        }
        $this->assertSame(1, $worker->notifications()->count());
        $this->assertSame(0, $inactive->notifications()->count());
        $this->assertSame(0, $other->notifications()->count());
        $this->artisan('app:check-delais')->assertSuccessful();
        $this->assertSame(1, $worker->notifications()->count());
    }
}
