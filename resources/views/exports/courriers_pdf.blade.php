<!DOCTYPE html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('courrier.export_pdf') }}</title>

    <style>
        @page {
            margin: 115px 28px 75px 28px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5px;
            color: #111;
        }

        /* Header / Footer (Dompdf fixed) */
        header {
            position: fixed;
            top: -95px;
            left: 0;
            right: 0;
            height: 95px;
        }

        footer {
            position: fixed;
            bottom: -55px;
            left: 0;
            right: 0;
            height: 55px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-left, .header-center, .header-right {
            vertical-align: top;
        }

        .brand {
            font-size: 11px;
            font-weight: 700;
            margin-top: 2px;
        }

        .muted {
            color: #444;
            font-size: 10px;
        }

        .title {
            font-size: 13px;
            font-weight: 800;
            margin: 0;
        }

        .subtitle {
            margin: 2px 0 0 0;
            font-size: 10px;
            color: #333;
        }

        .rule {
            border-top: 1px solid #ddd;
            margin-top: 6px;
        }

        .filters-box {
            border: 1px solid #ddd;
            padding: 7px 9px;
            border-radius: 6px;
            margin-bottom: 10px;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
        }
        table.data th, table.data td {
            border: 1px solid #ddd;
            padding: 5px 6px;
            vertical-align: top;
        }
        table.data th {
            background: #f3f5f7;
            font-size: 10px;
            text-align: left;
        }

        .nowrap { white-space: nowrap; }
        .small { font-size: 9.5px; color: #666; }
        .status {
            font-size: 9.5px;
            padding: 2px 6px;
            border-radius: 10px;
            border: 1px solid #ddd;
            display: inline-block;
        }

        /* Footer */
        .footer-wrap {
            border-top: 1px solid #ddd;
            padding-top: 6px;
            font-size: 9.5px;
            color: #444;
        }
        .footer-left {
            float: left;
            width: 70%;
        }
        .footer-right {
            float: right;
            width: 30%;
            text-align: right;
        }
        .pagenum:before {
            content: "Page " counter(page) " / " counter(pages);
        }

        /* Watermark (optionnel) */
        .watermark {
            position: fixed;
            top: 45%;
            left: 10%;
            right: 10%;
            text-align: center;
            font-size: 48px;
            color: rgba(0,0,0,0.06);
            transform: rotate(-20deg);
        }
    </style>
</head>

@php
    $inst = config('institution');

    $logoPath = $inst['logo_public_path'] ?? null;
    $hasLogo = $logoPath ? file_exists($logoPath) : false;

    $filtersHuman = $filtersHuman ?? [];
@endphp

<body>

{{-- Watermark (facultatif) --}}
<div class="watermark">{{ $inst['app_label'] ?? 'COURRIER GUTF' }}</div>

<header>
    <table class="header-table">
        <tr>
            <td class="header-left" style="width:33%;">
                <div class="brand">{{ $inst['country'] }}</div>
                <div class="muted">{{ $inst['motto'] }}</div>
                <div class="rule"></div>
                <div class="muted" style="margin-top:6px;">
                    {{ $inst['ministry'] }}<br>
                    <strong>{{ $inst['structure'] }}</strong>
                </div>
            </td>

            <td class="header-center" style="width:34%; text-align:center;">
                <p class="title">{{ __('courrier.export_pdf') }}</p>
                <p class="subtitle">{{ __('courrier.title') }}</p>
                <div class="muted" style="margin-top:6px;">
                    {{ __('courrier.export_generated_at') }} : {{ now()->format('d/m/Y H:i') }}<br>
                    {{ __('courrier.export_generated_by') }} : {{ auth()->user()->name }} ({{ auth()->user()->email }})
                </div>
            </td>

            <td class="header-right" style="width:33%; text-align:right;">
                @if($hasLogo)
                    <img src="{{ $logoPath }}" alt="Logo" style="height:42px;">
                @else
                    <div class="brand">{{ $inst['app_label'] }}</div>
                @endif
                <div class="rule"></div>
                <div class="muted" style="margin-top:6px;">
                    {{ config('app.name', 'COURRIER GUTF') }}
                </div>
            </td>
        </tr>
    </table>
</header>

<footer>
    <div class="footer-wrap">
        <div class="footer-left">
            {{ $inst['confidentiality'] }}
        </div>
        <div class="footer-right">
            <span class="pagenum"></span>
        </div>
    </div>
</footer>

<main>
    @if(count($filtersHuman))
        <div class="filters-box">
            <strong>{{ __('courrier.export_filters') }} :</strong>
            <span class="small">{{ implode(' | ', $filtersHuman) }}</span>
        </div>
    @endif

    <table class="data">
        <thead>
        <tr>
            <th class="nowrap">{{ __('courrier.ref') }}</th>
            <th class="nowrap">{{ __('courrier.type') }}</th>
            <th>{{ __('courrier.subject') }}</th>
            <th class="nowrap">{{ __('courrier.status') }}</th>
            <th>{{ __('courrier.sender') }}</th>
            <th>{{ __('courrier.recipient') }}</th>
            <th class="nowrap">{{ __('courrier.export_date') }}</th>
            <th>{{ __('courrier.assigned_to') }}</th>
            <th class="nowrap">PJ</th>
        </tr>
        </thead>

        <tbody>
        @foreach($rows as $c)
            @php
                $date = $c->date_reception ?: $c->date_courrier;
                $dateStr = $date ? $date->format('d/m/Y') : '—';
                $statusLabel = __('courrier.status_label.' . $c->status);
            @endphp

            <tr>
                <td class="nowrap">{{ $c->reference_interne }}</td>
                <td class="nowrap">{{ strtoupper($c->type) }}</td>
                <td>{{ $c->objet }}</td>
                <td class="nowrap"><span class="status">{{ $statusLabel }}</span></td>
                <td>{{ $c->expediteur ?? '—' }}</td>
                <td>{{ $c->destinataire ?? '—' }}</td>
                <td class="nowrap">{{ $dateStr }}</td>
                <td>
                    @if($c->assignedTo)
                        {{ $c->assignedTo->name }}
                        <div class="small">{{ $c->assignedTo->email }}</div>
                    @else
                        —
                    @endif
                </td>
                <td class="nowrap">{{ $c->attachments_count ?? 0 }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</main>

</body>
</html>
