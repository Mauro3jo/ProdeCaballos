<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resultado extends Model
{
    protected $fillable = ['carrera_id', 'caballo_id'];

    public function carrera()
    {
        return $this->belongsTo(Carrera::class);
    }

    public function caballo()
    {
        return $this->belongsTo(Caballo::class);
    }
}
