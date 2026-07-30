<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CourrierAttachment extends Model
{
    protected $fillable = [
        'courrier_id',
        'uploaded_by_user_id',
        'source',
        'original_name',
        'stored_name',
        'mime',
        'size',
        'path',
    ];

    public function courrier(): BelongsTo
    {
        return $this->belongsTo(Courrier::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /**
     * Chemin relatif utilisable sur le disk "local" (tolère anciens préfixes private/).
     */
    public function resolvedStoragePath(): ?string
    {
        $disk = Storage::disk('local');
        $raw = ltrim(str_replace('\\', '/', (string) $this->path), '/');

        $candidates = array_values(array_unique(array_filter([
            $raw,
            preg_replace('#^private/#', '', $raw),
            str_starts_with($raw, 'private/') ? $raw : 'private/'.$raw,
            preg_replace('#^private/private/#', 'private/', $raw),
        ])));

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && $disk->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public function absolutePath(): ?string
    {
        $relative = $this->resolvedStoragePath();
        if (! $relative) {
            return null;
        }

        $full = Storage::disk('local')->path($relative);

        return is_file($full) ? $full : null;
    }

    public function isPdf(): bool
    {
        $mime = strtolower((string) $this->mime);
        $name = strtolower((string) $this->original_name);

        return str_contains($mime, 'pdf') || str_ends_with($name, '.pdf');
    }

    public function isImage(): bool
    {
        $mime = strtolower((string) $this->mime);
        $name = strtolower((string) $this->original_name);

        return str_starts_with($mime, 'image/')
            || str_ends_with($name, '.png')
            || str_ends_with($name, '.jpg')
            || str_ends_with($name, '.jpeg')
            || str_ends_with($name, '.gif')
            || str_ends_with($name, '.webp');
    }
}
