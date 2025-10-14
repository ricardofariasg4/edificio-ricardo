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
        Schema::table('PETS', function (Blueprint $table) {
            $table->foreign(['id_morador'], 'fk_pet_morador_morador')->references(['id_usuario'])->on('MORADORES')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('PETS', function (Blueprint $table) {
            $table->dropForeign('fk_pet_morador_morador');
        });
    }
};
