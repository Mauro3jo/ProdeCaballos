<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pronostico extends Model
{
    protected $fillable = [
        'formulario_id', 'carrera_id', 'caballo_id', 'tipo'
    ];

    public function formulario()
    {
        return $this->belongsTo(Formulario::class);
    }

    public function carrera()
    {
        return $this->belongsTo(Carrera::class);
    }

    public function caballo()
    {
        return $this->belongsTo(Caballo::class);
    }
}
