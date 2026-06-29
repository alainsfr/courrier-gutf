<?php

namespace App\Http\Controllers;

use App\Models\CourrierHistory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AuditExport;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $action = (string) $request->query('action', '');
        $dateFrom = (string) $request->query('date_from', '');
        $dateTo = (string) $request->query('date_to', '');

        $histories = CourrierHistory::query()
            ->with(['courrier:id,reference_interne', 'actor:id,name,email'])
            ->when($q !== '', fn ($sub) =>
                $sub->whereHas('courrier', fn ($c) =>
                    $c->where('reference_interne', 'like', "%{$q}%")
                )
            )
            ->when($action !== '', fn ($sub) => $sub->where('action', $action))
            ->when($dateFrom !== '', fn ($sub) => $sub->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($sub) => $sub->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('audit.index', compact('histories', 'q', 'action', 'dateFrom', 'dateTo'));
    }

    public function exportPdf(Request $request)
    {
        $rows = CourrierHistory::with(['courrier', 'actor'])->latest()->get();

        $pdf = Pdf::loadView('exports.audit_pdf', compact('rows'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('audit_' . now()->format('Ymd_His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        return Excel::download(new AuditExport, 'audit_' . now()->format('Ymd_His') . '.xlsx');
    }
}
