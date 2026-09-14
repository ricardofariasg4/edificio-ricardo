<?php

namespace Tests\Feature;

use App\Enum\PeopleBuilding;
use App\Models\Boleto;
use App\Models\Morador;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sindico_lista_todos_boletos(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador1 = Morador::factory()->create();
        $morador2 = Morador::factory()->create();

        Boleto::factory()->create(['id_morador' => $morador1->id_usuario, 'id_notificador' => $sindico->id_usuario]);
        Boleto::factory()->create(['id_morador' => $morador2->id_usuario, 'id_notificador' => $sindico->id_usuario]);

        $response = $this->actingAs($sindico)->getJson('/invoices');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json());
    }

    public function test_morador_lista_apenas_seus_boletos(): void
    {
        $morador1 = Morador::factory()->create();
        $morador2 = Morador::factory()->create();
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);

        Boleto::factory()->create(['id_morador' => $morador1->id_usuario, 'id_notificador' => $sindico->id_usuario]);
        Boleto::factory()->create(['id_morador' => $morador2->id_usuario, 'id_notificador' => $sindico->id_usuario]);

        $response = $this->actingAs($morador1->usuario)->getJson('/invoices');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
    }

    public function test_sindico_cria_boleto_com_notificador_automatico(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Morador::factory()->create();

        $response = $this->actingAs($sindico)->postJson('/invoice', [
            'valor' => 500.00,
            'vencimento' => '2024-10-31',
            'status_pagamento' => 0,
            'id_morador' => $morador->id_usuario,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('BOLETOS', [
            'valor' => 500.00,
            'id_morador' => $morador->id_usuario,
            'id_notificador' => $sindico->id_usuario,
        ]);
    }

    public function test_morador_nao_pode_criar_boleto(): void
    {
        $morador1 = Morador::factory()->create();
        $morador2 = Morador::factory()->create();

        $response = $this->actingAs($morador1->usuario)->postJson('/invoice', [
            'valor' => 500.00,
            'vencimento' => '2024-10-31',
            'status_pagamento' => 0,
            'id_morador' => $morador2->id_usuario,
        ]);

        $response->assertStatus(403);
    }

    public function test_sindico_ve_boleto_especifico(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Morador::factory()->create();
        $boleto = Boleto::factory()->create(['id_morador' => $morador->id_usuario, 'id_notificador' => $sindico->id_usuario]);

        $response = $this->actingAs($sindico)->getJson("/invoice/{$boleto->id_boleto}");

        $response->assertStatus(200);
        $this->assertEquals($boleto->id_boleto, $response->json('id_boleto'));
    }

    public function test_morador_ve_seu_boleto(): void
    {
        $morador = Morador::factory()->create();
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $boleto = Boleto::factory()->create(['id_morador' => $morador->id_usuario, 'id_notificador' => $sindico->id_usuario]);

        $response = $this->actingAs($morador->usuario)->getJson("/invoice/{$boleto->id_boleto}");

        $response->assertStatus(200);
    }

    public function test_morador_nao_ve_boleto_de_outro(): void
    {
        $morador1 = Morador::factory()->create();
        $morador2 = Morador::factory()->create();
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $boleto = Boleto::factory()->create(['id_morador' => $morador2->id_usuario, 'id_notificador' => $sindico->id_usuario]);

        $response = $this->actingAs($morador1->usuario)->getJson("/invoice/{$boleto->id_boleto}");

        $response->assertStatus(403);
    }

    public function test_sindico_atualiza_boleto(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Morador::factory()->create();
        $boleto = Boleto::factory()->create(['id_morador' => $morador->id_usuario, 'id_notificador' => $sindico->id_usuario]);

        $response = $this->actingAs($sindico)->putJson("/invoice/{$boleto->id_boleto}", [
            'valor' => 600.00,
            'status_pagamento' => 1,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('BOLETOS', [
            'id_boleto' => $boleto->id_boleto,
            'valor' => 600.00,
            'status_pagamento' => 1,
        ]);
    }

    public function test_sindico_deleta_boleto(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Morador::factory()->create();
        $boleto = Boleto::factory()->create(['id_morador' => $morador->id_usuario, 'id_notificador' => $sindico->id_usuario]);

        $response = $this->actingAs($sindico)->deleteJson("/invoice/{$boleto->id_boleto}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('BOLETOS', ['id_boleto' => $boleto->id_boleto]);
    }
}
