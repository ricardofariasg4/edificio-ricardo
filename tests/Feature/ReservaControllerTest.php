<?php

namespace Tests\Feature;

use App\Enum\PeopleBuilding;
use App\Models\Ambiente;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_primeira_reserva_para_uma_data_e_confirmada(): void
    {
        $morador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $ambiente = Ambiente::factory()->create();

        $response = $this->actingAs($morador)->postJson('/reserva', [
            'id_ambiente' => $ambiente->id_ambiente,
            'data' => now()->addDays(10)->format('Y-m-d'),
        ]);

        $response->assertStatus(201);
        $this->assertEquals('confirmada', $response->json('reserva.status'));
        $this->assertDatabaseHas('RESERVAS', [
            'id_ambiente' => $ambiente->id_ambiente,
            'id_usuario' => $morador->id_usuario,
            'status' => 'confirmada',
        ]);
    }

    public function test_segunda_reserva_na_mesma_data_entra_na_fila(): void
    {
        $morador1 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $morador2 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $ambiente = Ambiente::factory()->create();
        $data = now()->addDays(10)->format('Y-m-d');

        $this->actingAs($morador1)->postJson('/reserva', [
            'id_ambiente' => $ambiente->id_ambiente,
            'data' => $data,
        ]);

        $response = $this->actingAs($morador2)->postJson('/reserva', [
            'id_ambiente' => $ambiente->id_ambiente,
            'data' => $data,
        ]);

        $response->assertStatus(201);
        $this->assertEquals('fila_espera', $response->json('reserva.status'));
        $this->assertEquals(1, $response->json('reserva.posicao_fila'));
    }

    public function test_mesmo_usuario_nao_pode_solicitar_duas_vezes_para_mesmo_ambiente_e_data(): void
    {
        $morador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $ambiente = Ambiente::factory()->create();
        $data = now()->addDays(10)->format('Y-m-d');

        $this->actingAs($morador)->postJson('/reserva', [
            'id_ambiente' => $ambiente->id_ambiente,
            'data' => $data,
        ]);

        $response = $this->actingAs($morador)->postJson('/reserva', [
            'id_ambiente' => $ambiente->id_ambiente,
            'data' => $data,
        ]);

        $response->assertStatus(400);
    }

    public function test_cancelar_reserva_confirmada_promove_primeiro_da_fila_e_notifica(): void
    {
        $morador1 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $morador2 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $ambiente = Ambiente::factory()->create();
        $data = now()->addDays(10)->format('Y-m-d');

        $reservaConfirmada = Reserva::factory()->create([
            'id_ambiente' => $ambiente->id_ambiente,
            'id_usuario' => $morador1->id_usuario,
            'data' => $data,
            'status' => 'confirmada',
        ]);

        $reservaNaFila = Reserva::factory()->create([
            'id_ambiente' => $ambiente->id_ambiente,
            'id_usuario' => $morador2->id_usuario,
            'data' => $data,
            'status' => 'fila_espera',
            'posicao_fila' => 1,
        ]);

        $response = $this->actingAs($morador1)->deleteJson("/reserva/{$reservaConfirmada->id_reserva}");

        $response->assertStatus(200);
        $this->assertDatabaseHas('RESERVAS', [
            'id_reserva' => $reservaConfirmada->id_reserva,
            'status' => 'cancelada',
        ]);
        $this->assertDatabaseHas('RESERVAS', [
            'id_reserva' => $reservaNaFila->id_reserva,
            'status' => 'confirmada',
            'posicao_fila' => null,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $morador2->id_usuario,
            'type' => \App\Notifications\ReservationPromotedNotification::class,
        ]);
    }

    public function test_cancelar_reserva_sem_fila_apenas_marca_cancelada(): void
    {
        $morador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $ambiente = Ambiente::factory()->create();

        $reserva = Reserva::factory()->create([
            'id_ambiente' => $ambiente->id_ambiente,
            'id_usuario' => $morador->id_usuario,
            'status' => 'confirmada',
        ]);

        $response = $this->actingAs($morador)->deleteJson("/reserva/{$reserva->id_reserva}");

        $response->assertStatus(200);
        $this->assertDatabaseHas('RESERVAS', ['id_reserva' => $reserva->id_reserva, 'status' => 'cancelada']);
    }

    public function test_outro_morador_nao_pode_cancelar_reserva_alheia(): void
    {
        $morador1 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $morador2 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $ambiente = Ambiente::factory()->create();

        $reserva = Reserva::factory()->create([
            'id_ambiente' => $ambiente->id_ambiente,
            'id_usuario' => $morador1->id_usuario,
            'status' => 'confirmada',
        ]);

        $response = $this->actingAs($morador2)->deleteJson("/reserva/{$reserva->id_reserva}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('RESERVAS', ['id_reserva' => $reserva->id_reserva, 'status' => 'confirmada']);
    }

    public function test_sindico_pode_cancelar_reserva_de_qualquer_morador(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $ambiente = Ambiente::factory()->create();

        $reserva = Reserva::factory()->create([
            'id_ambiente' => $ambiente->id_ambiente,
            'id_usuario' => $morador->id_usuario,
            'status' => 'confirmada',
        ]);

        $response = $this->actingAs($sindico)->deleteJson("/reserva/{$reserva->id_reserva}");

        $response->assertStatus(200);
    }

    public function test_morador_lista_apenas_suas_proprias_reservas(): void
    {
        $morador1 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $morador2 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $ambiente = Ambiente::factory()->create();

        Reserva::factory()->create(['id_ambiente' => $ambiente->id_ambiente, 'id_usuario' => $morador1->id_usuario]);
        Reserva::factory()->create(['id_ambiente' => $ambiente->id_ambiente, 'id_usuario' => $morador2->id_usuario]);

        $response = $this->actingAs($morador1)->getJson('/reservas');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
    }

    public function test_sindico_lista_todas_as_reservas(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador1 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $morador2 = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);
        $ambiente = Ambiente::factory()->create();

        Reserva::factory()->create(['id_ambiente' => $ambiente->id_ambiente, 'id_usuario' => $morador1->id_usuario]);
        Reserva::factory()->create(['id_ambiente' => $ambiente->id_ambiente, 'id_usuario' => $morador2->id_usuario]);

        $response = $this->actingAs($sindico)->getJson('/reservas');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json());
    }
}
