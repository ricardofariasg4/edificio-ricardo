<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reserva>
 */
class ReservaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_ambiente' => \App\Models\Ambiente::factory(),
            'id_usuario' => \App\Models\Usuario::factory(),
            'data' => $this->faker->dateTimeBetween('+1 day', '+2 months')->format('Y-m-d'),
            'status' => 'confirmada',
            'posicao_fila' => null,
        ];
    }
}
