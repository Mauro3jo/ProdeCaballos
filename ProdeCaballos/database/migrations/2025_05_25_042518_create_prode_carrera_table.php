<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prode_carrera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prode_caballo_id')->constrained('prode_caballos')->onDelete('cascade');
            $table->foreignId('carrera_id')->constrained('carreras')->onDelete('cascade');
            $table->boolean('obligatoria')->default(false);
            $table->unique(['prode_caballo_id', 'carrera_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prode_carrera');
    }
};
