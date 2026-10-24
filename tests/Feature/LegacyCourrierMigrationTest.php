<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyCourrierMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_statuses_are_preserved_and_mapped_before_the_column_is_removed(): void
    {
        Schema::table('courriers', fn (Blueprint $table) => $table->string('status', 30)->default('recu'));
        $user = User::factory()->create();
        $orders = ['recu' => 1, 'affecte' => 2, 'en_traitement' => 3, 'en_validation' => 4, 'valide' => 5, 'rejete' => 3, 'archive' => 6];

        foreach (array_keys($orders) as $index => $status) {
            DB::table('courriers')->insert([
                'type' => 'entrant', 'year' => 2026, 'seq' => $index + 1,
                'reference_interne' => 'LEGACY-'.($index + 1), 'objet' => 'Migration test',
                'status' => $status, 'created_by_user_id' => $user->id,
                'created_at' => '2026-01-10 09:00:00', 'updated_at' => '2026-01-11 10:00:00',
            ]);
        }

        $migration = require database_path('migrations/2026_07_29_162959_preserve_legacy_courrier_statuses.php');
        $migration->up();
        $migration->up(); // Repeating the conversion must preserve identifiers and original statuses.

        foreach ($orders as $status => $order) {
            $row = DB::table('courriers')->where('status', $status)->first();
            $step = DB::table('workflow_etapes')->where('id', $row->workflow_etape_id)->first();
            $this->assertSame($status, $row->legacy_status);
            $this->assertSame($order, (int) $step->ordre);
            $this->assertSame((int) $step->bureau_id, (int) $row->bureau_actuel_id);
        }
        $this->assertSame('2026-01-11 10:00:00', DB::table('courriers')->where('status', 'archive')->value('archived_at'));
        $this->assertSame(7, DB::table('courriers')->count());

        $dropStatus = require database_path('migrations/2026_07_29_163001_drop_status_from_courriers_table.php');
        $dropStatus->up();
        $this->assertFalse(Schema::hasColumn('courriers', 'status'));
        $this->assertSame(7, DB::table('courriers')->whereNotNull('legacy_status')->count());
        $dropStatus->down();
        $migration->down();
        foreach (array_keys($orders) as $index => $status) {
            $this->assertSame($status, DB::table('courriers')->where('seq', $index + 1)->value('status'));
        }
    }

    public function test_unknown_status_stops_conversion_without_discarding_data(): void
    {
        Schema::table('courriers', fn (Blueprint $table) => $table->string('status', 30)->default('recu'));
        $user = User::factory()->create();
        DB::table('courriers')->insert([
            'type' => 'entrant', 'year' => 2026, 'seq' => 1,
            'reference_interne' => 'LEGACY-UNKNOWN', 'objet' => 'Unknown status',
            'status' => 'custom_status', 'created_by_user_id' => $user->id,
        ]);

        $migration = require database_path('migrations/2026_07_29_162959_preserve_legacy_courrier_statuses.php');
        try {
            $migration->up();
            $this->fail('Unknown status must interrupt the migration.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Statut de courrier inconnu', $exception->getMessage());
            $this->assertSame('custom_status', DB::table('courriers')->value('status'));
            $this->assertNull(DB::table('courriers')->value('workflow_etape_id'));
        }
    }
}
