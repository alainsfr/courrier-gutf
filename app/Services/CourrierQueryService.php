<?php

namespace App\Services;

use App\Models\Courrier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CourrierQueryService
{
    /**
     * Construit la query filtrée (source de vérité) utilisée par :
     * - l'index (Registre)
     * - l'export Excel
     * - l'export PDF
     */
    public function buildFilteredQuery(Request $request): Builder
    {
        $q = Courrier::query()
            ->withCount('attachments')
            ->with([
                'assignedTo:id,name,email',
                'bureauActuel:id,nom,code,color',
                'workflowEtape:id,libelle,ordre,ui_color',
            ]);

        $this->applyRegisterFilters($q, $request);
        $this->applyRegisterSort($q, $request);

        return $q;
    }

    /**
     * Filtres + périmètre du Registre (sans with/sort).
     */
    public function applyRegisterFilters(Builder $q, Request $request): void
    {
        $this->applyVisibilityScope($q, $request, forRegister: true);

        $search = trim((string) $request->query('q', ''));
        $type = (string) $request->query('type', '');
        $etapeId = (string) $request->query('workflow_etape_id', '');
        $bureauId = (string) $request->query('bureau_actuel_id', '');
        $hasAttachments = (string) $request->query('has_attachments', '');
        $assignedTo = (string) $request->query('assigned_to', '');
        $dateFrom = (string) $request->query('date_from', '');
        $dateTo = (string) $request->query('date_to', '');
        $isLate = (string) $request->query('is_late', '');
        $archived = (string) $request->query('archived', 'active');

        if ($search !== '') {
            $q->where(function (Builder $sub) use ($search) {
                $sub->where('reference_interne', 'like', "%{$search}%")
                    ->orWhere('objet', 'like', "%{$search}%")
                    ->orWhere('reference_externe', 'like', "%{$search}%")
                    ->orWhere('tf', 'like', "%{$search}%")
                    ->orWhere('expediteur', 'like', "%{$search}%")
                    ->orWhere('destinataire', 'like', "%{$search}%")
                    ->orWhere('proprietaire', 'like', "%{$search}%")
                    ->orWhere('requerant', 'like', "%{$search}%");
            });
        }

        if (in_array($type, ['entrant', 'sortant', 'interne'], true)) {
            $q->where('type', $type);
        }

        if ($etapeId !== '' && ctype_digit($etapeId)) {
            $q->where('workflow_etape_id', (int) $etapeId);
        }

        if ($bureauId !== '' && ctype_digit($bureauId)) {
            $q->where('bureau_actuel_id', (int) $bureauId);
        }

        if ($hasAttachments === 'yes') {
            $q->whereHas('attachments');
        } elseif ($hasAttachments === 'no') {
            $q->whereDoesntHave('attachments');
        }

        if ($assignedTo === 'unassigned') {
            $q->whereNull('assigned_to_user_id');
        } elseif ($assignedTo !== '' && ctype_digit($assignedTo)) {
            $q->where('assigned_to_user_id', (int) $assignedTo);
        }

        if ($dateFrom !== '') {
            $q->whereRaw('COALESCE(date_reception, date_courrier) >= ?', [$dateFrom]);
        }
        if ($dateTo !== '') {
            $q->whereRaw('COALESCE(date_reception, date_courrier) <= ?', [$dateTo]);
        }

        if ($isLate === '1' || $isLate === 'yes') {
            $q->where('is_late', true)->notArchived();
        }

        if ($archived === 'archived') {
            $q->where(function (Builder $sub) {
                $sub->whereNotNull('archived_at')
                    ->orWhereHas('workflowEtape', fn (Builder $e) => $e->where('ordre', Courrier::ETAPE_ARCHIVAGE));
            });
        } elseif ($archived === 'all') {
            // no archive constraint
        } else {
            $q->notArchived();
        }
    }

    /**
     * Compteurs pour les raccourcis du Registre (périmètre visible, hors filtres fins).
     *
     * @return array{active:int,late:int,unassigned:int,validation:int,entrant:int,sortant:int}
     */
    public function registerChipCounts(Request $request): array
    {
        $base = Courrier::query();
        $this->applyVisibilityScope($base, $request, forRegister: true);

        return [
            'active' => (clone $base)->notArchived()->count(),
            'late' => (clone $base)->where('is_late', true)->notArchived()->count(),
            'unassigned' => (clone $base)->notArchived()->whereNull('assigned_to_user_id')->count(),
            'validation' => (clone $base)->notArchived()->whereEtapeOrdre(Courrier::ETAPE_VALIDATION)->count(),
            'entrant' => (clone $base)->notArchived()->where('type', 'entrant')->count(),
            'sortant' => (clone $base)->notArchived()->where('type', 'sortant')->count(),
        ];
    }

    /**
     * Recherche multicritère CCF (page Search).
     */
    public function buildMulticriteriaSearchQuery(Request $request): Builder
    {
        $q = Courrier::query()
            ->with([
                'assignedTo:id,name',
                'bureauActuel:id,nom,color',
                'workflowEtape:id,libelle,ordre,ui_color',
            ])
            ->withCount('attachments')
            ->latest('id');

        $this->applyVisibilityScope($q, $request, forRegister: false);

        $tf = trim((string) $request->query('tf', ''));
        $type = (string) $request->query('type', '');
        $dateFrom = (string) $request->query('date_from', '');
        $dateTo = (string) $request->query('date_to', '');
        $bureauId = (string) $request->query('bureau_actuel_id', '');
        $global = trim((string) $request->query('q', ''));

        $q->when($tf !== '', function (Builder $query) use ($tf) {
            $query->where(function (Builder $sub) use ($tf) {
                $sub->where('tf', 'like', "%{$tf}%")
                    ->orWhere('reference_externe', 'like', "%{$tf}%")
                    ->orWhere('reference_interne', 'like', "%{$tf}%")
                    ->orWhere('numero_acte', 'like', "%{$tf}%")
                    ->orWhere('numero_repertoire', 'like', "%{$tf}%");
            });
        });

        $q->when(in_array($type, ['entrant', 'sortant', 'interne'], true), function (Builder $query) use ($type) {
            $query->where('type', $type);
        });

        $q->when($dateFrom !== '', function (Builder $query) use ($dateFrom) {
            $query->whereRaw('COALESCE(date_reception, date_courrier) >= ?', [$dateFrom]);
        });

        $q->when($dateTo !== '', function (Builder $query) use ($dateTo) {
            $query->whereRaw('COALESCE(date_reception, date_courrier) <= ?', [$dateTo]);
        });

        $q->when($bureauId !== '' && ctype_digit($bureauId), function (Builder $query) use ($bureauId) {
            $query->where('bureau_actuel_id', (int) $bureauId);
        });

        $q->when($global !== '', function (Builder $query) use ($global) {
            $query->where(function (Builder $sub) use ($global) {
                $sub->where('proprietaire', 'like', "%{$global}%")
                    ->orWhere('requerant', 'like', "%{$global}%")
                    ->orWhere('vendeur', 'like', "%{$global}%")
                    ->orWhere('etude_notariale', 'like', "%{$global}%")
                    ->orWhere('expediteur', 'like', "%{$global}%")
                    ->orWhere('destinataire', 'like', "%{$global}%")
                    ->orWhere('objet', 'like', "%{$global}%")
                    ->orWhere('description', 'like', "%{$global}%")
                    ->orWhere('reference_externe', 'like', "%{$global}%")
                    ->orWhere('reference_interne', 'like', "%{$global}%")
                    ->orWhere('tf', 'like', "%{$global}%");
            });
        });

        return $q;
    }

    private function applyRegisterSort(Builder $q, Request $request): void
    {
        $sort = (string) $request->query('sort', 'recent');

        match ($sort) {
            'oldest' => $q->orderBy('id'),
            'deadline' => $q->orderByRaw('date_limite is null')->orderBy('date_limite')->orderByDesc('id'),
            'ref' => $q->orderByDesc('reference_interne'),
            'updated' => $q->orderByDesc('updated_at'),
            default => $q->latest('id'),
        };
    }

    /**
     * Périmètre de visibilité.
     *
     * @param  bool  $forRegister  Si true, honore scope=mine|all pour admin/super-admin.
     */
    private function applyVisibilityScope(Builder $q, Request $request, bool $forRegister = false): void
    {
        $user = $request->user();
        if (! $user) {
            $q->whereRaw('1 = 0');

            return;
        }

        $isAdmin = $user->hasRole(['admin', 'super-admin']);

        if ($forRegister && $isAdmin) {
            $scope = (string) $request->query('scope', 'all');
            if (! in_array($scope, ['all', 'mine'], true)) {
                $scope = 'all';
            }
            if ($scope === 'all') {
                return;
            }
        } elseif ($isAdmin && ! $forRegister) {
            return;
        }

        $q->where(function (Builder $sub) use ($user) {
            $sub->where('created_by_user_id', $user->id)
                ->orWhere('assigned_to_user_id', $user->id);

            if ($user->can('courrier.validate')) {
                $sub->orWhereHas('workflowEtape', fn (Builder $e) => $e->where('ordre', Courrier::ETAPE_VALIDATION));
            }

            if ($user->bureau_id) {
                $sub->orWhere('bureau_actuel_id', $user->bureau_id);
            }
        });
    }
}
