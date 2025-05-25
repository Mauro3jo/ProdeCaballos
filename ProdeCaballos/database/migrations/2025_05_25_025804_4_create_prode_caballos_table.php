<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProdeCaballosTable extends Migration
{
    public function up()
    {
        Schema::create('prode_caballos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('prode_caballos');
    }
}
