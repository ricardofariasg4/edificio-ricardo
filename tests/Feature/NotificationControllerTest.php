<?php

namespace Tests\Feature;

use App\Enum\PeopleBuilding;
use App\Models\Morador;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_porteiro_notifica_entrega_para_morador(): void
    {
        $porteiro = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::PORTEIRO]);
        $morador = Morador::factory()->create();

        $response = $this->actingAs($porteiro)->postJson('/notifications/delivery', [
            'id_destinatario' => $morador->usuario_id,
            'aplicativo' => 'ifood',
            'observacao' => 'Deixado com o porteiro do turno da tarde',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $morador->usuario_id,
            'notifiable_type' => Usuario::class,
        ]);
    }

    public function test_morador_nao_pode_notificar_entrega(): void
    {
        $morador = Morador::factory()->create();
        $outroMorador = Morador::factory()->create();

        $response = $this->actingAs($morador->usuario)->postJson('/notifications/delivery', [
            'id_destinatario' => $outroMorador->usuario_id,
            'aplicativo' => 'rappi',
        ]);

        $response->assertStatus(403);
    }

    public function test_sindico_nao_pode_notificar_entrega(): void
    {
        // RF03: notificação de entrega é enviada apenas por porteiros
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Morador::factory()->create();

        $response = $this->actingAs($sindico)->postJson('/notifications/delivery', [
            'id_destinatario' => $morador->usuario_id,
            'aplicativo' => 'ifood',
        ]);

        $response->assertStatus(403);
    }

    public function test_sindico_notifica_manutencao_para_todos_moradores(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador1 = Morador::factory()->create();
        $morador2 = Morador::factory()->create();

        $response = $this->actingAs($sindico)->postJson('/notifications/maintenance', [
            'titulo' => 'Manutenção do elevador social',
            'descricao' => 'Elevador ficará indisponível das 8h às 12h',
            'data_agendada' => now()->addDays(3)->format('Y-m-d H:i:s'),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $morador1->usuario_id]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $morador2->usuario_id]);
    }

    public function test_morador_nao_pode_notificar_manutencao(): void
    {
        $morador = Morador::factory()->create();

        $response = $this->actingAs($morador->usuario)->postJson('/notifications/maintenance', [
            'titulo' => 'Manutenção da caixa d\'água',
            'descricao' => 'Interrupção no fornecimento de água',
            'data_agendada' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ]);

        $response->assertStatus(403);
    }

    public function test_criar_encomenda_notifica_destinatario_automaticamente(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Morador::factory()->create();

        $response = $this->actingAs($sindico)->postJson('/package', [
            'codigo_rastreio' => 'BR999888777',
            'data_recebimento' => '2024-09-15',
            'id_usuario' => $morador->usuario_id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $morador->usuario_id,
            'type' => \App\Notifications\PackageArrivedNotification::class,
        ]);
    }

    public function test_criar_mudanca_notifica_sindicos_e_porteiros(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $porteiro = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::PORTEIRO]);
        $morador = Morador::factory()->create();

        $response = $this->actingAs($morador->usuario)->postJson('/move', [
            'data' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'id_morador' => $morador->usuario_id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $sindico->id,
            'type' => \App\Notifications\MoveApprovalRequiredNotification::class,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $porteiro->id,
            'type' => \App\Notifications\MoveApprovalRequiredNotification::class,
        ]);
    }

    public function test_usuario_lista_suas_proprias_notificacoes(): void
    {
        $porteiro = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::PORTEIRO]);
        $morador = Morador::factory()->create();

        $this->actingAs($porteiro)->postJson('/notifications/delivery', [
            'id_destinatario' => $morador->usuario_id,
            'aplicativo' => 'ifood',
        ]);

        $response = $this->actingAs($morador->usuario)->getJson('/notifications');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(1, $response->json('meta.unread_count'));
    }

    public function test_usuario_marca_notificacao_como_lida(): void
    {
        $porteiro = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::PORTEIRO]);
        $morador = Morador::factory()->create();

        $this->actingAs($porteiro)->postJson('/notifications/delivery', [
            'id_destinatario' => $morador->usuario_id,
            'aplicativo' => 'ifood',
        ]);

        $listResponse = $this->actingAs($morador->usuario)->getJson('/notifications');
        $notificationId = $listResponse->json('data.0.id');

        $readResponse = $this->actingAs($morador->usuario)->postJson("/notification/{$notificationId}/read");
        $readResponse->assertStatus(200);

        $this->assertDatabaseMissing('notifications', ['id' => $notificationId, 'read_at' => null]);
    }

    public function test_marcar_notificacao_inexistente_retorna_404(): void
    {
        $morador = Morador::factory()->create();

        $response = $this->actingAs($morador->usuario)->postJson('/notification/00000000-0000-0000-0000-000000000000/read');

        $response->assertStatus(404);
    }
}
