<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-bold text-gray-900">{{ __('nav.administration') }}</h1>
            <p class="mb-0 text-sm text-gray-500">Gouvernance · utilisateurs & bureaux</p>
        </div>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2">
        <a href="{{ route('admin.users.index') }}"
           class="group block rounded-xl border border-gray-200 bg-white p-6 shadow-sm no-underline transition hover:border-[#17384b]/30 hover:shadow-md">
            <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[#17384b]/10 text-[#17384b]">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
            </div>
            <h2 class="mb-1 text-base font-bold text-gray-900 group-hover:text-[#17384b]">{{ __('users.title') }}</h2>
            <p class="mb-0 text-sm text-gray-500">Rôles Spatie, bureau d’affectation et accès plateforme.</p>
        </a>

        <a href="{{ route('admin.bureaux.index') }}"
           class="group block rounded-xl border border-gray-200 bg-white p-6 shadow-sm no-underline transition hover:border-[#17384b]/30 hover:shadow-md">
            <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[#dc633a]/10 text-[#dc633a]">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                </svg>
            </div>
            <h2 class="mb-1 text-base font-bold text-gray-900 group-hover:text-[#17384b]">{{ __('bureaux.title') }}</h2>
            <p class="mb-0 text-sm text-gray-500">Services métier du workflow (nom, description).</p>
        </a>
    </div>
</x-app-layout>
