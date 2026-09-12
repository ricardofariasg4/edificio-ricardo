<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `MUDANCAS` MODIFY `status` ENUM('pendente','aprovado','em_andamento','finalizado','recusado') NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::table('MUDANCAS')->where('status', 'recusado')->update(['status' => 'pendente']);
            DB::statement("ALTER TABLE `MUDANCAS` MODIFY `status` ENUM('pendente','aprovado','em_andamento','finalizado') NULL");
        }
    }
};
