<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdeCaballo extends Model
{
    protected $table = 'prode_caballos';

    protected $fillable = ['nombre', 'precio', 'fechafin'];

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
}
