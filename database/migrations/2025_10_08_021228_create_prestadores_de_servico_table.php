<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prestadores_de_servico', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->unique('idx_prestadores_usuario_id');
            $table->dateTime('data_ultimo_trabalho')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestadores_de_servico');
    }
};
