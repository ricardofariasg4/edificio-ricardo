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
        Schema::table('ENCOMENDAS', function (Blueprint $table) {
            $table->foreign(['id_usuario'], 'fk_encomenda_usuario_usuario')->references(['id_usuario'])->on('USUARIOS')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ENCOMENDAS', function (Blueprint $table) {
            $table->dropForeign('fk_encomenda_usuario_usuario');
        });
    }
};
