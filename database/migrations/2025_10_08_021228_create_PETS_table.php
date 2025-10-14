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
        Schema::create('PETS', function (Blueprint $table) {
            $table->increments('id_pet');
            $table->string('nome')->nullable();
            $table->unsignedTinyInteger('peso')->nullable();
            $table->boolean('vacinado')->nullable();
            $table->char('cpf', 11)->nullable()->unique('cpf_unique');
            $table->unsignedInteger('id_morador')->index('idx_pet_morador_idmorador');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('PETS');
    }
};
