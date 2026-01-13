<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourrierAttachment extends Model
{
    protected $fillable = [
        'courrier_id',
        'uploaded_by_user_id',
        'source',
        'original_name',
        'stored_name',
        'mime',
        'size',
        'path',
    ];

    public function courrier(): BelongsTo
    {
        return $this->belongsTo(Courrier::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
