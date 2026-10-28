<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Courrier extends Model
{
    protected $hidden = ['submission_key', 'submission_hash'];

    public const ETAPE_RECEPTION = 1;

    public const ETAPE_ANALYSE = 2;

    public const ETAPE_TRAITEMENT = 3;

    public const ETAPE_VALIDATION = 4;

    public const ETAPE_SORTIE = 5;

    public const ETAPE_ARCHIVAGE = 6;

    protected $fillable = [
        'type',
        'year',
        'seq',
        'reference_interne',
        'reference_externe',
        'tf',
        'objet',
        'description',
        'expediteur',
        'destinataire',
        'proprietaire',
        'requerant',
        'vendeur',
        'etude_notariale',
        'valeur_immeuble',
        'numero_acte',
        'numero_repertoire',
        'date_courrier',
        'date_reception',
        'date_envoi',
        'created_by_user_id',
        'assigned_to_user_id',
        'bureau_actuel_id',
        'workflow_etape_id',
        'validated_by_user_id',
        'validated_at',
        'archived_at',
        'date_limite',
        'is_late',
    ];

    protected $casts = [
        'date_courrier' => 'date',
        'date_reception' => 'date',
        'date_envoi' => 'date',
        'validated_at' => 'datetime',
        'archived_at' => 'datetime',
        'date_limite' => 'datetime',
        'is_late' => 'boolean',
        'valeur_immeuble' => 'decimal:2',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function bureauActuel(): BelongsTo
    {
        return $this->belongsTo(Bureau::class, 'bureau_actuel_id');
    }

    public function workflowEtape(): BelongsTo
    {
        return $this->belongsTo(WorkflowEtape::class, 'workflow_etape_id');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by_user_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(CourrierHistory::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CourrierAttachment::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CourrierComment::class)->oldest();
    }

    public function visas(): HasMany
    {
        return $this->hasMany(ParapheurVisa::class);
    }

    /**
     * Courriers enfants liés (ce dossier = parent).
     */
    public function linkedCourriers(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'courrier_links',
            'parent_id',
            'child_id'
        )->withTimestamps();
    }

    /**
     * Courriers parents liés (ce dossier = enfant).
     */
    public function parentCourrier(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'courrier_links',
            'child_id',
            'parent_id'
        )->withTimestamps();
    }

    public function etapeOrdre(): ?int
    {
        if ($this->relationLoaded('workflowEtape')) {
            return $this->workflowEtape?->ordre !== null
                ? (int) $this->workflowEtape->ordre
                : null;
        }

        return $this->workflowEtape()->value('ordre');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null
            || $this->etapeOrdre() === self::ETAPE_ARCHIVAGE;
    }

    public function isEnValidation(): bool
    {
        return $this->etapeOrdre() === self::ETAPE_VALIDATION;
    }

    public function isAtOrdre(int $ordre): bool
    {
        return $this->etapeOrdre() === $ordre;
    }

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query
            ->whereNull('archived_at')
            ->where(function (Builder $q) {
                $q->whereNull('workflow_etape_id')
                    ->orWhereHas('workflowEtape', fn (Builder $e) => $e->where('ordre', '<', self::ETAPE_ARCHIVAGE));
            });
    }

    public function scopeWhereEtapeOrdre(Builder $query, int $ordre): Builder
    {
        return $query->whereHas('workflowEtape', fn (Builder $e) => $e->where('ordre', $ordre));
    }

    public function scopeWhereEtapeOrdreIn(Builder $query, array $ordres): Builder
    {
        return $query->whereHas('workflowEtape', fn (Builder $e) => $e->whereIn('ordre', $ordres));
    }
}
