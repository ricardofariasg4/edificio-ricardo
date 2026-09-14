<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mudanca>
 */
class MudancaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $possiveisAutorizadores = [
            \App\Models\Sindico::inRandomOrder()->value('usuario_id'),
            \App\Models\Porteiro::inRandomOrder()->value('usuario_id')
        ];

        $autorizador = $this->faker->randomElement($possiveisAutorizadores);

        return [
            'data' => $this->faker->date(),
            'status' => $this->faker->randomElement(['pendente', 'aprovado', 'em_andamento', 'finalizado']),
            'observacao' => $this->faker->sentence(),
            'id_morador' => \App\Models\Morador::inRandomOrder()->value('usuario_id'),
            'id_autorizador' => $autorizador,
        ];
    }
}
