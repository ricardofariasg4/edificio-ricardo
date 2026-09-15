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
        Schema::create('RESERVAS', function (Blueprint $table) {
            $table->increments('id_reserva');
            $table->unsignedInteger('id_ambiente')->index('idx_reserva_ambiente_idambiente');
            $table->unsignedInteger('id_usuario')->index('idx_reserva_usuario_idusuario');
            $table->date('data');
            $table->enum('status', ['confirmada', 'fila_espera', 'cancelada']);
            $table->unsignedInteger('posicao_fila')->nullable();
            $table->timestamps();

            $table->foreign(['id_ambiente'], 'fk_reserva_ambiente_idambiente')
                ->references(['id_ambiente'])->on('AMBIENTES')
                ->onUpdate('no action')->onDelete('cascade');

            $table->foreign(['id_usuario'], 'fk_reserva_usuario_idusuario')
                ->references(['id_usuario'])->on('USUARIOS')
                ->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('RESERVAS');
    }
};
