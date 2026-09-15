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
        Schema::table('mudancas', function (Blueprint $table) {
            $table->foreign(['id_morador'], 'fk_mudancas_morador_usuario_id')->references(['usuario_id'])->on('moradores')->onUpdate('no action')->onDelete('restrict');
            $table->foreign(['id_autorizador'], 'fk_mudancas_autorizador_id')->references(['id'])->on('usuarios')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mudancas', function (Blueprint $table) {
            $table->dropForeign('fk_mudancas_morador_usuario_id');
            $table->dropForeign('fk_mudancas_autorizador_id');
        });
    }
};
