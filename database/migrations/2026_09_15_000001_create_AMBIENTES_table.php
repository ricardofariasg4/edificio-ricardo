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
        Schema::create('AMBIENTES', function (Blueprint $table) {
            $table->increments('id_ambiente');
            $table->string('nome', 100);
            $table->text('descricao')->nullable();
            $table->unsignedInteger('capacidade')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('AMBIENTES');
    }
};
