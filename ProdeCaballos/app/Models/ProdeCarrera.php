<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdeCarrera extends Model
{
    protected $table = 'prode_carrera';
    protected $fillable = ['prode_caballo_id', 'carrera_id', 'obligatoria'];

    public function prode()
    {
        return $this->belongsTo(ProdeCaballo::class, 'prode_caballo_id');
    }

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }
}
