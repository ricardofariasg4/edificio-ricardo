<?php

namespace Tests\Feature;

use App\Enum\PeopleBuilding;
use App\Models\Morador;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogControllerTest extends TestCase
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

    private function moradorUser(): Usuario
    {
        $morador = Morador::factory()->create();
        return $morador->usuario;
    }

    private function staffUser(PeopleBuilding $tipo): Usuario
    {
        return Usuario::factory()->create(['tipo_usuario' => $tipo]);
    }

    private function writeLogLine(array $entry): void
    {
        file_put_contents($this->logPath, json_encode($entry) . "\n", FILE_APPEND);
    }

    public function test_morador_nao_pode_acessar_os_logs(): void
    {
        $morador = $this->moradorUser();

        $response = $this->actingAs($morador)->getJson('/logs');

        $response->assertStatus(403);
    }

    public function test_porteiro_pode_acessar_os_logs(): void
    {
        $porteiro = $this->staffUser(PeopleBuilding::PORTEIRO);

        $response = $this->actingAs($porteiro)->getJson('/logs');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta' => ['total', 'per_page', 'current_page', 'last_page']]);
    }

    public function test_endpoint_retorna_vazio_quando_ainda_nao_ha_log_registrado(): void
    {
        $sindico = $this->staffUser(PeopleBuilding::SINDICO);

        $response = $this->actingAs($sindico)->getJson('/logs');

        $response->assertStatus(200)
            ->assertJson(['data' => [], 'meta' => ['total' => 0]]);
    }

    public function test_endpoint_pagina_e_devolve_as_entradas_mais_recentes_primeiro(): void
    {
        $sindico = $this->staffUser(PeopleBuilding::SINDICO);

        $this->writeLogLine(['message' => 'primeiro']);
        $this->writeLogLine(['message' => 'segundo']);
        $this->writeLogLine(['message' => 'terceiro']);

        $response = $this->actingAs($sindico)->getJson('/logs?per_page=2&page=1');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 3);
        $response->assertJsonPath('meta.per_page', 2);
        $response->assertJsonPath('meta.last_page', 2);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.message', 'terceiro');
        $response->assertJsonPath('data.1.message', 'segundo');
    }

    public function test_endpoint_de_logs_devolve_a_segunda_pagina(): void
    {
        $sindico = $this->staffUser(PeopleBuilding::SINDICO);

        $this->writeLogLine(['message' => 'primeiro']);
        $this->writeLogLine(['message' => 'segundo']);
        $this->writeLogLine(['message' => 'terceiro']);

        $response = $this->actingAs($sindico)->getJson('/logs?per_page=2&page=2');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.message', 'primeiro');
    }

    public function test_erro_tratado_pelo_controller_nao_vaza_detalhes_e_e_registrado_no_log(): void
    {
        $morador = $this->moradorUser();

        $response = $this->actingAs($morador)->getJson('/pet/999999');

        $response->assertStatus(404);
        $response->assertJsonMissingPath('details');
        $response->assertExactJson(['error' => 'Pet não encontrado']);

        $this->assertFileExists($this->logPath);
        $logged = json_decode(trim(file_get_contents($this->logPath)), true);
        $this->assertSame('App\\Exceptions\\EntityNotFoundException', $logged['context']['exception']);
    }
}
