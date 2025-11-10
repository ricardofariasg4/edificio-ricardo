<?php

namespace Database\Seeders;

use App\Models\Boleto;
use App\Models\Encomenda;
use App\Models\Morador;
use App\Models\Mudanca;
use App\Models\Pet;
use App\Models\Porteiro;
use App\Models\PrestadorDeServico;
use App\Models\Visitante;
use App\Models\Sindico;
use Illuminate\Database\Seeder;

class EdificioRicardoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Sindico::factory()
            ->count(1)
            ->create();

        Porteiro::factory()
            ->count(3)
            ->create();

        Morador::factory()
            ->count(20)
            ->create();

        Visitante::factory()
            ->count(5)
            ->create();

        PrestadorDeServico::factory()
            ->count(5)
            ->create();

        Pet::factory()
            ->count(5)
            ->create();

        Mudanca::factory()
            ->count(5)
            ->create();

        Encomenda::factory()
            ->count(5)
            ->create();

        Boleto::factory()
            ->count(20)
            ->create();
    }
}
