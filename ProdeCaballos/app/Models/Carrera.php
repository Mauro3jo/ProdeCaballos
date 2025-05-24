<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    protected $fillable = ['nombre', 'fecha', 'estado'];

    public function caballos()
    {
        return $this->belongsToMany(Caballo::class, 'caballo_carrera');
    }

    public function resultados()
    {
        return $this->hasOne(Resultado::class);
    }

    public function pronosticos()
    {
        return $this->hasMany(Pronostico::class);
    }
}
