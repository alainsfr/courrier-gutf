<?php

namespace App\Console\Commands;

use App\Models\CourrierAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MigrateCourrierFilesCommand extends Command
{
    protected $signature = 'app:migrate-courrier-files
                            {--dry-run : Simule sans déplacer ni mettre à jour la BDD}
                            {--chunk=100 : Taille des lots Eloquent}';

    protected $description = 'Déplace les PJ du double préfixe private/courriers vers courriers/ et met à jour les chemins en BDD';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));
        $disk = Storage::disk('local');

        $this->info($dryRun
            ? 'Mode dry-run : aucune modification ne sera écrite.'
            : 'Migration des fichiers courriers…');

        $moved = 0;
        $updatedDbOnly = 0;
        $skipped = 0;
        $missing = 0;
        $errors = 0;

        CourrierAttachment::query()
            ->where('path', 'like', 'private/courriers/%')
            ->orderBy('id')
            ->chunkById($chunk, function ($attachments) use (
                $disk,
                $dryRun,
                &$moved,
                &$updatedDbOnly,
                &$skipped,
                &$missing,
                &$errors
            ) {
                foreach ($attachments as $attachment) {
                    $oldPath = (string) $attachment->path;
                    $newPath = $this->normalizePath($oldPath);

                    if ($newPath === $oldPath) {
                        $skipped++;
                        continue;
                    }

                    try {
                        $result = $this->migrateOne($attachment, $oldPath, $newPath, $disk, $dryRun);

                        match ($result) {
                            'moved' => $moved++,
                            'db_only' => $updatedDbOnly++,
                            'missing' => $missing++,
                            default => $skipped++,
                        };
                    } catch (\Throwable $e) {
                        $errors++;
                        $this->error("ID {$attachment->id} : {$e->getMessage()}");
                    }
                }
            });

        // Fichiers orphelins encore présents sous private/courriers sur le disk local
        $orphanMoved = $this->migrateOrphanFiles($disk, $dryRun);

        $this->newLine();
        $this->table(
            ['Métrique', 'Total'],
            [
                ['Déplacés + BDD mise à jour', $moved],
                ['BDD seule (fichier déjà au bon endroit)', $updatedDbOnly],
                ['Fichiers orphelins déplacés', $orphanMoved],
                ['Introuvables', $missing],
                ['Ignorés', $skipped],
                ['Erreurs', $errors],
            ]
        );

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * private/courriers/{id}/file.pdf → courriers/{id}/file.pdf
     */
    private function normalizePath(string $path): string
    {
        $normalized = str_replace('\\', '/', ltrim($path, '/'));

        if (str_starts_with($normalized, 'private/courriers/')) {
            return substr($normalized, strlen('private/'));
        }

        return $normalized;
    }

    /**
     * @return 'moved'|'db_only'|'missing'|'skipped'
     */
    private function migrateOne(
        CourrierAttachment $attachment,
        string $oldPath,
        string $newPath,
        $disk,
        bool $dryRun
    ): string {
        $hasOld = $disk->exists($oldPath);
        $hasNew = $disk->exists($newPath);

        if (! $hasOld && ! $hasNew) {
            $this->warn("Manquant ID {$attachment->id} : {$oldPath}");

            return 'missing';
        }

        if ($hasOld && ! $hasNew) {
            if (! $dryRun) {
                $disk->makeDirectory(dirname($newPath));
                // move() si dispo, sinon copy+delete
                if (method_exists($disk, 'move')) {
                    $disk->move($oldPath, $newPath);
                } else {
                    $disk->copy($oldPath, $newPath);
                    $disk->delete($oldPath);
                }

                $this->updateAttachmentPath($attachment, $newPath);
            }

            $this->line(($dryRun ? '[dry-run] ' : '')."Move {$oldPath} → {$newPath}");

            return 'moved';
        }

        // Fichier déjà au nouvel emplacement (ou les deux existent) : on aligne la BDD
        if (! $dryRun) {
            $this->updateAttachmentPath($attachment, $newPath);

            if ($hasOld && $hasNew && $oldPath !== $newPath) {
                $disk->delete($oldPath);
            }
        }

        $this->line(($dryRun ? '[dry-run] ' : '')."DB {$attachment->id} : {$oldPath} → {$newPath}");

        return 'db_only';
    }

    private function updateAttachmentPath(CourrierAttachment $attachment, string $newPath): void
    {
        DB::transaction(function () use ($attachment, $newPath) {
            $attachment->path = $newPath;
            $attachment->stored_name = basename($newPath);
            $attachment->save();
        });
    }

    /**
     * Déplace d'éventuels fichiers physiques restants sous private/courriers
     * même sans ligne BDD (nettoyage).
     */
    private function migrateOrphanFiles($disk, bool $dryRun): int
    {
        if (! $disk->exists('private/courriers')) {
            return 0;
        }

        $count = 0;

        foreach ($disk->allFiles('private/courriers') as $oldPath) {
            $newPath = $this->normalizePath($oldPath);

            if ($newPath === $oldPath || $disk->exists($newPath)) {
                continue;
            }

            // Ne pas toucher si une ligne BDD pointe encore vers l'ancien chemin
            // (sera géré par le chunk ci-dessus) — ici uniquement orphelins
            $stillReferenced = CourrierAttachment::query()
                ->where('path', $oldPath)
                ->exists();

            if ($stillReferenced) {
                continue;
            }

            if (! $dryRun) {
                $disk->makeDirectory(dirname($newPath));
                if (method_exists($disk, 'move')) {
                    $disk->move($oldPath, $newPath);
                } else {
                    $disk->copy($oldPath, $newPath);
                    $disk->delete($oldPath);
                }
            }

            $this->line(($dryRun ? '[dry-run] ' : '')."Orphelin {$oldPath} → {$newPath}");
            $count++;
        }

        return $count;
    }
}
