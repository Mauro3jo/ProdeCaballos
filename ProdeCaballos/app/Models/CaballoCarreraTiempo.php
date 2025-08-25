<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaballoCarreraTiempo extends Model
{
    protected $table = 'caballo_carrera_tiempos';

    protected $fillable = [
        'caballo_carrera_id',
        'metros',
        'tiempo',
    ];

    public function caballoCarrera()
    {
        return $this->belongsTo(CaballoCarrera::class, 'caballo_carrera_id');
    }
}
