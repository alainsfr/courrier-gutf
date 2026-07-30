<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AppSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private readonly AppSettingsService $settings
    ) {}

    public function edit(): View
    {
        return view('admin.settings.edit', [
            'settings' => $this->settings->all(),
            'logoUrl' => $this->settings->logoUrl(),
            'faviconUrl' => $this->settings->faviconUrl(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:120'],
            'app_tagline' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:120'],
            'motto' => ['nullable', 'string', 'max:120'],
            'ministry' => ['nullable', 'string', 'max:255'],
            'structure' => ['nullable', 'string', 'max:500'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'primary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'favicon' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,ico', 'max:1024'],
            'remove_logo' => ['nullable', Rule::in(['1', '0', 1, 0, true, false])],
            'remove_favicon' => ['nullable', Rule::in(['1', '0', 1, 0, true, false])],
        ]);

        $payload = [
            'app_name' => $validated['app_name'],
            'app_tagline' => $validated['app_tagline'] ?? '',
            'country' => $validated['country'] ?? '',
            'motto' => $validated['motto'] ?? '',
            'ministry' => $validated['ministry'] ?? '',
            'structure' => $validated['structure'] ?? '',
            'support_email' => $validated['support_email'] ?? '',
            'primary_color' => strtolower($validated['primary_color']),
            'accent_color' => strtolower($validated['accent_color']),
        ];

        $current = $this->settings->all();

        if ($request->boolean('remove_logo') && ! empty($current['logo_path'])) {
            Storage::disk('public')->delete($current['logo_path']);
            $payload['logo_path'] = null;
        }

        if ($request->boolean('remove_favicon') && ! empty($current['favicon_path'])) {
            Storage::disk('public')->delete($current['favicon_path']);
            $payload['favicon_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if (! empty($current['logo_path'])) {
                Storage::disk('public')->delete($current['logo_path']);
            }
            $payload['logo_path'] = $request->file('logo')->store('branding', 'public');
        }

        if ($request->hasFile('favicon')) {
            if (! empty($current['favicon_path'])) {
                Storage::disk('public')->delete($current['favicon_path']);
            }
            $payload['favicon_path'] = $request->file('favicon')->store('branding', 'public');
        }

        $this->settings->setMany($payload);

        // Keep Laravel app name in sync for mail/PDF titles when possible
        config(['app.name' => $payload['app_name']]);

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', __('settings.saved'));
    }
}
