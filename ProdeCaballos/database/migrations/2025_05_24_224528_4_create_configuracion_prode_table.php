<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('configuracion_prode', function (Blueprint $table) {
            $table->id();
            $table->integer('carreras_obligatorias');
            $table->integer('carreras_opcionales');
            $table->integer('carreras_suplentes');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('configuracion_prode');
    }
};
