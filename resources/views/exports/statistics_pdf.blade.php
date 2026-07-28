<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ __('statistics.export_title') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 6px; }
        h2 { font-size: 13px; margin: 18px 0 8px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        .meta { color: #666; margin-bottom: 14px; }
        .kpis { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .kpis td { border: 1px solid #ccc; padding: 8px 10px; }
        .kpis .label { background: #f3f5f7; font-weight: bold; width: 55%; }
        .kpis .value { text-align: right; font-size: 14px; font-weight: bold; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #ccc; padding: 5px 7px; }
        table.data th { background: #f3f5f7; text-align: left; }
        table.data td.num { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ __('statistics.export_title') }}</h1>
    <div class="meta">{{ __('statistics.generated_at') }} {{ $generatedAt->format('d/m/Y H:i') }}</div>

    <h2>{{ __('statistics.kpis') }}</h2>
    <table class="kpis">
        <tr>
            <td class="label">{{ __('statistics.treated_this_month') }}</td>
            <td class="value">{{ $treatedThisMonth }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('statistics.late_count') }}</td>
            <td class="value">{{ $lateCount }}</td>
        </tr>
    </table>

    <h2>{{ __('statistics.by_bureau') }}</h2>
    <table class="data">
        <thead>
            <tr>
                <th>{{ __('statistics.bureau') }}</th>
                <th>{{ __('statistics.count') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($byBureau as $row)
                <tr>
                    <td>{{ $row->nom }}</td>
                    <td class="num">{{ $row->total }}</td>
                </tr>
            @empty
                <tr><td colspan="2">{{ __('statistics.no_data') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>{{ __('statistics.monthly_received') }}</h2>
    <table class="data">
        <thead>
            <tr>
                <th>{{ __('statistics.month') }}</th>
                <th>{{ __('statistics.count') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($monthlyReceived as $month)
                <tr>
                    <td>{{ $month['label'] }}</td>
                    <td class="num">{{ $month['count'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
