<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Formulario extends Model
{
    protected $fillable = [
    'prode_caballo_id',
    'nombre',
    'alias',
    'alias_admin',
    'dni',
    'celular',
    'forma_pago',
    'preciopagado'   // <--- AGREGADO
];


    public function prode()
    {
        return $this->belongsTo(ProdeCaballo::class, 'prode_caballo_id');
    }

    public function pronosticos()
    {
        return $this->hasMany(Pronostico::class, 'formulario_id');
    }
}
