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
        Schema::create('NOTIFICACOES', function (Blueprint $table) {
            $table->increments('id_notificacao');
            $table->string('titulo', 45)->nullable();
            $table->text('mensagem')->nullable();
            $table->string('icone')->nullable();
            $table->string('imagem')->nullable();
            $table->dateTime('data_envio')->nullable();
            $table->unsignedInteger('id_remetente')->index('idx_notificacao_usuario_idremetente');
            $table->unsignedInteger('id_destinatario')->index('idx_notificacao_usuario_iddestinario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('NOTIFICACOES');
    }
};
