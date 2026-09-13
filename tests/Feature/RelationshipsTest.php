<?php

namespace Tests\Feature;

use App\Enum\PeopleBuilding;
use App\Models\Boleto;
use App\Models\Encomenda;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_pode_listar_suas_encomendas(): void
    {
        $usuario = Usuario::factory()->create();
        Encomenda::factory()->create(['id_usuario' => $usuario->id_usuario]);
        Encomenda::factory()->create(['id_usuario' => $usuario->id_usuario]);

        $encomendas = $usuario->encomenda;

        $this->assertCount(2, $encomendas);
        $this->assertTrue($encomendas->every(fn ($e) => $e->id_usuario === $usuario->id_usuario));
    }

    public function test_boleto_retorna_notificador_correto(): void
    {
        $notificador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);
        $morador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);

        $boleto = Boleto::factory()->create([
            'id_morador' => $morador->id_usuario,
            'id_notificador' => $notificador->id_usuario,
        ]);

        $usuarioQueNotificou = $boleto->foiNotificadoPor;

        $this->assertNotNull($usuarioQueNotificou);
        $this->assertEquals($notificador->id_usuario, $usuarioQueNotificou->id_usuario);
        $this->assertNotEquals($morador->id_usuario, $usuarioQueNotificou->id_usuario);
    }

    public function test_encomendas_sao_filtradas_por_usuario_correto(): void
    {
        $usuario1 = Usuario::factory()->create();
        $usuario2 = Usuario::factory()->create();

        Encomenda::factory()->create(['id_usuario' => $usuario1->id_usuario]);
        Encomenda::factory()->create(['id_usuario' => $usuario2->id_usuario]);

        $this->assertCount(1, $usuario1->encomenda);
        $this->assertCount(1, $usuario2->encomenda);
    }
}
