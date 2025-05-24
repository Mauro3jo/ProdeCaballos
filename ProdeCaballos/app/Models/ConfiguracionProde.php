<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionProde extends Model
{
    protected $table = 'configuracion_prode';

    protected $fillable = [
        'carreras_obligatorias',
        'carreras_opcionales',
        'carreras_suplentes'
    ];
}
