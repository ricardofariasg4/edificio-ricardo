<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PrestadorDeServico>
 */
class PrestadorDeServicoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_usuario' => \App\Models\Usuario::factory()->create(['tipo_usuario' => 'prestador'])->id_usuario,
            'data_ultimo_trabalho' => $this->faker->date(),
        ];
    }
}
