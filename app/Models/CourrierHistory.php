<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourrierHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'courrier_id',
        'action',
        'from_status',
        'to_status',
        'ancienne_etape_id',
        'nouvelle_etape_id',
        'note_metier',
        'action_type',
        'actor_user_id',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function courrier(): BelongsTo
    {
        return $this->belongsTo(Courrier::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function ancienneEtape(): BelongsTo
    {
        return $this->belongsTo(WorkflowEtape::class, 'ancienne_etape_id');
    }

    public function nouvelleEtape(): BelongsTo
    {
        return $this->belongsTo(WorkflowEtape::class, 'nouvelle_etape_id');
    }
}
