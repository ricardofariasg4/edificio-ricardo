<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ambiente>
 */
class AmbienteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => $this->faker->randomElement(['Salão de festas', 'Churrasqueira', 'Quadra poliesportiva', 'Espaço gourmet']),
            'descricao' => $this->faker->sentence(),
            'capacidade' => $this->faker->numberBetween(10, 100),
        ];
    }
}
