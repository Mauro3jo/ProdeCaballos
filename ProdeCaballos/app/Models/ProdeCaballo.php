<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdeCaballo extends Model
{
    protected $table = 'prode_caballos';

    // Agregamos 'foto', 'reglas' y 'premio' al fillable
    protected $fillable = ['nombre', 'precio', 'premio', 'fechafin', 'foto', 'reglas','tipo'];

    public function formularios()
    {
        return $this->hasMany(Formulario::class, 'prode_caballo_id');
    }

    public function configuraciones()
    {
        return $this->hasMany(ConfiguracionProde::class, 'prode_caballo_id');
    }

    public function carreras()
    {
        return $this->belongsToMany(Carrera::class, 'prode_carrera')
            ->withPivot('obligatoria')
            ->withTimestamps();
    }

    // Accesor para obtener la URL pública de la foto (si existe)
    public function getFotoUrlAttribute()
    {
        return $this->foto ? asset('img/' . $this->foto) : null;
    }
}
