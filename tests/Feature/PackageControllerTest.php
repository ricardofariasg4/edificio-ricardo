<?php

namespace Tests\Feature;

use App\Enum\PeopleBuilding;
use App\Models\Encomenda;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sindico_lista_todas_encomendas(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador1 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $morador2 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);

        Encomenda::factory()->create(['id_usuario' => $morador1->id]);
        Encomenda::factory()->create(['id_usuario' => $morador2->id]);

        $response = $this->actingAs($sindico)->getJson('/packages');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json());
    }

    public function test_morador_lista_apenas_suas_encomendas(): void
    {
        $morador1 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $morador2 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);

        Encomenda::factory()->create(['id_usuario' => $morador1->id]);
        Encomenda::factory()->create(['id_usuario' => $morador2->id]);

        $response = $this->actingAs($morador1)->getJson('/packages');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
    }

    public function test_sindico_cria_encomenda(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);

        $response = $this->actingAs($sindico)->postJson('/package', [
            'codigo_rastreio' => 'BR123456789',
            'data_recebimento' => '2024-09-15',
            'id_usuario' => $morador->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('encomendas', [
            'codigo_rastreio' => 'BR123456789',
            'id_usuario' => $morador->id,
        ]);
    }

    public function test_morador_nao_pode_criar_encomenda(): void
    {
        $morador1 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $morador2 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);

        $response = $this->actingAs($morador1)->postJson('/package', [
            'codigo_rastreio' => 'BR123456789',
            'data_recebimento' => '2024-09-15',
            'id_usuario' => $morador2->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_sindico_ve_encomenda_especifica(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $encomenda = Encomenda::factory()->create(['id_usuario' => $morador->id]);

        $response = $this->actingAs($sindico)->getJson("/package/{$encomenda->id}");

        $response->assertStatus(200);
        $this->assertEquals($encomenda->id, $response->json('id'));
    }

    public function test_morador_ve_sua_encomenda(): void
    {
        $morador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $encomenda = Encomenda::factory()->create(['id_usuario' => $morador->id]);

        $response = $this->actingAs($morador)->getJson("/package/{$encomenda->id}");

        $response->assertStatus(200);
    }

    public function test_morador_nao_ve_encomenda_de_outro(): void
    {
        $morador1 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $morador2 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $encomenda = Encomenda::factory()->create(['id_usuario' => $morador2->id]);

        $response = $this->actingAs($morador1)->getJson("/package/{$encomenda->id}");

        $response->assertStatus(403);
    }

    public function test_sindico_atualiza_encomenda(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $encomenda = Encomenda::factory()->create(['id_usuario' => $morador->id]);

        $response = $this->actingAs($sindico)->putJson("/package/{$encomenda->id}", [
            'codigo_rastreio' => 'BR987654321',
            'data_recebimento' => '2024-09-20',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('encomendas', [
            'id' => $encomenda->id,
            'codigo_rastreio' => 'BR987654321',
        ]);
    }

    public function test_sindico_deleta_encomenda(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $encomenda = Encomenda::factory()->create(['id_usuario' => $morador->id]);

        $response = $this->actingAs($sindico)->deleteJson("/package/{$encomenda->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('encomendas', ['id' => $encomenda->id]);
    }
}
