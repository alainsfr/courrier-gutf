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
            ->with(['assignedTo:id,name,email'])
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

                // Validateurs : voir aussi les plis en attente de validation
                if ($request->user()->can('courrier.validate')) {
                    $sub->orWhere('status', 'en_validation');
                }
            });
        }

        $search = trim((string) $request->query('q', ''));
        $type = (string) $request->query('type', '');
        $status = (string) $request->query('status', '');
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

        if (in_array($type, ['entrant', 'sortant'], true)) {
            $q->where('type', $type);
        }

        $allowedStatuses = ['recu', 'affecte', 'en_traitement', 'en_validation', 'valide', 'rejete', 'archive'];
        if (in_array($status, $allowedStatuses, true)) {
            $q->where('status', $status);
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
}
