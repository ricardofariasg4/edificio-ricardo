<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Visitante>
 */
class VisitanteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_usuario' => \App\Models\Usuario::factory()->create(['tipo_usuario' => 'visitante'])->id_usuario,
            'visita_de' => \App\Models\Morador::inRandomOrder()->value('id_usuario'),
        ];
    }
}
