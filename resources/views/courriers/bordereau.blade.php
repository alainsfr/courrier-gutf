<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('courrier.bordereau_title') }} — {{ $courrier->reference_interne }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4; margin: 12mm; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        .qr-wrap svg { width: 120px; height: 120px; }
    </style>
</head>
<body class="bg-white p-8 text-black antialiased">
    <div class="no-print mb-6 flex justify-end gap-2">
        <button type="button"
                onclick="window.print()"
                class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50">
            {{ __('courrier.bordereau_print_again') }}
        </button>
        <button type="button"
                onclick="window.close()"
                class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50">
            {{ __('courrier.bordereau_close') }}
        </button>
    </div>

    <header class="mb-8 border-b border-black pb-6 text-center">
        <p class="mb-1 text-xs font-semibold uppercase tracking-[0.2em] text-gray-600">
            {{ config('app.name', 'COURRIER GUTF') }}
        </p>
        <h1 class="mb-4 text-2xl font-bold tracking-tight">{{ __('courrier.bordereau_title') }}</h1>

        <div class="qr-wrap mx-auto mb-3 inline-block">
            {!! QrCode::size(120)->generate(route('courriers.show', $courrier)) !!}
        </div>
        <p class="text-xs text-gray-600">{{ __('courrier.bordereau_qr_hint') }}</p>
    </header>

    <section class="mb-8">
        <h2 class="mb-3 text-sm font-bold uppercase tracking-wide">{{ __('courrier.bordereau_info') }}</h2>
        <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            <div class="rounded border border-black/20 px-3 py-2">
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-gray-600">{{ __('courrier.ref') }}</dt>
                <dd class="mt-0.5 font-bold">{{ $courrier->reference_interne }}</dd>
            </div>
            <div class="rounded border border-black/20 px-3 py-2">
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-gray-600">{{ __('courrier.date_received') }}</dt>
                <dd class="mt-0.5 font-semibold">
                    {{ optional($courrier->date_reception)->format('d/m/Y') ?? '—' }}
                </dd>
            </div>
            <div class="rounded border border-black/20 px-3 py-2 sm:col-span-2">
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-gray-600">{{ __('courrier.subject') }}</dt>
                <dd class="mt-0.5 font-semibold">{{ $courrier->objet }}</dd>
            </div>
            <div class="rounded border border-black/20 px-3 py-2 sm:col-span-2">
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-gray-600">{{ __('courrier.sender') }}</dt>
                <dd class="mt-0.5 font-semibold">
                    {{ $courrier->expediteur ?: ($courrier->destinataire ?: '—') }}
                </dd>
            </div>
        </dl>
    </section>

    <section>
        <h2 class="mb-3 text-sm font-bold uppercase tracking-wide">{{ __('courrier.bordereau_circuit') }}</h2>
        <table class="w-full border-collapse border border-black text-sm">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-black px-3 py-2 text-left font-bold">{{ __('courrier.bordereau_col_date') }}</th>
                    <th class="border border-black px-3 py-2 text-left font-bold">{{ __('courrier.bordereau_col_bureau') }}</th>
                    <th class="border border-black px-3 py-2 text-left font-bold">{{ __('courrier.bordereau_col_name') }}</th>
                    <th class="border border-black px-3 py-2 text-left font-bold">{{ __('courrier.bordereau_col_signature') }}</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 0; $i < 8; $i++)
                    <tr>
                        <td class="border border-black px-3 py-6">&nbsp;</td>
                        <td class="border border-black px-3 py-6">&nbsp;</td>
                        <td class="border border-black px-3 py-6">&nbsp;</td>
                        <td class="border border-black px-3 py-6">&nbsp;</td>
                    </tr>
                @endfor
            </tbody>
        </table>
    </section>

    <footer class="mt-8 text-center text-[11px] text-gray-500">
        {{ __('courrier.bordereau_footer', ['ref' => $courrier->reference_interne, 'date' => now()->format('d/m/Y H:i')]) }}
    </footer>

    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
