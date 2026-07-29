<?php

namespace App\Services;

use App\Models\Courrier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CourrierQueryService
{
    /**
     * Construit la query filtrée (source de vérité) utilisée par :
     * - l'index
     * - l'export Excel
     * - l'export PDF
     */
    public function buildFilteredQuery(Request $request): Builder
    {
        $q = Courrier::query()
            ->withCount('attachments')
            ->with([
                'assignedTo:id,name,email',
                'workflowEtape:id,libelle,ordre,ui_color',
            ])
            ->latest('id');

        $isAdmin = $request->user()->hasRole('admin');

        // scope=all|mine (admin choisit, non-admin => mine)
        $scope = (string) $request->query('scope', $isAdmin ? 'all' : 'mine');
        if (! in_array($scope, ['all', 'mine'], true)) {
            $scope = $isAdmin ? 'all' : 'mine';
        }

        if (! $isAdmin || $scope === 'mine') {
            $q->where(function ($sub) use ($request) {
                $sub->where('created_by_user_id', $request->user()->id)
                    ->orWhere('assigned_to_user_id', $request->user()->id);

                // Validateurs : voir aussi les plis en attente de validation (étape 4)
                if ($request->user()->can('courrier.validate')) {
                    $sub->orWhereHas('workflowEtape', fn ($e) => $e->where('ordre', Courrier::ETAPE_VALIDATION));
                }
            });
        }

        $search = trim((string) $request->query('q', ''));
        $type = (string) $request->query('type', '');
        $etapeId = (string) $request->query('workflow_etape_id', '');
        $hasAttachments = (string) $request->query('has_attachments', '');
        $assignedTo = (string) $request->query('assigned_to', '');
        $dateFrom = (string) $request->query('date_from', '');
        $dateTo = (string) $request->query('date_to', '');

        if ($search !== '') {
            $q->where(function ($sub) use ($search) {
                $sub->where('reference_interne', 'like', "%{$search}%")
                    ->orWhere('objet', 'like', "%{$search}%")
                    ->orWhere('reference_externe', 'like', "%{$search}%")
                    ->orWhere('expediteur', 'like', "%{$search}%")
                    ->orWhere('destinataire', 'like', "%{$search}%");
            });
        }

        if (in_array($type, ['entrant', 'sortant', 'interne'], true)) {
            $q->where('type', $type);
        }

        if ($etapeId !== '' && ctype_digit($etapeId)) {
            $q->where('workflow_etape_id', (int) $etapeId);
        }

        if ($hasAttachments === 'yes') {
            $q->having('attachments_count', '>', 0);
        } elseif ($hasAttachments === 'no') {
            $q->having('attachments_count', '=', 0);
        }

        if ($assignedTo !== '' && ctype_digit($assignedTo)) {
            $q->where('assigned_to_user_id', (int) $assignedTo);
        }

        // période : COALESCE(date_reception, date_courrier)
        if ($dateFrom !== '') {
            $q->whereRaw('COALESCE(date_reception, date_courrier) >= ?', [$dateFrom]);
        }
        if ($dateTo !== '') {
            $q->whereRaw('COALESCE(date_reception, date_courrier) <= ?', [$dateTo]);
        }

        return $q;
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

        $this->applyVisibilityScope($q, $request);

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

        // Texte global : propriétaire / requérant / vendeur / étude (+ champs classiques)
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

    /**
     * Périmètre de visibilité (admin / stakeholder / validateur).
     */
    private function applyVisibilityScope(Builder $q, Request $request): void
    {
        $user = $request->user();
        if (! $user || $user->hasRole('admin')) {
            return;
        }

        $q->where(function (Builder $sub) use ($user) {
            $sub->where('created_by_user_id', $user->id)
                ->orWhere('assigned_to_user_id', $user->id);

            if ($user->can('courrier.validate')) {
                $sub->orWhereHas('workflowEtape', fn ($e) => $e->where('ordre', Courrier::ETAPE_VALIDATION));
            }

            if ($user->bureau_id) {
                $sub->orWhere('bureau_actuel_id', $user->bureau_id);
            }
        });
    }
}
