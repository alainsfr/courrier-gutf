<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserAdminController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($q !== '', function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
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
        $roles = Role::query()->orderBy('name')->get(['name']);
        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'lowercase', 'unique:users,email'],
            'locale' => ['required', Rule::in(['fr', 'en'])],
            'role' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'locale' => $validated['locale'],
            'password' => Hash::make($validated['password']),
            'is_active' => (bool)($validated['is_active'] ?? true),
            'email_verified_at' => now(), // V1: simplifie l’accès (modifiable si besoin)
        ]);

        // V1: un rôle principal
        $user->syncRoles([$validated['role']]);

        return redirect()->route('admin.users.index')->with('status', __('users.created'));
    }

    public function edit(User $user): View
    {
        $roles = Role::query()->orderBy('name')->get(['name']);
        $currentRole = $user->getRoleNames()->first();

        return view('admin.users.edit', compact('user', 'roles', 'currentRole'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'lowercase', Rule::unique('users', 'email')->ignore($user->id)],
            'locale' => ['required', Rule::in(['fr', 'en'])],
            'role' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'locale' => $validated['locale'],
            'is_active' => (bool)($validated['is_active'] ?? false),
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()->route('admin.users.index')->with('status', __('users.updated'));
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $user->is_active = ! (bool) $user->is_active;
        $user->save();

        return back()->with('status', $user->is_active ? __('users.activated') : __('users.deactivated'));
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return back()->with('status', __('users.password_reset'));
    }
}
