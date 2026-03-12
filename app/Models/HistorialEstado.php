<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialEstado extends Model
{
    protected $fillable = [
        'credito_id',
        'estado',
        'usuario',
        'observaciones',
    ];

    public function credito(): BelongsTo
    {
        return $this->belongsTo(Credito::class);
    }
}
