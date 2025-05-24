<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Formulario extends Model
{
    protected $fillable = [
        'nombre', 'dni', 'celular', 'forma_pago'
    ];

    public function pronosticos()
    {
        return $this->hasMany(Pronostico::class);
    }
}
