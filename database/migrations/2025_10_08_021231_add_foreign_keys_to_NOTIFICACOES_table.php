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
        Schema::table('NOTIFICACOES', function (Blueprint $table) {
            $table->foreign(['id_destinatario'], 'fk_notificacao_destinatario_usuario')->references(['id_usuario'])->on('USUARIOS')->onUpdate('no action')->onDelete('restrict');
            $table->foreign(['id_remetente'], 'fk_notificacao_remetente_usuario')->references(['id_usuario'])->on('USUARIOS')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('NOTIFICACOES', function (Blueprint $table) {
            $table->dropForeign('fk_notificacao_destinatario_usuario');
            $table->dropForeign('fk_notificacao_remetente_usuario');
        });
    }
};
