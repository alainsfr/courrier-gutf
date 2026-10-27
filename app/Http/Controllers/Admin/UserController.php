<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bureau;
use App\Models\User;
use App\Services\AdminAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
            'is_active' => ['required', 'boolean'],
            'avatar' => ['nullable', 'file', 'extensions:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'locale' => $validated['locale'],
            'bureau_id' => $validated['bureau_id'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_active' => $request->boolean('is_active'),
            'email_verified_at' => now(),
        ]);

        if ($request->hasFile('avatar')) {
            $user->forceFill([
                'avatar_path' => $request->file('avatar')->store('avatars', 'public'),
            ])->save();
        }

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

    public function update(Request $request, User $user, AdminAccessService $access): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'lowercase', Rule::unique('users', 'email')->ignore($user->id)],
            'locale' => ['required', Rule::in(['fr', 'en'])],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'bureau_id' => ['nullable', 'integer', 'exists:bureaux,id'],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'avatar' => ['nullable', 'file', 'extensions:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', Rule::in(['1', '0', 1, 0, true, false])],
        ]);

        $isActive = $request->boolean('is_active');

        if ((int) $user->id === (int) $request->user()->id && ! $isActive) {
            return back()
                ->withInput()
                ->withErrors(['is_active' => __('users.cannot_deactivate_self')]);
        }

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'locale' => $validated['locale'],
            'bureau_id' => $validated['bureau_id'] ?? null,
            'is_active' => $isActive,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $access->change($user, function (User $user) use ($request, $payload, $validated) {
            if ($request->boolean('remove_avatar') && $user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
                $payload['avatar_path'] = null;
            }

            if ($request->hasFile('avatar')) {
                if ($user->avatar_path) {
                    Storage::disk('public')->delete($user->avatar_path);
                }
                $payload['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
            }

            $user->update($payload);
            $user->syncRoles([$validated['role']]);
        }, role: $validated['role'], active: $isActive);

        return redirect()
            ->route('admin.users.index')
            ->with('status', __('users.updated'));
    }

    public function toggleActive(Request $request, User $user, AdminAccessService $access): RedirectResponse
    {
        if ((int) $user->id === (int) $request->user()->id) {
            return back()->withErrors(['user' => __('users.cannot_deactivate_self')]);
        }

        $access->change($user, function (User $current) {
            $current->forceFill(['is_active' => ! $current->is_active])->save();
        }, toggle: true);
        $user->refresh();

        return back()->with(
            'status',
            $user->is_active ? __('users.activated') : __('users.deactivated')
        );
    }

    public function destroy(User $user, AdminAccessService $access): RedirectResponse
    {
        if ((int) $user->id === (int) auth()->id()) {
            return back()->withErrors(['user' => __('users.cannot_delete_self')]);
        }

        $access->change($user, function (User $user) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $user->delete();
        }, delete: true);

        return redirect()
            ->route('admin.users.index')
            ->with('status', __('users.deleted'));
    }
}
