<?php

namespace Tests\Feature;

use App\Models\Courrier;
use App\Models\CourrierAttachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;

class MigrateCourrierFilesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_moves_double_prefixed_files_and_updates_database(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $courrier = Courrier::create([
            'type' => 'entrant',
            'year' => (int) now()->year,
            'seq' => 10,
            'reference_interne' => 'GUTF-ENT-' . now()->year . '-000010',
            'objet' => 'Migration fichiers',
            'created_by_user_id' => $user->id,
        ]);

        $oldPath = "private/courriers/{$courrier->id}/scan.pdf";
        $newPath = "courriers/{$courrier->id}/scan.pdf";

        Storage::disk('local')->put($oldPath, 'contenu-scan');

        $attachment = CourrierAttachment::create([
            'courrier_id' => $courrier->id,
            'uploaded_by_user_id' => $user->id,
            'source' => 'upload',
            'original_name' => 'scan.pdf',
            'stored_name' => 'scan.pdf',
            'mime' => 'application/pdf',
            'size' => 12,
            'path' => $oldPath,
        ]);

        $exit = Artisan::call('app:migrate-courrier-files');

        $this->assertSame(0, $exit);
        $this->assertFalse(Storage::disk('local')->exists($oldPath));
        $this->assertTrue(Storage::disk('local')->exists($newPath));
        $this->assertSame($newPath, $attachment->fresh()->path);
    }

    public function test_dry_run_does_not_modify_files_or_database(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $courrier = Courrier::create([
            'type' => 'entrant',
            'year' => (int) now()->year,
            'seq' => 11,
            'reference_interne' => 'GUTF-ENT-' . now()->year . '-000011',
            'objet' => 'Dry run',
            'created_by_user_id' => $user->id,
        ]);

        $oldPath = "private/courriers/{$courrier->id}/dry.pdf";
        Storage::disk('local')->put($oldPath, 'dry');

        $attachment = CourrierAttachment::create([
            'courrier_id' => $courrier->id,
            'uploaded_by_user_id' => $user->id,
            'source' => 'upload',
            'original_name' => 'dry.pdf',
            'stored_name' => 'dry.pdf',
            'mime' => 'application/pdf',
            'size' => 3,
            'path' => $oldPath,
        ]);

        Artisan::call('app:migrate-courrier-files', ['--dry-run' => true]);

        $this->assertTrue(Storage::disk('local')->exists($oldPath));
        $this->assertFalse(Storage::disk('local')->exists("courriers/{$courrier->id}/dry.pdf"));
        $this->assertSame($oldPath, $attachment->fresh()->path);
    }
}
