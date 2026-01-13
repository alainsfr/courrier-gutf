<!DOCTYPE html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('courrier.export_pdf') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #111; }
        .header { display: table; width: 100%; margin-bottom: 10px; }
        .header .left { display: table-cell; vertical-align: middle; width: 70%; }
        .header .right { display: table-cell; vertical-align: middle; text-align: right; width: 30%; }
        h1 { font-size: 14px; margin: 0 0 2px 0; }
        .meta { font-size: 10px; color: #555; }
        .filters { margin: 8px 0 12px 0; }
        .filters .box { border: 1px solid #ddd; padding: 6px 8px; border-radius: 6px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 5px 6px; vertical-align: top; }
        th { background: #f3f5f7; text-align: left; font-size: 10px; }
        .small { font-size: 9.5px; color: #666; }
        .nowrap { white-space: nowrap; }
        .status { font-size: 9.5px; padding: 2px 6px; border-radius: 10px; border: 1px solid #ddd; }
    </style>
</head>
<body>
@php
    $logoPath = public_path('images/gutf-logo.png'); // optionnel
    $hasLogo = file_exists($logoPath);
@endphp

<div class="header">
    <div class="left">
        <h1>{{ __('courrier.export_pdf') }} – {{ __('courrier.title') }}</h1>
        <div class="meta">
            {{ __('courrier.export_generated_at') }} : {{ now()->format('d/m/Y H:i') }} |
            {{ __('courrier.export_generated_by') }} : {{ auth()->user()->name }} ({{ auth()->user()->email }})
        </div>
    </div>
    <div class="right">
        @if($hasLogo)
            <img src="{{ $logoPath }}" alt="GUTF" style="height:28px;">
        @else
            <div class="small">{{ config('app.name', 'COURRIER GUTF') }}</div>
        @endif
    </div>
</div>

@if(!empty($filtersHuman))
    <div class="filters">
        <div class="box">
            <strong>{{ __('courrier.export_filters') }} :</strong>
            <span class="small">{{ implode(' | ', $filtersHuman) }}</span>
        </div>
    </div>
@endif

<table>
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
</body>
</html>
