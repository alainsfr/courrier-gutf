<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowEtape extends Model
{
    protected $table = 'workflow_etapes';

    protected $fillable = [
        'libelle',
        'bureau_id',
        'delai_jours',
        'ordre',
        'is_rejectable',
        'ui_color',
        'ui_icon',
    ];

    protected $casts = [
        'delai_jours' => 'integer',
        'ordre' => 'integer',
        'is_rejectable' => 'boolean',
    ];

    public function bureau(): BelongsTo
    {
        return $this->belongsTo(Bureau::class, 'bureau_id');
    }

    public function courriers(): HasMany
    {
        return $this->hasMany(Courrier::class, 'workflow_etape_id');
    }
}
