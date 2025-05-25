<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pronostico extends Model
{
    protected $fillable = [
        'formulario_id', 'carrera_id', 'caballo_id', 'es_suplente'
    ];

    public function formulario()
    {
        return $this->belongsTo(Formulario::class, 'formulario_id');
    }

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }

    public function caballo()
    {
        return $this->belongsTo(Caballo::class, 'caballo_id');
    }
}
