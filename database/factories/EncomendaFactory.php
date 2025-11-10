<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Encomenda>
 */
class EncomendaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo_rastreio' => $this->faker->unique()->bothify('??##########'),
            'data_recebimento' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'id_usuario' => \App\Models\Usuario::factory()->create()->id_usuario,
        ];
    }
}
