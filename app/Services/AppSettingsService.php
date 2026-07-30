<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AppSettingsService
{
    public const CACHE_KEY = 'app_settings.v1';

    /**
     * @return array<string, string|null>
     */
    public function defaults(): array
    {
        $inst = config('institution', []);

        return [
            'app_name' => (string) ($inst['app_label'] ?? config('app.name', 'COURRIER GUTF')),
            'app_tagline' => (string) ($inst['structure_short_fr'] ?? ''),
            'country' => (string) ($inst['country'] ?? ''),
            'motto' => (string) ($inst['motto'] ?? ''),
            'ministry' => (string) ($inst['ministry'] ?? ''),
            'structure' => (string) ($inst['structure'] ?? ''),
            'support_email' => '',
            'primary_color' => '#17384b',
            'accent_color' => '#dc633a',
            'logo_path' => null,
            'favicon_path' => null,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            $stored = AppSetting::query()->pluck('value', 'key')->all();
            $merged = array_merge($this->defaults(), $stored);

            foreach ($merged as $key => $value) {
                if ($value === null || $value === '') {
                    $merged[$key] = $this->defaults()[$key] ?? $value;
                }
            }

            // Keep nullable media paths truly empty when unset
            foreach (['logo_path', 'favicon_path'] as $mediaKey) {
                if (! array_key_exists($mediaKey, $stored) || $stored[$mediaKey] === null || $stored[$mediaKey] === '') {
                    $merged[$mediaKey] = null;
                }
            }

            return $merged;
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return $all[$key] ?? $default;
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            AppSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        $this->forgetCache();
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function logoUrl(): ?string
    {
        $path = $this->get('logo_path');

        return $this->publicUrl(is_string($path) ? $path : null);
    }

    public function faviconUrl(): ?string
    {
        $path = $this->get('favicon_path');

        return $this->publicUrl(is_string($path) ? $path : null);
    }

    public function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
