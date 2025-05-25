<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePronosticosTable extends Migration
{
    public function up()
    {
        Schema::create('pronosticos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formulario_id')->constrained('formularios')->onDelete('cascade');
            $table->foreignId('carrera_id')->constrained('carreras')->onDelete('cascade');
            $table->foreignId('caballo_id')->constrained('caballos')->onDelete('cascade');
            $table->boolean('es_suplente')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pronosticos');
    }
}
