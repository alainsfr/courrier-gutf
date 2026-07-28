<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bureau extends Model
{
    protected $table = 'bureaux';

    protected $fillable = [
        'code',
        'nom',
        'icon',
        'color',
        'is_active',
        'ordre',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'ordre' => 'integer',
    ];

    public function etapes(): HasMany
    {
        return $this->hasMany(WorkflowEtape::class, 'bureau_id')->orderBy('ordre');
    }

    public function courriersActuels(): HasMany
    {
        return $this->hasMany(Courrier::class, 'bureau_actuel_id');
    }
}
