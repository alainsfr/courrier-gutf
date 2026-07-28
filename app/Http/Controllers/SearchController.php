<?php

namespace App\Http\Controllers;

use App\Models\Bureau;
use App\Models\Courrier;
use App\Services\CourrierQueryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __construct(
        private readonly CourrierQueryService $queryService
    ) {}

    /**
     * Recherche multicritère CCF : formulaire + résultats paginés.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Courrier::class);

        $filters = [
            'tf' => trim((string) $request->query('tf', '')),
            'type' => (string) $request->query('type', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'bureau_actuel_id' => (string) $request->query('bureau_actuel_id', ''),
            'q' => trim((string) $request->query('q', '')),
        ];

        $hasFilters = collect($filters)->contains(fn ($v) => $v !== '');

        $courriers = null;
        if ($hasFilters || $request->boolean('search')) {
            $courriers = $this->queryService
                ->buildMulticriteriaSearchQuery($request)
                ->paginate(15)
                ->withQueryString();
        }

        $bureaux = Bureau::query()
            ->where('is_active', true)
            ->orderBy('ordre')
            ->get(['id', 'nom', 'code', 'color']);

        return view('courriers.search', compact('filters', 'courriers', 'bureaux', 'hasFilters'));
    }
}
