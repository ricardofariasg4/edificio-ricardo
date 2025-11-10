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
        Schema::table('VISITANTES', function (Blueprint $table) {
            $table->foreign(['id_usuario'], 'fk_visitante_usuario_idUsuario')->references(['id_usuario'])->on('USUARIOS')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['visita_de'], 'fk_visitante_usuario_visita_de')->references(['id_usuario'])->on('USUARIOS')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('VISITANTES', function (Blueprint $table) {
            $table->dropForeign('fk_visitante_usuario_idUsuario');
            $table->dropForeign('fk_visitante_usuario_visita_de');
        });
    }
};
