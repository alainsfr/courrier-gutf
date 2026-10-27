<details class="rounded-xl border border-gray-200 bg-slate-50 p-4 text-sm">
    <summary class="cursor-pointer font-semibold text-gray-800">{{ __('users.roles_guide') }}</summary>
    <p class="mb-3 mt-3 text-xs text-gray-500">{{ __('users.roles_hint') }}</p>
    <dl class="space-y-3">
        @foreach(['super-admin', 'admin', 'agent_courrier', 'agent_traitement', 'validateur', 'consultation', 'directeur', 'auditor'] as $role)
            <div><dt class="font-semibold text-gray-700">{{ __('users.roles.'.$role) }}</dt><dd class="m-0 mt-0.5 text-xs text-gray-500">{{ __('users.role_descriptions.'.$role) }}</dd></div>
        @endforeach
    </dl>
</details>
