<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caballo extends Model
{
    protected $fillable = ['nombre', 'descripcion'];

    public function carreras()
    {
        return $this->belongsToMany(Carrera::class, 'caballo_carrera');
    }

    public function pronosticos()
    {
        return $this->hasMany(Pronostico::class, 'caballo_id');
    }

    public function resultados()
    {
        return $this->hasMany(Resultado::class, 'caballo_id');
    }
}
