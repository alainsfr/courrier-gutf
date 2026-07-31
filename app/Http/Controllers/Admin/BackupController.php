<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function index(): View
    {
        $backups = $this->listBackupArchives();

        return view('admin.backups.index', [
            'backups' => $backups,
            'backupName' => (string) config('backup.backup.name'),
        ]);
    }

    public function download(string $fileName): BinaryFileResponse|StreamedResponse
    {
        $safeName = basename($fileName);

        abort_unless(
            (bool) preg_match('/\.zip$/i', $safeName),
            404
        );

        $relative = $this->relativePathFor($safeName);
        abort_unless($relative && Storage::disk('backups')->exists($relative), 404);

        return Storage::disk('backups')->download($relative, $safeName);
    }

    /**
     * @return Collection<int, array{name: string, size: int, size_human: string, date: \Carbon\CarbonInterface|null, path: string}>
     */
    private function listBackupArchives(): Collection
    {
        $disk = Storage::disk('backups');
        $backupName = trim((string) config('backup.backup.name'), '/');

        $files = collect($disk->allFiles())
            ->filter(fn (string $path) => str_ends_with(strtolower($path), '.zip'))
            ->when($backupName !== '', function (Collection $paths) use ($backupName) {
                return $paths->filter(
                    fn (string $path) => str_starts_with($path, $backupName.'/')
                        || str_starts_with($path, $backupName.'\\')
                        || ! str_contains($path, '/')
                );
            })
            ->values();

        return $files
            ->map(function (string $path) use ($disk) {
                $name = basename($path);
                $size = (int) $disk->size($path);
                $timestamp = $disk->lastModified($path);

                return [
                    'name' => $name,
                    'path' => $path,
                    'size' => $size,
                    'size_human' => $this->humanFilesize($size),
                    'date' => $timestamp ? \Carbon\Carbon::createFromTimestamp($timestamp) : null,
                ];
            })
            ->sortByDesc(fn (array $item) => $item['date']?->timestamp ?? 0)
            ->values();
    }

    private function relativePathFor(string $safeName): ?string
    {
        $disk = Storage::disk('backups');
        $backupName = trim((string) config('backup.backup.name'), '/');

        $candidates = array_values(array_unique(array_filter([
            $safeName,
            $backupName !== '' ? $backupName.'/'.$safeName : null,
        ])));

        foreach ($candidates as $candidate) {
            if ($disk->exists($candidate)) {
                return $candidate;
            }
        }

        // Recherche récursive limitée au nom de fichier exact
        foreach ($disk->allFiles() as $path) {
            if (basename($path) === $safeName) {
                return $path;
            }
        }

        return null;
    }

    private function humanFilesize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' o';
        }

        $units = ['Ko', 'Mo', 'Go', 'To'];
        $i = -1;
        do {
            $bytes /= 1024;
            $i++;
        } while ($bytes >= 1024 && $i < count($units) - 1);

        return number_format($bytes, $bytes >= 10 ? 0 : 1, ',', ' ').' '.$units[$i];
    }
}
