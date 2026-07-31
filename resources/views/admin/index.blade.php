<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-bold text-gray-900">{{ __('nav.administration') }}</h1>
            <p class="mb-0 text-sm text-gray-500">Gouvernance · utilisateurs, bureaux & paramètres</p>
        </div>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <a href="{{ route('admin.users.index') }}"
           class="group block rounded-xl border border-gray-200 bg-white p-6 shadow-sm no-underline transition hover:border-[color-mix(in_srgb,var(--gutf-primary)_30%,white)] hover:shadow-md">
            <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[color-mix(in_srgb,var(--gutf-primary)_10%,white)]" style="color: var(--gutf-primary)">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
            </div>
            <h2 class="mb-1 text-base font-bold text-gray-900" style="color: inherit">{{ __('users.title') }}</h2>
            <p class="mb-0 text-sm text-gray-500">Rôles, bureau, photo de profil et accès plateforme.</p>
        </a>

        <a href="{{ route('admin.bureaux.index') }}"
           class="group block rounded-xl border border-gray-200 bg-white p-6 shadow-sm no-underline transition hover:shadow-md">
            <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl" style="background: color-mix(in srgb, var(--gutf-accent) 12%, white); color: var(--gutf-accent)">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                </svg>
            </div>
            <h2 class="mb-1 text-base font-bold text-gray-900">{{ __('bureaux.title') }}</h2>
            <p class="mb-0 text-sm text-gray-500">Services métier du workflow (nom, description).</p>
        </a>

        <a href="{{ route('admin.settings.edit') }}"
           class="group block rounded-xl border border-gray-200 bg-white p-6 shadow-sm no-underline transition hover:shadow-md">
            <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.174.1.31.245.41.41.112.183.164.39.146.599l-.213 1.281c-.09.542-.56.94-1.11.94h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281a1.125 1.125 0 0 0-.645-.87 1.125 1.125 0 0 1-.41-.41 1.125 1.125 0 0 1-.146-.599l.213-1.281ZM12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z" />
                </svg>
            </div>
            <h2 class="mb-1 text-base font-bold text-gray-900">{{ __('settings.title') }}</h2>
            <p class="mb-0 text-sm text-gray-500">Logo, nom, textes institutionnels et couleurs du thème.</p>
        </a>
        <a href="{{ route('admin.backups.index') }}"
           class="group block rounded-xl border border-gray-200 bg-white p-6 shadow-sm no-underline transition hover:shadow-md">
            <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-sky-50 text-sky-700">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375" />
                </svg>
            </div>
            <h2 class="mb-1 text-base font-bold text-gray-900">{{ __('backups.nav') }}</h2>
            <p class="mb-0 text-sm text-gray-500">{{ __('backups.subtitle') }}</p>
        </a>
    </div>
</x-app-layout>
