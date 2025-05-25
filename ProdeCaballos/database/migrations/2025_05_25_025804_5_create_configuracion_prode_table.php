<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConfiguracionProdeTable extends Migration
{
    public function up()
    {
        Schema::create('configuracion_prode', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prode_caballo_id')->constrained('prode_caballos')->onDelete('cascade');
            $table->integer('cantidad_obligatorias')->default(0);
            $table->integer('cantidad_opcionales')->default(0);
            $table->integer('cantidad_suplentes')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('configuracion_prode');
    }
}
