<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionProde extends Model
{
    protected $table = 'configuracion_prode';

    protected $fillable = [
        'prode_caballo_id',
        'cantidad_obligatorias',
        'cantidad_opcionales',
        'cantidad_suplentes'
    ];

    public function prode()
    {
        return $this->belongsTo(ProdeCaballo::class, 'prode_caballo_id');
    }
}
