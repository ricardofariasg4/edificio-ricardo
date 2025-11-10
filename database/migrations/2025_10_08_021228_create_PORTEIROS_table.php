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
        Schema::create('PORTEIROS', function (Blueprint $table) {
            $table->unsignedInteger('id_usuario')->index('idx_porteiro_usuario_idusuario');
            $table->char('turno_de_trabalho', 1);
            $table->timestamps();
            $table->primary(['id_usuario']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('PORTEIROS');
    }
};
