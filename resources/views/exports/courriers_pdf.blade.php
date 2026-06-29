@php
    $isFr = (app()->getLocale() === 'fr');
    $inst = config('institution');

    $logoPath = $inst['logo_public_path'] ?? null;
    $hasLogo = $logoPath ? file_exists($logoPath) : false;

    $filtersHuman = $filtersHuman ?? [];

    // Récupération dynamique et bilingue des entités pour l'en-tête du PDF
    $ministryName = $isFr ? ($inst['ministry_full_fr'] ?? 'MINDCAF & MINFI') : ($inst['ministry_full_en'] ?? 'MINDCAF & MINFI');
    $structureName = $isFr ? ($inst['structure'] ?? '') : ($inst['structure_en'] ?? '');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $inst['app_label'] }} - PDF</title>

    <style>
        /* Marges réduites et optimisées pour maximiser l'espace du tableau sur une seule page */
        @page {
            margin: 100px 30px 50px 30px;
        }

        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 8.5px;
            color: #334155;
            line-height: 1.3;
        }

        /* En-tête fixe compact */
        header {
            position: fixed;
            top: -85px;
            left: 0;
            right: 0;
            height: 70px;
            border-bottom: 1.5px solid #cbd5e1;
            padding-bottom: 5px;
        }

        /* Pied de page fixe */
        footer {
            position: fixed;
            bottom: -35px;
            left: 0;
            right: 0;
            height: 25px;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
            font-size: 7.5px;
            color: #64748b;
        }

        /* En-tête */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }
        .header-table td {
            border: none;
            padding: 0;
            vertical-align: top;
        }
        .header-left {
            width: 50%;
        }
        .header-center {
            width: 20%;
            text-align: center;
            padding-top: 5px;
        }
        .header-right {
            width: 30%;
            text-align: right;
        }

        .brand-title {
            font-size: 10px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            margin-bottom: 1px;
        }
        .motto {
            font-size: 7.5px;
            font-style: italic;
            color: #64748b;
            margin-bottom: 3px;
        }
        .institution-details {
            font-size: 7.5px;
            color: #475569;
            line-height: 1.2;
        }

        .pdf-title {
            font-size: 12px;
            font-weight: 800;
            color: #1e293b;
            text-transform: uppercase;
            margin: 0;
        }
        .pdf-subtitle {
            font-size: 7.5px;
            color: #64748b;
            margin-top: 2px;
        }

        /* Zone de filtres de recherche */
        .filters-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 5px 8px;
            margin-bottom: 10px;
            font-size: 8px;
            color: #475569;
        }

        /* Tableau de données ultra-simplifié pour éviter les sauts de page incorrects */
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        table.data th, table.data td {
            padding: 5px 6px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        table.data th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
        }
        table.data tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* Formatage simplifié du texte des statuts (évite les bugs de hauteur de DomPDF) */
        .status-text {
            font-weight: bold;
            font-size: 7.5px;
        }
        .text-entrant {
            color: #1d4ed8; /* Bleu */
        }
        .text-sortant {
            color: #b45309; /* Orange */
        }

        /* Pied de page */
        .footer-left {
            float: left;
            width: 80%;
        }
        .footer-right {
            float: right;
            width: 20%;
            text-align: right;
            font-weight: bold;
        }
        .pagenum:before {
            content: "Page " counter(page);
        }

        /* Filigrane d'arrière-plan (Watermark) */
        .watermark {
            position: fixed;
            top: 40%;
            left: 10%;
            right: 10%;
            text-align: center;
            font-size: 55px;
            font-weight: bold;
            color: rgba(226, 232, 240, 0.35);
            transform: rotate(-25deg);
            z-index: -1000;
        }
    </style>
</head>
<body>

    <div class="watermark">{{ $inst['app_label'] }}</div>

    {{-- EN-TÊTE FIXE --}}
    <header>
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <div class="brand-title">{{ $inst['country'] }}</div>
                    <div class="motto">{{ $inst['motto'] }}</div>
                    <div class="institution-details">
                        {{ $ministryName }}<br>
                        <strong>{{ $structureName }}</strong>
                    </div>
                </td>

                <td class="header-center">
                    <h2 class="pdf-title">{{ $isFr ? 'Courriers' : 'Mails' }}</h2>
                    <div class="pdf-subtitle">
                        {{ $isFr ? 'Registre officiel' : 'Official Registry' }}
                    </div>
                </td>

                <td class="header-right">
                    @if($hasLogo)
                        <img src="{{ $logoPath }}" alt="Logo" style="height:35px;">
                    @else
                        <div class="brand-title" style="color: var(--gutf-primary);">{{ $inst['app_label'] }}</div>
                    @endif
                    <div class="pdf-subtitle" style="margin-top: 3px;">
                        {{ $isFr ? 'Généré le' : 'Generated on' }} {{ now()->format('d/m/Y H:i') }}
                    </div>
                </td>
            </tr>
        </table>
    </header>

    {{-- PIED DE PAGE FIXE --}}
    <footer>
        <div class="footer-left">
            {{ $inst['confidentiality'] }}
        </div>
        <div class="footer-right">
            <span class="pagenum"></span>
        </div>
    </footer>

    {{-- TABLEAU DE DONNÉES --}}
    <main>
        @if(count($filtersHuman))
            <div class="filters-box">
                <strong>{{ $isFr ? 'Filtres appliqués' : 'Active filters' }} :</strong>
                <span>{{ implode(' | ', $filtersHuman) }}</span>
            </div>
        @endif

        <table class="data">
            <thead>
                <tr>
                    <th style="width: 14%;">{{ __('courrier.ref') }}</th>
                    <th style="width: 10%;">{{ __('courrier.type') }}</th>
                    <th style="width: 26%;">{{ __('courrier.subject') }}</th>
                    <th style="width: 12%;">{{ __('courrier.status') }}</th>
                    <th style="width: 14%;">{{ __('courrier.sender') }}</th>
                    <th style="width: 14%;">{{ __('courrier.recipient') }}</th>
                    <th style="width: 10%;">{{ __('courrier.export_date') }}</th>
                    <th style="width: 4%; text-align: center;">PJ</th>
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
                        <td style="font-weight: bold; color: #0f172a;">{{ $c->reference_interne }}</td>
                        <td>
                            @if($c->type === 'entrant')
                                <span class="status-text text-entrant">{{ $isFr ? '📥 Entrant' : '📥 Incoming' }}</span>
                            @else
                                <span class="status-text text-sortant">{{ $isFr ? '📤 Sortant' : '📤 Outgoing' }}</span>
                            @endif
                        </td>
                        <td style="color: #1e293b; font-weight: 500;">{{ $c->objet }}</td>
                        <td>
                            <span class="status-text">{{ $statusLabel }}</span>
                        </td>
                        <td style="color: #334155;">{{ $c->expediteur ?? '—' }}</td>
                        <td style="color: #334155;">{{ $c->destinataire ?? '—' }}</td>
                        <td>{{ $dateStr }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ $c->attachments_count ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </main>

</body>
</html>
