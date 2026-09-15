<?php

namespace Tests\Feature;

use App\Enum\PeopleBuilding;
use App\Models\Morador;
use App\Models\Pet;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetControllerTest extends TestCase
{
    use RefreshDatabase;

    private function moradorUser(): Usuario
    {
        $morador = Morador::factory()->create();
        return $morador->usuario;
    }

    private function staffUser(PeopleBuilding $tipo): Usuario
    {
        return Usuario::factory()->create(['tipo_usuario' => $tipo]);
    }

    public function test_morador_pode_cadastrar_pet_vacinado_para_si_mesmo(): void
    {
        $morador = $this->moradorUser();

        $response = $this->actingAs($morador)->postJson('/pet', [
            'nome' => 'Rex',
            'peso' => 10,
            'vacinado' => true,
            'id_morador' => $morador->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('pets', ['nome' => 'Rex', 'id_morador' => $morador->id]);
    }

    public function test_cadastro_de_pet_nao_vacinado_e_rejeitado(): void
    {
        $morador = $this->moradorUser();

        $response = $this->actingAs($morador)->postJson('/pet', [
            'nome' => 'Rex',
            'vacinado' => false,
            'id_morador' => $morador->id,
        ]);

        $response->assertStatus(400);
        $this->assertDatabaseMissing('pets', ['nome' => 'Rex']);
    }

    public function test_morador_nao_pode_cadastrar_pet_para_outro_morador(): void
    {
        $morador = $this->moradorUser();
        $outroMorador = $this->moradorUser();

        $response = $this->actingAs($morador)->postJson('/pet', [
            'nome' => 'Rex',
            'vacinado' => true,
            'id_morador' => $outroMorador->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_porteiro_pode_cadastrar_pet_para_qualquer_morador(): void
    {
        $porteiro = $this->staffUser(PeopleBuilding::PORTEIRO);
        $morador = $this->moradorUser();

        $response = $this->actingAs($porteiro)->postJson('/pet', [
            'nome' => 'Rex',
            'vacinado' => true,
            'id_morador' => $morador->id,
        ]);

        $response->assertStatus(201);
    }

    public function test_morador_ve_apenas_seus_proprios_pets_no_index(): void
    {
        $morador = $this->moradorUser();
        $outroMorador = $this->moradorUser();

        Pet::factory()->create(['id_morador' => $morador->id]);
        Pet::factory()->create(['id_morador' => $outroMorador->id]);

        $response = $this->actingAs($morador)->getJson('/pets');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
    }

    public function test_sindico_ve_todos_os_pets_no_index(): void
    {
        $sindico = $this->staffUser(PeopleBuilding::SINDICO);
        Pet::factory()->count(3)->create(['id_morador' => $this->moradorUser()->id]);

        $response = $this->actingAs($sindico)->getJson('/pets');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json());
    }

    public function test_morador_nao_pode_ver_pet_de_outro_morador(): void
    {
        $morador = $this->moradorUser();
        $pet = Pet::factory()->create(['id_morador' => $this->moradorUser()->id]);

        $response = $this->actingAs($morador)->getJson("/pet/{$pet->id}");

        $response->assertStatus(403);
    }

    public function test_ver_pet_inexistente_retorna_404(): void
    {
        $morador = $this->moradorUser();

        $response = $this->actingAs($morador)->getJson('/pet/99999');

        $response->assertStatus(404);
    }

    public function test_nao_e_permitido_remover_vacinacao_do_pet(): void
    {
        $morador = $this->moradorUser();
        $pet = Pet::factory()->create(['id_morador' => $morador->id, 'vacinado' => true]);

        $response = $this->actingAs($morador)->putJson("/pet/{$pet->id}", [
            'vacinado' => false,
        ]);

        $response->assertStatus(400);
        $this->assertDatabaseHas('pets', ['id' => $pet->id, 'vacinado' => true]);
    }

    public function test_dono_pode_atualizar_nome_do_pet(): void
    {
        $morador = $this->moradorUser();
        $pet = Pet::factory()->create(['id_morador' => $morador->id, 'vacinado' => true]);

        $response = $this->actingAs($morador)->putJson("/pet/{$pet->id}", [
            'nome' => 'Novo Nome',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('pets', ['id' => $pet->id, 'nome' => 'Novo Nome']);
    }

    public function test_dono_pode_deletar_seu_pet(): void
    {
        $morador = $this->moradorUser();
        $pet = Pet::factory()->create(['id_morador' => $morador->id]);

        $response = $this->actingAs($morador)->deleteJson("/pet/{$pet->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('pets', ['id' => $pet->id]);
    }

    public function test_morador_nao_pode_deletar_pet_de_outro_morador(): void
    {
        $morador = $this->moradorUser();
        $pet = Pet::factory()->create(['id_morador' => $this->moradorUser()->id]);

        $response = $this->actingAs($morador)->deleteJson("/pet/{$pet->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('pets', ['id' => $pet->id]);
    }
}
