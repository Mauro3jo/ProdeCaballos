<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCaballoCarreraTable extends Migration
{
    public function up()
    {
        Schema::create('caballo_carrera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caballo_id')->constrained('caballos')->onDelete('cascade');
            $table->foreignId('carrera_id')->constrained('carreras')->onDelete('cascade');
            $table->integer('numero')->nullable();
            $table->timestamps();

            $table->unique(['caballo_id', 'carrera_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('caballo_carrera');
    }
}
