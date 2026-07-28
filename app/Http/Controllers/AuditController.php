<?php

namespace App\Http\Controllers;

use App\Exports\AuditExport;
use App\Models\CourrierHistory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\LazyCollection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $action = (string) $request->query('action', '');
        $dateFrom = (string) $request->query('date_from', '');
        $dateTo = (string) $request->query('date_to', '');

        $histories = $this->filteredQuery($request)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('audit.index', compact('histories', 'q', 'action', 'dateFrom', 'dateTo'));
    }

    /**
     * PDF via LazyCollection : les modèles sont hydratés par lots (pas un get() monolithique).
     */
    public function exportPdf(Request $request)
    {
        /** @var LazyCollection<int, CourrierHistory> $rows */
        $rows = $this->filteredQuery($request)->lazyByIdDesc(200);

        $pdf = Pdf::loadView('exports.audit_pdf', [
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('audit_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Excel via FromQuery (chunk natif Maatwebsite) — évite de charger tout en mémoire.
     */
    public function exportExcel(Request $request): BinaryFileResponse|StreamedResponse
    {
        return Excel::download(
            new AuditExport($request),
            'audit_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    private function filteredQuery(Request $request): Builder
    {
        $q = trim((string) $request->query('q', ''));
        $action = (string) $request->query('action', '');
        $dateFrom = (string) $request->query('date_from', '');
        $dateTo = (string) $request->query('date_to', '');

        return CourrierHistory::query()
            ->with(['courrier:id,reference_interne', 'actor:id,name,email'])
            ->when($q !== '', fn ($sub) =>
                $sub->whereHas('courrier', fn ($c) =>
                    $c->where('reference_interne', 'like', "%{$q}%")
                )
            )
            ->when($action !== '', fn ($sub) => $sub->where('action', $action))
            ->when($dateFrom !== '', fn ($sub) => $sub->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($sub) => $sub->whereDate('created_at', '<=', $dateTo));
    }
}
