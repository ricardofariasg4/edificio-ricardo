<?php

namespace Database\Factories;

use App\Enum\PeopleBuilding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Morador>
 */
class MoradorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_usuario' => \App\Models\Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR])->id_usuario,
            'numero_apto' => $this->faker->numberBetween(1, 800),
        ];
    }
}
