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
        Schema::table('MUDANCAS', function (Blueprint $table) {
            $table->foreign(['id_morador'], 'fk_mudanca_morador_idMorador')->references(['id_usuario'])->on('MORADORES')->onUpdate('no action')->onDelete('restrict');
            $table->foreign(['id_autorizador'], 'fk_mudanca_usuario_idAutorizador')->references(['id_usuario'])->on('USUARIOS')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('MUDANCAS', function (Blueprint $table) {
            $table->dropForeign('fk_mudanca_morador_idMorador');
            $table->dropForeign('fk_mudanca_usuario_idAutorizador');
        });
    }
};
