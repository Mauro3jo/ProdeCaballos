<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    // Ahora con hipico
    protected $fillable = ['nombre', 'descripcion', 'hipico', 'fecha', 'estado'];

    public function caballos()
    {
        return $this->belongsToMany(Caballo::class, 'caballo_carrera');
    }

    public function prodes()
    {
        return $this->belongsToMany(ProdeCaballo::class, 'prode_carrera')
            ->withPivot('obligatoria')
            ->withTimestamps();
    }

    public function resultados()
    {
        return $this->hasMany(Resultado::class, 'carrera_id');
    }

    public function pronosticos()
    {
        return $this->hasMany(Pronostico::class, 'carrera_id');
    }
}
