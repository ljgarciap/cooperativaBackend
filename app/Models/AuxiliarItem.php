<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuxiliarItem extends Model
{
    protected $fillable = [
        'conciliacion_id',
        'fecha',
        'identificacion',
        'descripcion',
        'referencia',
        'valor',
        'conciliado',
        'color',
    ];

    public function conciliacion(): BelongsTo
    {
        return $this->belongsTo(Conciliacion::class);
    }
}
