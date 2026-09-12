<?php

namespace Tests\Feature;

use App\Enum\PeopleBuilding;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_porteiro_pode_registrar_novo_usuario_com_tipo_usuario_persistido(): void
    {
        $porteiro = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::PORTEIRO]);

        $response = $this->actingAs($porteiro)->postJson('/register', [
            'nome' => 'Novo Morador',
            'email' => 'novo.morador@example.com',
            'senha' => 'password123',
            'cpf' => '12345678901',
            'tipo_usuario' => 'morador',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('USUARIOS', [
            'email' => 'novo.morador@example.com',
            'tipo_usuario' => 'morador',
        ]);
    }

    public function test_registro_sem_tipo_usuario_e_rejeitado(): void
    {
        $porteiro = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::PORTEIRO]);

        $response = $this->actingAs($porteiro)->postJson('/register', [
            'nome' => 'Novo Morador',
            'email' => 'sem.tipo@example.com',
            'senha' => 'password123',
            'cpf' => '12345678902',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('USUARIOS', ['email' => 'sem.tipo@example.com']);
    }

    public function test_registro_com_tipo_usuario_invalido_e_rejeitado(): void
    {
        $porteiro = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::PORTEIRO]);

        $response = $this->actingAs($porteiro)->postJson('/register', [
            'nome' => 'Novo Morador',
            'email' => 'tipo.invalido@example.com',
            'senha' => 'password123',
            'cpf' => '12345678903',
            'tipo_usuario' => 'admin',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('USUARIOS', ['email' => 'tipo.invalido@example.com']);
    }

    public function test_morador_nao_pode_registrar_novo_usuario(): void
    {
        $morador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);

        $response = $this->actingAs($morador)->postJson('/register', [
            'nome' => 'Novo Morador',
            'email' => 'bloqueado@example.com',
            'senha' => 'password123',
            'cpf' => '12345678904',
            'tipo_usuario' => 'morador',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('USUARIOS', ['email' => 'bloqueado@example.com']);
    }
}
