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
        Schema::create('mudancas', function (Blueprint $table) {
            $table->id();
            $table->dateTime('data')->nullable();
            $table->enum('status', ['pendente', 'aprovado', 'em_andamento', 'finalizado'])->nullable();
            $table->text('observacao')->nullable();
            $table->unsignedBigInteger('id_morador')->index('idx_mudanca_usuario_idmorador');
            $table->unsignedBigInteger('id_autorizador')->index('idx_mudanca_usuario_idautorizador')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mudancas');
    }
};
