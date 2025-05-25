<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prode_caballos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->decimal('precio', 10, 2); // ajustá la precisión si querés más/menos decimales
            $table->dateTime('fechafin');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prode_caballos');
    }
};
