<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaballoCarrera extends Model
{
    protected $table = 'caballo_carrera';
    public $timestamps = false;

    protected $fillable = [
        'carrera_id',
        'caballo_id',
        'numero',
        'posicion',
        'ganador',
        'perdedor',
    ];

    public function caballo()
    {
        return $this->belongsTo(Caballo::class, 'caballo_id');
    }

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }

    public function tiempos()
    {
        return $this->hasMany(CaballoCarreraTiempo::class, 'caballo_carrera_id');
    }
}
