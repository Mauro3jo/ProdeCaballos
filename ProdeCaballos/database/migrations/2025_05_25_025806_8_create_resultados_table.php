<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResultadosTable extends Migration
{
    public function up()
    {
        Schema::create('resultados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrera_id')->constrained('carreras')->onDelete('cascade');
            $table->foreignId('caballo_id')->constrained('caballos')->onDelete('cascade');
            $table->integer('posicion')->nullable();
            $table->boolean('empate')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('resultados');
    }
}
