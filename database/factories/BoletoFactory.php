<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Boleto>
 */
class BoletoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_morador' => \App\Models\Morador::inRandomOrder()->value('id_usuario'),
            'status_pagamento' => $this->faker->numberBetween(0, 1),
            'vencimento' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
            'valor' => $this->faker->randomFloat(2, 250, 450),
            'id_notificador' => $this->faker->randomElement([
                \App\Models\Sindico::inRandomOrder()->value('id_usuario'),
                \App\Models\Porteiro::inRandomOrder()->value('id_usuario'),
            ]),
        ];
    }
}
