<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Credito extends Model
{
    protected $fillable = [
        'identificacion',
        'nombre',
        'monto',
        'tipo',
        'estado',
        'observaciones',
    ];

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialEstado::class);
    }
}
