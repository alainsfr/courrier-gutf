<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h1>{{ __('ux.manual') }}</h1><p class="mb-0 text-sm text-slate-500">Guide complet en français · Édition du 28 octobre 2026</p></div>
            <x-buttons.primary :href="asset('docs/manuel-utilisation.pdf')" target="_blank" rel="noopener">Télécharger le PDF</x-buttons.primary>
        </div>
    </x-slot>
    <div class="mx-auto grid max-w-6xl items-start gap-6 xl:grid-cols-[16rem_minmax(0,1fr)]">
        <nav class="courrier-card" aria-label="Sommaire du manuel">
            <h2 class="mb-4 text-sm font-bold">Sommaire</h2>
            <ol class="m-0 list-none space-y-3 p-0">
                @foreach($sections as $section)
                    <li><a class="text-sm text-slate-600 no-underline hover:underline" href="#{{ $section['id'] }}">{{ $section['title'] }}</a></li>
                @endforeach
            </ol>
        </nav>
        <article class="user-manual courrier-card">
            {!! $intro !!}
            @foreach($sections as $section)
                <section id="{{ $section['id'] }}" class="scroll-mt-48">
                    <h2>{{ $section['title'] }}</h2>
                    {!! $section['html'] !!}
                </section>
            @endforeach
        </article>
    </div>
</x-app-layout>
