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
        Schema::create('boletos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_morador')->index('idx_boleto_morador_idmorador');
            $table->unsignedTinyInteger('status_pagamento')->nullable();
            $table->date('vencimento')->nullable();
            $table->decimal('valor', 10)->unsigned()->nullable();
            $table->unsignedBigInteger('id_notificador')->index('idx_boleto_usuario_idnotificador');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boletos');
    }
};
