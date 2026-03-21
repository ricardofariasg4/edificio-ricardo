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
        Schema::create('MUDANCAS', function (Blueprint $table) {
            $table->increments('id_mudanca');
            $table->dateTime('data')->nullable();
            $table->enum('status', ['pendente', 'aprovado', 'em_andamento', 'finalizado'])->nullable();
            $table->text('observacao')->nullable();
            $table->unsignedInteger('id_morador')->index('idx_mudanca_usuario_idmorador');
            $table->unsignedInteger('id_autorizador')->index('idx_mudanca_usuario_idautorizador')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('MUDANCAS');
    }
};
