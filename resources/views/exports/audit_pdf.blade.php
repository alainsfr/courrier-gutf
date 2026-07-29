<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Journal d'audit</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h1 { font-size: 16px; margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; vertical-align: top; }
        th { background: #f3f5f7; text-align: left; }
        .meta { color: #666; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>{{ __('audit.title') }}</h1>
    <div class="meta">Export généré le {{ now()->format('d/m/Y H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Référence</th>
                <th>Action</th>
                <th>De</th>
                <th>Vers</th>
                <th>Acteur</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $history)
                <tr>
                    <td>{{ optional($history->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $history->courrier->reference_interne ?? '—' }}</td>
                    <td>{{ __('audit.actions.' . $history->action) }}</td>
                    <td>{{ $history->from_status ?: '—' }}</td>
                    <td>{{ $history->to_status ?: '—' }}</td>
                    <td>{{ $history->actor->name ?? 'Système' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
