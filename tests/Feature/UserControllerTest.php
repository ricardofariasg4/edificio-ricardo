<?php

namespace Tests\Feature;

use App\Enum\PeopleBuilding;
use App\Models\Boleto;
use App\Models\Morador;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sindico_lista_usuarios(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        Morador::factory()->create();

        $response = $this->actingAs($sindico)->getJson('/users');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json());
    }

    public function test_morador_nao_lista_usuarios(): void
    {
        $morador1 = Morador::factory()->create();
        Morador::factory()->create();

        $response = $this->actingAs($morador1->usuario)->getJson('/users');

        $response->assertStatus(403);
    }

    public function test_sindico_ve_usuario_especifico(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Morador::factory()->create();

        $response = $this->actingAs($sindico)->getJson("/user/{$morador->usuario_id}");

        $response->assertStatus(200);
        $this->assertEquals($morador->usuario_id, $response->json('id'));
    }

    public function test_morador_ve_a_si_mesmo(): void
    {
        $morador = Morador::factory()->create();

        $response = $this->actingAs($morador->usuario)->getJson("/user/{$morador->usuario_id}");

        $response->assertStatus(200);
    }

    public function test_morador_nao_ve_outro_morador(): void
    {
        $morador1 = Morador::factory()->create();
        $morador2 = Morador::factory()->create();

        $response = $this->actingAs($morador1->usuario)->getJson("/user/{$morador2->usuario_id}");

        $response->assertStatus(403);
    }

    public function test_sindico_deleta_morador_sem_boletos_pendentes(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Morador::factory()->create();

        $response = $this->actingAs($sindico)->deleteJson("/user/{$morador->usuario_id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('usuarios', ['id' => $morador->usuario_id]);
    }

    public function test_sindico_nao_deleta_morador_com_boletos_pendentes(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Morador::factory()->create();

        Boleto::factory()->create([
            'id_morador' => $morador->usuario_id,
            'status_pagamento' => 0,
            'id_notificador' => $sindico->id,
        ]);

        $response = $this->actingAs($sindico)->deleteJson("/user/{$morador->usuario_id}");

        $response->assertStatus(400);
        $this->assertDatabaseHas('usuarios', ['id' => $morador->usuario_id]);
    }

    public function test_morador_nao_pode_deletar_outro_usuario(): void
    {
        $morador1 = Morador::factory()->create();
        $morador2 = Morador::factory()->create();

        $response = $this->actingAs($morador1->usuario)->deleteJson("/user/{$morador2->usuario_id}");

        $response->assertStatus(403);
    }

    public function test_usuario_nao_pode_deletar_a_si_mesmo(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);

        $response = $this->actingAs($sindico)->deleteJson("/user/{$sindico->id}");

        $response->assertStatus(403);
    }

    public function test_porteiro_nao_pode_deletar_usuario(): void
    {
        $porteiro = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::PORTEIRO]);
        $morador = Morador::factory()->create();

        $response = $this->actingAs($porteiro)->deleteJson("/user/{$morador->usuario_id}");

        $response->assertStatus(403);
    }

    public function test_sindico_nao_pode_atualizar_outro_usuario(): void
    {
        // Gate update-internal-member exige que o ator seja o próprio dono
        // do registro (ver app/Providers/AppServiceProvider.php) — nem
        // síndico/admin podem editar dados de outro usuário por essa rota.
        // Documentado como ponto a validar com o PO em débitos técnicos.
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Morador::factory()->create();

        $response = $this->actingAs($sindico)->putJson("/user/{$morador->usuario_id}", [
            'nome' => 'Novo Nome',
        ]);

        $response->assertStatus(403);
    }

    public function test_usuario_pode_atualizar_a_si_mesmo(): void
    {
        $morador = Morador::factory()->create();

        $response = $this->actingAs($morador->usuario)->putJson("/user/{$morador->usuario_id}", [
            'nome' => 'Novo Nome do Morador',
        ]);

        $response->assertStatus(200);
    }

    public function test_morador_nao_pode_atualizar_outro_usuario(): void
    {
        $morador1 = Morador::factory()->create();
        $morador2 = Morador::factory()->create();

        $response = $this->actingAs($morador1->usuario)->putJson("/user/{$morador2->usuario_id}", [
            'nome' => 'Nome alterado',
        ]);

        $response->assertStatus(403);
    }
}
