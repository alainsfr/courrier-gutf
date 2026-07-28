<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Courrier extends Model
{
    protected $fillable = [
        'type',
        'year',
        'seq',
        'reference_interne',
        'reference_externe',
        'objet',
        'description',
        'expediteur',
        'destinataire',
        'date_courrier',
        'date_reception',
        'date_envoi',
        'status',
        'created_by_user_id',
        'assigned_to_user_id',
        'bureau_actuel_id',
        'workflow_etape_id',
        'validated_by_user_id',
        'validated_at',
        'archived_at',
    ];

    protected $casts = [
        'date_courrier' => 'date',
        'date_reception' => 'date',
        'date_envoi' => 'date',
        'validated_at' => 'datetime',
        'archived_at' => 'datetime',
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
}
