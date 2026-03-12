<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conciliacion extends Model
{
    protected $fillable = [
        'banco',
        'mes',
        'anio',
        'saldo_banco',
        'saldo_contable',
        'estado',
    ];

    public function extractoItems(): HasMany
    {
        return $this->hasMany(ExtractoItem::class);
    }

    public function auxiliarItems(): HasMany
    {
        return $this->hasMany(AuxiliarItem::class);
    }
}
