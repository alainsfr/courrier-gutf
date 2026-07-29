<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bureau;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BureauController extends Controller
{
    public function index(): View
    {
        $bureaux = Bureau::query()
            ->withCount('users')
            ->orderBy('ordre')
            ->orderBy('nom')
            ->paginate(20);

        return view('admin.bureaux.index', compact('bureaux'));
    }

    public function create(): View
    {
        return view('admin.bureaux.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'code' => ['nullable', 'string', 'max:50', 'unique:bureaux,code'],
            'is_active' => ['nullable', 'boolean'],
            'ordre' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $code = $validated['code'] ?? null;
        if (! $code) {
            $base = Str::upper(Str::slug($validated['nom'], '_'));
            $base = $base !== '' ? $base : 'BUREAU';
            $code = $base;
            $i = 1;
            while (Bureau::where('code', $code)->exists()) {
                $code = $base.'_'.$i++;
            }
        }

        Bureau::create([
            'nom' => $validated['nom'],
            'description' => $validated['description'] ?? null,
            'code' => $code,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'ordre' => (int) ($validated['ordre'] ?? 0),
        ]);

        return redirect()
            ->route('admin.bureaux.index')
            ->with('status', __('bureaux.created'));
    }

    public function edit(Bureau $bureau): View
    {
        return view('admin.bureaux.edit', compact('bureau'));
    }

    public function update(Request $request, Bureau $bureau): RedirectResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'code' => ['required', 'string', 'max:50', Rule::unique('bureaux', 'code')->ignore($bureau->id)],
            'is_active' => ['nullable', 'boolean'],
            'ordre' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $bureau->update([
            'nom' => $validated['nom'],
            'description' => $validated['description'] ?? null,
            'code' => $validated['code'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'ordre' => (int) ($validated['ordre'] ?? 0),
        ]);

        return redirect()
            ->route('admin.bureaux.index')
            ->with('status', __('bureaux.updated'));
    }

    public function destroy(Bureau $bureau): RedirectResponse
    {
        if ($bureau->users()->exists() || $bureau->etapes()->exists()) {
            return back()->withErrors([
                'bureau' => __('bureaux.cannot_delete_linked'),
            ]);
        }

        $bureau->delete();

        return redirect()
            ->route('admin.bureaux.index')
            ->with('status', __('bureaux.deleted'));
    }
}
