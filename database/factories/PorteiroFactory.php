<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Porteiro>
 */
class PorteiroFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => \App\Models\Usuario::factory()->create(['tipo_usuario' => 'porteiro'])->id,
            'turno_de_trabalho' => $this->faker->randomElement(['M', 'T', 'N']),
        ];
    }
}
