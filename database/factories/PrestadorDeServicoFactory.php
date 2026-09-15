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
            'usuario_id' => \App\Models\Usuario::factory()->create(['tipo_usuario' => 'prestador'])->id,
            'data_ultimo_trabalho' => $this->faker->date(),
        ];
    }
}
