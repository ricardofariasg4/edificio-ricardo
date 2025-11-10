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
        Schema::create('MORADORES', function (Blueprint $table) {
            $table->unsignedInteger('id_usuario')->index('idx_morador_usuario_idusuario');
            $table->unsignedSmallInteger('numero_apto');
            $table->timestamps();
            $table->primary(['id_usuario']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('MORADORES');
    }
};
