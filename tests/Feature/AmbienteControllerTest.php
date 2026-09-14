<?php

namespace Tests\Feature;

use App\Enum\PeopleBuilding;
use App\Models\Ambiente;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmbienteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_qualquer_autenticado_lista_ambientes(): void
    {
        $morador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        Ambiente::factory()->count(3)->create();

        $response = $this->actingAs($morador)->getJson('/ambientes');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json());
    }

    public function test_sindico_cria_ambiente(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);

        $response = $this->actingAs($sindico)->postJson('/ambiente', [
            'nome' => 'Salão de festas',
            'descricao' => 'Espaço para eventos',
            'capacidade' => 50,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('AMBIENTES', ['nome' => 'Salão de festas', 'capacidade' => 50]);
    }

    public function test_morador_nao_pode_criar_ambiente(): void
    {
        $morador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);

        $response = $this->actingAs($morador)->postJson('/ambiente', [
            'nome' => 'Salão de festas',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('AMBIENTES', ['nome' => 'Salão de festas']);
    }

    public function test_sindico_atualiza_ambiente(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $ambiente = Ambiente::factory()->create(['nome' => 'Nome antigo']);

        $response = $this->actingAs($sindico)->putJson("/ambiente/{$ambiente->id_ambiente}", [
            'nome' => 'Nome novo',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('AMBIENTES', ['id_ambiente' => $ambiente->id_ambiente, 'nome' => 'Nome novo']);
    }

    public function test_sindico_deleta_ambiente(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $ambiente = Ambiente::factory()->create();

        $response = $this->actingAs($sindico)->deleteJson("/ambiente/{$ambiente->id_ambiente}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('AMBIENTES', ['id_ambiente' => $ambiente->id_ambiente]);
    }

    public function test_disponibilidade_retorna_apenas_datas_confirmadas(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador1 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $morador2 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $ambiente = Ambiente::factory()->create();

        \App\Models\Reserva::factory()->create([
            'id_ambiente' => $ambiente->id_ambiente,
            'id_usuario' => $morador1->id_usuario,
            'data' => '2027-01-10',
            'status' => 'confirmada',
        ]);
        \App\Models\Reserva::factory()->create([
            'id_ambiente' => $ambiente->id_ambiente,
            'id_usuario' => $morador2->id_usuario,
            'data' => '2027-01-15',
            'status' => 'cancelada',
        ]);

        $response = $this->actingAs($morador1)->getJson("/ambiente/{$ambiente->id_ambiente}/disponibilidade");

        $response->assertStatus(200);
        $datas = $response->json('datas_ocupadas');
        $this->assertContains('2027-01-10', $datas);
        $this->assertNotContains('2027-01-15', $datas);
    }
}
