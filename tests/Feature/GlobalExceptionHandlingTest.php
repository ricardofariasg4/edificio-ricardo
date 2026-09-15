<?php

namespace Tests\Feature;

use App\Enum\PeopleBuilding;
use App\Models\Boleto;
use App\Models\Morador;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cobre a "rede de segurança" registrada em bootstrap/app.php (issue #7):
 * uma exceção de domínio que escapa sem ser tratada por um controller
 * (ex.: EntityDeleteException em UserController::destroy, que não tem
 * try/catch) não pode vazar mensagem/stack trace crus ao cliente, e ainda
 * assim deve ser registrada no canal `critical`.
 */
class GlobalExceptionHandlingTest extends TestCase
{
    use RefreshDatabase;

    private string $logPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logPath = storage_path('logs/critical_test_' . uniqid() . '.log');
        config(['logging.channels.critical.path' => $this->logPath]);
    }

    protected function tearDown(): void
    {
        if (is_file($this->logPath)) {
            unlink($this->logPath);
        }

        parent::tearDown();
    }

    public function test_excecao_nao_tratada_pelo_controller_nao_vaza_detalhes_e_e_registrada(): void
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
        $response->assertExactJson(['error' => 'Erro interno do servidor.']);

        $this->assertFileExists($this->logPath);
        $logged = json_decode(trim(file_get_contents($this->logPath)), true);
        $this->assertSame('App\\Exceptions\\EntityDeleteException', $logged['context']['exception']);
        $this->assertDatabaseHas('moradores', ['usuario_id' => $morador->usuario_id]);
    }
}
