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

    public function test_sindico_pode_registrar_porteiro(): void
    {
        $sindico = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::SINDICO]);

        $response = $this->actingAs($sindico)->postJson('/register', [
            'nome' => 'Novo Porteiro',
            'email' => 'novo.porteiro@example.com',
            'senha' => 'password123',
            'cpf' => '12345678905',
            'tipo_usuario' => 'porteiro',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('USUARIOS', [
            'email' => 'novo.porteiro@example.com',
            'tipo_usuario' => 'porteiro',
        ]);
    }

    public function test_qualquer_funcionario_pode_registrar_via_endpoint_register(): void
    {
        // A rota /register (AuthController::register) é protegida apenas pelo
        // middleware EnsureRegistrationByAuthorized (checa se o ator é
        // funcionário), sem validar a hierarquia de CanRegister — diferente
        // de POST /user (UserController::store), que usa o Gate
        // register-internal-member. Por isso um porteiro registra um síndico
        // com sucesso por essa rota.
        $porteiro = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::PORTEIRO]);

        $response = $this->actingAs($porteiro)->postJson('/register', [
            'nome' => 'Novo Sindico',
            'email' => 'novo.sindico@example.com',
            'senha' => 'password123',
            'cpf' => '12345678906',
            'tipo_usuario' => 'sindico',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('USUARIOS', ['email' => 'novo.sindico@example.com']);
    }

    public function test_porteiro_nao_pode_registrar_sindico_via_endpoint_user(): void
    {
        // POST /user (UserController::store) usa o Gate register-internal-member,
        // que valida a hierarquia de CanRegister — diferente de /register (ver
        // teste acima).
        $porteiro = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::PORTEIRO]);

        $response = $this->actingAs($porteiro)->postJson('/user', [
            'nome' => 'Novo Sindico',
            'email' => 'novo.sindico.2@example.com',
            'senha' => 'password123',
            'cpf' => '12345678916',
            'idade' => 40,
            'tipo_usuario' => 'sindico',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('USUARIOS', ['email' => 'novo.sindico.2@example.com']);
    }

    public function test_morador_pode_registrar_visitante_via_endpoint_user(): void
    {
        $morador = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::MORADOR]);

        $response = $this->actingAs($morador)->postJson('/user', [
            'nome' => 'Novo Visitante',
            'email' => 'novo.visitante@example.com',
            'senha' => 'password123',
            'cpf' => '12345678907',
            'idade' => 30,
            'tipo_usuario' => 'visitante',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('USUARIOS', [
            'email' => 'novo.visitante@example.com',
            'tipo_usuario' => 'visitante',
        ]);
    }

    public function test_visitante_nao_pode_registrar_ninguem(): void
    {
        $visitante = Usuario::factory()->create(['tipo_usuario' => PeopleBuilding::VISITANTE]);

        $response = $this->actingAs($visitante)->postJson('/register', [
            'nome' => 'Novo Usuario',
            'email' => 'novo.usuario@example.com',
            'senha' => 'password123',
            'cpf' => '12345678908',
            'tipo_usuario' => 'visitante',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('USUARIOS', ['email' => 'novo.usuario@example.com']);
    }
}
