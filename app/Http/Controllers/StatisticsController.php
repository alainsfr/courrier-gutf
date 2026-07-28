<?php

namespace App\Http\Controllers;

use App\Exports\StatisticsWorkbookExport;
use App\Models\Courrier;
use App\Services\StatisticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatisticsController extends Controller
{
    public function __construct(
        private readonly StatisticsService $statistics
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Courrier::class);

        $data = $this->statistics->dashboard();

        return view('statistics.index', [
            'treatedThisMonth' => $data['treatedThisMonth'],
            'lateCount' => $data['lateCount'],
            'byBureau' => $data['byBureau'],
            'monthlyReceived' => $data['monthlyReceived'],
            'chartBureauLabels' => $data['byBureau']->pluck('nom')->values(),
            'chartBureauValues' => $data['byBureau']->pluck('total')->values(),
            'chartBureauColors' => $data['byBureau']->pluck('color')->values(),
            'chartMonthLabels' => collect($data['monthlyReceived'])->pluck('label')->values(),
            'chartMonthValues' => collect($data['monthlyReceived'])->pluck('count')->values(),
        ]);
    }

    /**
     * Rapport PDF (agrégats uniquement — volume fixe, pas de risque OOM).
     */
    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', Courrier::class);

        $data = $this->statistics->dashboard();

        $pdf = Pdf::loadView('exports.statistics_pdf', [
            'treatedThisMonth' => $data['treatedThisMonth'],
            'lateCount' => $data['lateCount'],
            'byBureau' => $data['byBureau'],
            'monthlyReceived' => $data['monthlyReceived'],
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('rapport_statistiques_'.now()->format('Ymd_His').'.pdf');
    }

    /**
     * Excel multi-feuilles : résumé (FromArray) + courriers en retard (FromQuery / chunk).
     */
    public function exportExcel(Request $request): BinaryFileResponse|StreamedResponse
    {
        $this->authorize('viewAny', Courrier::class);

        $data = $this->statistics->dashboard();

        return Excel::download(
            new StatisticsWorkbookExport($data),
            'rapport_statistiques_'.now()->format('Ymd_His').'.xlsx'
        );
    }
}
