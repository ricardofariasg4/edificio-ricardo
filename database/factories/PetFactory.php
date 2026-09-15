<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pet>
 */
class PetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => $this->faker->firstName(),
            'peso' => $this->faker->numberBetween(1, 50),
            'vacinado' => $this->faker->boolean(),
            'cpf' => $this->faker->unique()->numerify('###########'),
            'id_morador' => \App\Models\Morador::inRandomOrder()->value('usuario_id'),
        ];
    }
}
