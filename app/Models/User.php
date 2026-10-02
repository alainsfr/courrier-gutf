<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'signature_pin',
        'locale',
        'avatar_path',
        'bureau_id',
        'is_active',
        'last_login_ip',
        'last_login_at',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'signature_pin',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function bureau(): BelongsTo
    {
        return $this->belongsTo(Bureau::class, 'bureau_id');
    }

    public function avatarUrl(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        if (! Storage::disk('public')->exists($this->avatar_path)) {
            return null;
        }

        $url = Storage::disk('public')->url($this->avatar_path);

        try {
            $version = Storage::disk('public')->lastModified($this->avatar_path);
        } catch (\Throwable) {
            $version = null;
        }

        return $version ? $url.(str_contains($url, '?') ? '&' : '?').'v='.$version : $url;
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->name)) ?: [];

        return strtoupper(
            substr($words[0] ?? 'U', 0, 1).
            (isset($words[1]) ? substr($words[1], 0, 1) : '')
        );
    }
}
