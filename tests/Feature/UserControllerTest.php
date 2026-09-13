<?php

namespace Tests\Feature;

use App\Enum\PeopleBuilding;
use App\Models\Boleto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sindico_lista_usuarios(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);

        $response = $this->actingAs($sindico)->getJson('/users');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_morador_nao_lista_usuarios(): void
    {
        $morador1 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $morador2 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);

        $response = $this->actingAs($morador1)->getJson('/users');

        $response->assertStatus(403);
    }

    public function test_sindico_ve_usuario_especifico(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);

        $response = $this->actingAs($sindico)->getJson("/user/{$morador->id_usuario}");

        $response->assertStatus(200);
        $this->assertEquals($morador->id_usuario, $response->json('id_usuario'));
    }

    public function test_morador_ve_a_si_mesmo(): void
    {
        $morador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);

        $response = $this->actingAs($morador)->getJson("/user/{$morador->id_usuario}");

        $response->assertStatus(200);
    }

    public function test_morador_nao_ve_outro_morador(): void
    {
        $morador1 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $morador2 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);

        $response = $this->actingAs($morador1)->getJson("/user/{$morador2->id_usuario}");

        $response->assertStatus(403);
    }

    public function test_sindico_deleta_morador_sem_boletos_pendentes(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);

        $response = $this->actingAs($sindico)->deleteJson("/user/{$morador->id_usuario}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('USUARIOS', ['id_usuario' => $morador->id_usuario]);
    }

    public function test_sindico_nao_deleta_morador_com_boletos_pendentes(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);

        Boleto::factory()->create([
            'id_morador' => $morador->id_usuario,
            'status_pagamento' => 0,
            'id_notificador' => $sindico->id_usuario,
        ]);

        $response = $this->actingAs($sindico)->deleteJson("/user/{$morador->id_usuario}");

        $response->assertStatus(400);
        $this->assertDatabaseHas('USUARIOS', ['id_usuario' => $morador->id_usuario]);
    }

    public function test_morador_nao_pode_deletar_outro_usuario(): void
    {
        $morador1 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $morador2 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);

        $response = $this->actingAs($morador1)->deleteJson("/user/{$morador2->id_usuario}");

        $response->assertStatus(403);
    }

    public function test_usuario_nao_pode_deletar_a_si_mesmo(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);

        $response = $this->actingAs($sindico)->deleteJson("/user/{$sindico->id_usuario}");

        $response->assertStatus(403);
    }

    public function test_porteiro_nao_pode_deletar_usuario(): void
    {
        $porteiro = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::PORTEIRO]);
        $morador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);

        $response = $this->actingAs($porteiro)->deleteJson("/user/{$morador->id_usuario}");

        $response->assertStatus(403);
    }

    public function test_sindico_atualiza_usuario(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);

        $response = $this->actingAs($sindico)->putJson("/user/{$morador->id_usuario}", [
            'nome' => 'Novo Nome',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('USUARIOS', [
            'id_usuario' => $morador->id_usuario,
            'nome' => 'Novo Nome',
        ]);
    }

    public function test_usuario_pode_atualizar_a_si_mesmo(): void
    {
        $morador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);

        $response = $this->actingAs($morador)->putJson("/user/{$morador->id_usuario}", [
            'nome' => 'Novo Nome do Morador',
        ]);

        $response->assertStatus(200);
    }

    public function test_morador_nao_pode_atualizar_outro_usuario(): void
    {
        $morador1 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $morador2 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);

        $response = $this->actingAs($morador1)->putJson("/user/{$morador2->id_usuario}", [
            'nome' => 'Nome alterado',
        ]);

        $response->assertStatus(403);
    }
}
