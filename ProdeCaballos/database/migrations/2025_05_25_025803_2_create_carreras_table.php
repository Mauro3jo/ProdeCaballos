<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarrerasTable extends Migration
{
    public function up()
    {
        Schema::create('carreras', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->dateTime('fecha')->nullable();
            $table->boolean('obligatoria')->default(false);
            $table->boolean('suspendida')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('carreras');
    }
}
