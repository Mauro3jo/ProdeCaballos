<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaballoCarrera extends Model
{
    protected $table = 'caballo_carrera';

    public $timestamps = false;

    protected $fillable = ['carrera_id', 'caballo_id'];
}
