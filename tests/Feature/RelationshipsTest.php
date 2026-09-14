<?php

namespace Tests\Feature;

use App\Enum\PeopleBuilding;
use App\Models\Boleto;
use App\Models\Encomenda;
use App\Models\Morador;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_pode_listar_suas_encomendas(): void
    {
        $usuario = Usuario::factory()->create();
        Encomenda::factory()->create(['id_usuario' => $usuario->id]);
        Encomenda::factory()->create(['id_usuario' => $usuario->id]);

        $encomendas = $usuario->encomenda;

        $this->assertCount(2, $encomendas);
        $this->assertTrue($encomendas->every(fn ($e) => $e->id_usuario === $usuario->id));
    }

    public function test_boleto_retorna_notificador_correto(): void
    {
        $notificador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Morador::factory()->create();

        $boleto = Boleto::factory()->create([
            'id_morador' => $morador->usuario_id,
            'id_notificador' => $notificador->id,
        ]);

        $usuarioQueNotificou = $boleto->foiNotificadoPor;

        $this->assertNotNull($usuarioQueNotificou);
        $this->assertEquals($notificador->id, $usuarioQueNotificou->id);
        $this->assertNotEquals($morador->usuario_id, $usuarioQueNotificou->id);
    }

    public function test_encomendas_sao_filtradas_por_usuario_correto(): void
    {
        $usuario1 = Usuario::factory()->create();
        $usuario2 = Usuario::factory()->create();

        Encomenda::factory()->create(['id_usuario' => $usuario1->id]);
        Encomenda::factory()->create(['id_usuario' => $usuario2->id]);

        $this->assertCount(1, $usuario1->encomenda);
        $this->assertCount(1, $usuario2->encomenda);
    }
}
