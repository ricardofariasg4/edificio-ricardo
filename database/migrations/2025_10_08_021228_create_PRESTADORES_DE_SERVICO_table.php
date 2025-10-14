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
        Schema::create('PRESTADORES_DE_SERVICO', function (Blueprint $table) {
            $table->unsignedInteger('id_usuario')->index('idx_prestador_usuario_idusuario');
            $table->dateTime('data_ultimo_trabalho')->nullable();

            $table->primary(['id_usuario']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('PRESTADORES_DE_SERVICO');
    }
};
