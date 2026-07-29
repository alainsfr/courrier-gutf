<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bureau;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->with(['roles:id,name', 'bureau:id,nom'])
            ->when($q !== '', function ($sub) use ($q) {
                $sub->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'q' => $q,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => Role::query()->orderBy('name')->get(['name']),
            'bureaux' => Bureau::query()->orderBy('ordre')->orderBy('nom')->get(['id', 'nom']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'lowercase', 'unique:users,email'],
            'locale' => ['required', Rule::in(['fr', 'en'])],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'bureau_id' => ['nullable', 'integer', 'exists:bureaux,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'locale' => $validated['locale'],
            'bureau_id' => $validated['bureau_id'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', __('users.created'));
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user->load('bureau:id,nom'),
            'roles' => Role::query()->orderBy('name')->get(['name']),
            'bureaux' => Bureau::query()->orderBy('ordre')->orderBy('nom')->get(['id', 'nom']),
            'currentRole' => $user->getRoleNames()->first(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'lowercase', Rule::unique('users', 'email')->ignore($user->id)],
            'locale' => ['required', Rule::in(['fr', 'en'])],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'bureau_id' => ['nullable', 'integer', 'exists:bureaux,id'],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'locale' => $validated['locale'],
            'bureau_id' => $validated['bureau_id'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);
        $user->syncRoles([$validated['role']]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', __('users.updated'));
    }

    public function destroy(User $user): RedirectResponse
    {
        if ((int) $user->id === (int) auth()->id()) {
            return back()->withErrors(['user' => __('users.cannot_delete_self')]);
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', __('users.deleted'));
    }
}
