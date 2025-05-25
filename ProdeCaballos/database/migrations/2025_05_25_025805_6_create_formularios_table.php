<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormulariosTable extends Migration
{
    public function up()
    {
        Schema::create('formularios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prode_caballo_id')->constrained('prode_caballos')->onDelete('cascade');
            $table->string('nombre');
            $table->string('dni');
            $table->string('celular');
            $table->string('forma_pago');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('formularios');
    }
}
