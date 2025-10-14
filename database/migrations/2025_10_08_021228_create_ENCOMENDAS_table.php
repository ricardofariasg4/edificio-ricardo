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
        Schema::create('ENCOMENDAS', function (Blueprint $table) {
            $table->increments('id_encomenda');
            $table->string('codigo_rastreio', 45)->index('idx_encomenda_usuario_codrastreio');
            $table->dateTime('data_recebimento');
            $table->unsignedInteger('id_usuario')->index('idx_encomenda_usuario_idusuario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ENCOMENDAS');
    }
};
