<?php

namespace Tests\Feature;

use App\Enum\PeopleBuilding;
use App\Models\Morador;
use App\Models\Mudanca;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoveControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sindico_aprova_mudanca_definitivamente(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Morador::factory()->create();
        $move = Mudanca::factory()->create([
            'id_morador' => $morador->id_usuario,
            'status' => 'pendente',
        ]);

        $response = $this->actingAs($sindico)->postJson("/move/{$move->id_mudanca}/decision", [
            'decision' => 'aprovado',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('MUDANCAS', [
            'id_mudanca' => $move->id_mudanca,
            'status' => 'aprovado',
            'id_autorizador' => $sindico->id_usuario,
        ]);
    }

    public function test_porteiro_aprova_mudanca_provisoriamente(): void
    {
        $porteiro = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::PORTEIRO]);
        $morador = Morador::factory()->create();
        $move = Mudanca::factory()->create([
            'id_morador' => $morador->id_usuario,
            'status' => 'pendente',
        ]);

        $response = $this->actingAs($porteiro)->postJson("/move/{$move->id_mudanca}/decision", [
            'decision' => 'aprovado',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('MUDANCAS', [
            'id_mudanca' => $move->id_mudanca,
            'status' => 'em_andamento',
            'id_autorizador' => $porteiro->id_usuario,
        ]);
    }

    public function test_recusa_mudanca_com_observacao(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Morador::factory()->create();
        $move = Mudanca::factory()->create([
            'id_morador' => $morador->id_usuario,
            'status' => 'pendente',
        ]);

        $response = $this->actingAs($sindico)->postJson("/move/{$move->id_mudanca}/decision", [
            'decision' => 'recusado',
            'observacao' => '  Conflito com agendar de manutenção  ',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('MUDANCAS', [
            'id_mudanca' => $move->id_mudanca,
            'status' => 'recusado',
            'observacao' => 'Conflito com agendar de manutenção',
            'id_autorizador' => $sindico->id_usuario,
        ]);
    }

    public function test_morador_nao_pode_decidir_mudanca(): void
    {
        $morador1 = Morador::factory()->create();
        $morador2 = Morador::factory()->create();
        $move = Mudanca::factory()->create([
            'id_morador' => $morador2->id_usuario,
            'status' => 'pendente',
        ]);

        $response = $this->actingAs($morador1->usuario)->postJson("/move/{$move->id_mudanca}/decision", [
            'decision' => 'aprovado',
        ]);

        $response->assertStatus(403);
    }

    public function test_listagem_mudancas_recusadas_morador_vee_apenas_suas(): void
    {
        $morador1 = Morador::factory()->create();
        $morador2 = Morador::factory()->create();

        $move1 = Mudanca::factory()->create([
            'id_morador' => $morador1->id_usuario,
            'status' => 'recusado',
            'observacao' => 'Conflito de data',
        ]);
        Mudanca::factory()->create([
            'id_morador' => $morador2->id_usuario,
            'status' => 'recusado',
            'observacao' => 'Fora do horário',
        ]);

        $response = $this->actingAs($morador1->usuario)->getJson('/moves/rejected');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
        $this->assertEquals($move1->id_mudanca, $response->json('0.id_mudanca'));
    }

    public function test_listagem_mudancas_recusadas_sindico_vee_todas(): void
    {
        $morador1 = Morador::factory()->create();
        $morador2 = Morador::factory()->create();
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);

        Mudanca::factory()->create([
            'id_morador' => $morador1->id_usuario,
            'status' => 'recusado',
            'observacao' => 'Conflito de data',
        ]);
        Mudanca::factory()->create([
            'id_morador' => $morador2->id_usuario,
            'status' => 'recusado',
            'observacao' => 'Fora do horário',
        ]);

        $response = $this->actingAs($sindico)->getJson('/moves/rejected');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json());
    }

    public function test_listagem_mudancas_recusadas_sem_observacao_nao_aparece(): void
    {
        $morador = Morador::factory()->create();
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);

        Mudanca::factory()->create([
            'id_morador' => $morador->id_usuario,
            'status' => 'recusado',
            'observacao' => null,
        ]);

        $response = $this->actingAs($sindico)->getJson('/moves/rejected');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json());
    }
}
