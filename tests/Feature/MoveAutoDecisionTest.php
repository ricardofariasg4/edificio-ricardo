<?php

namespace Tests\Feature;

use App\Enum\PeopleBuilding;
use App\Models\Morador;
use App\Models\Mudanca;
use App\Models\Usuario;
use App\Services\MoveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MoveAutoDecisionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function criarMudanca(string $status, Carbon $data, ?int $idAutorizador = null): Mudanca
    {
        return Mudanca::factory()->create([
            'status' => $status,
            'data' => $data,
            'id_morador' => Morador::factory()->create()->usuario_id,
            'id_autorizador' => $idAutorizador,
        ]);
    }

    public function test_mudanca_em_andamento_a_menos_de_24h_e_aprovada_automaticamente(): void
    {
        Carbon::setTestNow('2026-01-10 10:00:00');
        $porteiro = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::PORTEIRO]);
        $mudanca = $this->criarMudanca('em_andamento', Carbon::parse('2026-01-10 20:00:00'), idAutorizador: $porteiro->id);

        $decididas = app(MoveService::class)->autoDecidePendingMoves();

        $this->assertCount(1, $decididas);
        $this->assertDatabaseHas('mudancas', [
            'id' => $mudanca->id,
            'status' => 'aprovado',
            'observacao' => null,
        ]);
    }

    public function test_mudanca_pendente_a_menos_de_24h_e_recusada_automaticamente(): void
    {
        Carbon::setTestNow('2026-01-10 10:00:00');
        $mudanca = $this->criarMudanca('pendente', Carbon::parse('2026-01-10 22:00:00'));

        $decididas = app(MoveService::class)->autoDecidePendingMoves();

        $this->assertCount(1, $decididas);
        $this->assertDatabaseHas('mudancas', [
            'id' => $mudanca->id,
            'status' => 'recusado',
            'observacao' => 'Ausência de aprovação',
        ]);
    }

    public function test_mudanca_pendente_com_mais_de_24h_de_folga_nao_e_alterada(): void
    {
        Carbon::setTestNow('2026-01-10 10:00:00');
        $mudanca = $this->criarMudanca('pendente', Carbon::parse('2026-01-15 10:00:00'));

        $decididas = app(MoveService::class)->autoDecidePendingMoves();

        $this->assertCount(0, $decididas);
        $this->assertDatabaseHas('mudancas', [
            'id' => $mudanca->id,
            'status' => 'pendente',
        ]);
    }

    public function test_mudanca_ja_aprovada_nao_e_reavaliada(): void
    {
        Carbon::setTestNow('2026-01-10 10:00:00');
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $mudanca = $this->criarMudanca('aprovado', Carbon::parse('2026-01-10 12:00:00'), idAutorizador: $sindico->id);

        $decididas = app(MoveService::class)->autoDecidePendingMoves();

        $this->assertCount(0, $decididas);
        $this->assertDatabaseHas('mudancas', [
            'id' => $mudanca->id,
            'status' => 'aprovado',
        ]);
    }

    public function test_mudanca_ja_recusada_nao_e_reavaliada(): void
    {
        Carbon::setTestNow('2026-01-10 10:00:00');
        $mudanca = $this->criarMudanca('recusado', Carbon::parse('2026-01-10 12:00:00'));

        $decididas = app(MoveService::class)->autoDecidePendingMoves();

        $this->assertCount(0, $decididas);
        $this->assertDatabaseHas('mudancas', [
            'id' => $mudanca->id,
            'status' => 'recusado',
        ]);
    }

    public function test_comando_artisan_executa_a_decisao_automatica(): void
    {
        Carbon::setTestNow('2026-01-10 10:00:00');
        $mudanca = $this->criarMudanca('pendente', Carbon::parse('2026-01-10 20:00:00'));

        $this->artisan('moves:auto-decide')
            ->expectsOutputToContain('1 mudança(s) decidida(s) automaticamente.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('mudancas', [
            'id' => $mudanca->id,
            'status' => 'recusado',
        ]);
    }
}
