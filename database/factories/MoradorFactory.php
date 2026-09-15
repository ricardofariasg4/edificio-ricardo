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
            'usuario_id' => \App\Models\Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR])->id,
            'numero_apto' => $this->faker->numberBetween(1, 800),
        ];
    }
}
