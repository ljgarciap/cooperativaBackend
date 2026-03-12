<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bitacora extends Model
{
    protected $fillable = [
        'nombre_archivo',
        'tipo_archivo',
        'proceso',
        'estado',
        'usuario',
        'entidad_id',
        'detalles',
    ];
}
