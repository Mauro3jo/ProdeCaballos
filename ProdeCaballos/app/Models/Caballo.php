<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caballo extends Model
{
    protected $fillable = ['nombre'];

    public function carreras()
    {
        return $this->belongsToMany(Carrera::class, 'caballo_carrera');
    }

    public function pronosticos()
    {
        return $this->hasMany(Pronostico::class);
    }
}
