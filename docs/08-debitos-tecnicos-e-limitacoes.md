# Débitos técnicos e limitações conhecidas

Este documento reúne problemas identificados durante a leitura da codebase atual.
Nenhum deles foi corrigido como parte desta documentação — o objetivo aqui é apenas
registrar e explicar, para priorização futura.

## 1. Comparação quebrada entre `tipo_usuario` (string) e o enum `PeopleBuilding`

> **Corrigido** (issue [#1](https://github.com/ricardofariasg4/edificio-ricardo/issues/1)):
> `Usuario::casts()` agora inclui `'tipo_usuario' => PeopleBuilding::class`, fazendo o
> Eloquent devolver instâncias do enum em vez de string crua — os `in_array()` dos
> Gates voltam a comparar enum-com-enum corretamente. `EnsureRegistrationByAuthorized`
> foi ajustado para `AuthorizedEmployees::tryFrom($user->tipo_usuario->value)`. A
> descrição abaixo é mantida como registro histórico do problema e da análise, já
> coberta por testes (`PetControllerTest`, `AuthControllerTest`) que exercitam
> síndico/porteiro através dos Gates afetados.

**Prioridade sugerida: alta — afeta autorização em praticamente toda a API.**

A grande maioria dos Gates em `app/Providers/AppServiceProvider.php` segue este
padrão:

```php
Gate::define('view-all-moves', function (Usuario $user) {
    return in_array($user->tipo_usuario, [PeopleBuilding::ADMIN, PeopleBuilding::SINDICO, PeopleBuilding::PORTEIRO]);
});
```

`$user->tipo_usuario` é uma **string** vinda diretamente da coluna `ENUM` do MySQL
(o model `Usuario` não tem um `cast` para o enum `PeopleBuilding`). `PeopleBuilding::ADMIN`
é uma **instância de enum** (`string`-backed). Em PHP, comparar uma string com uma
instância de enum via `==` (o que `in_array()` faz por padrão) **nunca é verdadeiro**,
mesmo que os valores "pareçam" iguais.

Confirmado empiricamente com PHP 8.3 (mesma versão usada na imagem Docker do
projeto):

```php
enum PeopleBuilding: string { case ADMIN = "admin"; case SINDICO = "sindico"; }
var_dump("admin" == PeopleBuilding::ADMIN);                              // bool(false)
var_dump(in_array("admin", [PeopleBuilding::ADMIN, PeopleBuilding::SINDICO])); // bool(false)
```

**Impacto:** todo Gate que usa esse padrão (`view-all-users`, `view-user`,
`delete-user`, `view-all-pets`, `view-pet`, `register-pet`, `update-pet`,
`delete-pet`, `view-all-invoices`, `view-invoice`, `register-invoice`,
`update-invoice`, `delete-invoice`, `view-all-packages`, `view-package`,
`register-package`, `view-all-moves`, `view-move`, `register-move`, `approve-move`)
**nunca reconhece corretamente** um admin/síndico/porteiro como tal. Na prática:

- Gates que exigem *apenas* esse papel (ex.: `approve-move`, `register-invoice`)
  sempre retornam `false` — nem síndico nem porteiro conseguem aprovar mudanças ou
  cadastrar boletos hoje, ainda que a regra de negócio pretendida permita.
- Gates que têm um "ou" com outra condição (ex.: `view-move`: admin/síndico/porteiro
  **ou** dono da mudança) ainda funcionam para o dono do recurso, mas nunca liberam
  acesso a funcionários que não sejam o dono.
- O mesmo padrão aparece em `UserService::deleteUserById`
  (`$user->tipo_usuario === PeopleBuilding::MORADOR`) — com o cast do item acima essa
  comparação estrita também passou a funcionar corretamente, então a regra "não
  deletar morador com boleto pendente" (documentada em
  [Regras de negócio](05-regras-de-negocio.md#usuários-userservice)) já é aplicada
  pela camada de serviço (coberta por `GlobalExceptionHandlingTest`).

**Caminho de correção (não aplicado aqui):** trocar `in_array($user->tipo_usuario,
[PeopleBuilding::X, ...])` por comparação de valores escalares, por exemplo
`in_array($user->tipo_usuario, [PeopleBuilding::ADMIN->value, PeopleBuilding::SINDICO->value, ...])`,
ou fazer cast do atributo `tipo_usuario` para o enum no model `Usuario` (`casts()`)
e comparar instância-a-instância (`$user->tipo_usuario === PeopleBuilding::ADMIN`).
Qualquer uma das abordagens deve ser acompanhada de testes automatizados (ver item 5).

O middleware `EnsureRegistrationByAuthorized` **não** tem esse problema, pois usa
`AuthorizedEmployees::tryFrom($user->tipo_usuario)` (compara string com string
internamente).

## 2. `Gate::define('register-internal-member', ...)` provavelmente lança erro em runtime

> **Corrigido** (issue [#2](https://github.com/ricardofariasg4/edificio-ricardo/issues/2)):
> Adicionados métodos `fromPeopleBuilding()` e `fromString()` ao enum `CanRegister`
> para mapear corretamente entre `PeopleBuilding` (string-backed) e `CanRegister`
> (int-backed). Também adicionado `ADMIN = -1` a `CanRegister` para permitir que
> admin registre qualquer tipo de usuário. Testes adicionados em `AuthControllerTest`
> cobrem todos os níveis de permissão (sindico registra porteiro/morador, porteiro
> não registra sindico, morador registra visitante, visitante não registra ninguém).

**Problema original (já resolvido):** `CanRegister` era um enum *backed* por `int`
(`SINDICO=0, PORTEIRO=1, MORADOR=2, VISITANTE=PRESTADOR=PET=3`), mas `$user->tipo_usuario`
era uma `string` ou instância de `PeopleBuilding`. `CanRegister::from()` esperava um `int`,
causando erro de tipo em runtime que quebraria o cadastro de novos usuários.

## 3. Relacionamentos com chave estrangeira incorreta em `app/Models/`

> **Corrigido** (issue [#3](https://github.com/ricardofariasg4/edificio-ricardo/issues/3)):
> - `Usuario::encomenda()` agora usa `hasMany(Encomenda::class, 'id_usuario')`
> - `Boleto::foiNotificadoPor()` agora usa `belongsTo(Usuario::class, 'id_notificador')`
> Testes adicionados em `RelationshipsTest` validam que os relacionamentos retornam
> os registros corretos.

**Problema original (já resolvido):**
- `Usuario::encomenda()` usava `'id_entregador'` (coluna inexistente), causando erro SQL
- `Boleto::foiNotificadoPor()` usava `'id_usuario'` (retornava morador em vez de notificador)

## 4. Código morto / incompleto

> **Corrigido** (issue [#4](https://github.com/ricardofariasg4/edificio-ricardo/issues/4)):
> Removidos os seguintes arquivos não utilizados:
> - `app/Policies/UserPolicy.php` (duplicava Gate `register-internal-member` já corrigido)
> - `app/Http/Middleware/CanRegister.php` (middleware no-op não anexado a rotas)
> - `app/Strategy/CadastroStrategy/CadastroStrategy.php` (interface não implementada)
> - `App\Enum\BuildingLocations.php` (enum não referenciado)

**Problema original (parcialmente resolvido):**
- Código morto removido conforme acima
- `AuthController::register` **já define** `tipo_usuario` corretamente (linha 39),
  alinhado com `UserController::store` — descrição da doc estava desatualizada

## 5. Cobertura de testes ainda incompleta

> **Consideravelmente melhorada**: existem agora testes de Feature para:
> - Autenticação/registro (`AuthControllerTest` — 7 testes)
> - Gates e regras de `PetService` (`PetControllerTest`)
> - Decisão automática de mudanças (`MoveAutoDecisionTest`)
> - Sistema de logging (`LogControllerTest`)
> - Rede de segurança de exceções (`GlobalExceptionHandlingTest`)
> - Relacionamentos de modelo (`RelationshipsTest` — 3 testes)
> - **Novo:** Fluxo de mudanças e recusas (`MoveControllerTest` — 6 testes)
> - **Novo:** Endpoints de boletos (`InvoiceControllerTest` — 8 testes)
> - **Novo:** Endpoints de encomendas (`PackageControllerTest` — 8 testes)
> - **Novo:** Gates e fluxos de usuários (`UserControllerTest` — 13 testes)

**Total de testes de Feature:** 68+ testes cobrindo:
- Todos os endpoints de CRUD (criar, ler, atualizar, deletar)
- Autorização por papel (sindico, porteiro, morador, visitante)
- Regras de negócio críticas (notificador automático, boletos pendentes, etc)
- Casos de sucesso e erro

## 6. Requisitos funcionais do PEX ainda sem implementação

Ver tabela completa em [Visão geral do negócio](01-visao-geral-do-negocio.md#requisitos-funcionais-rf):
RF03 (notificação de entregas por aplicativo), RF05 (notificação de manutenções
prediais) e RF06 (agendamento de prestadores de serviço) ainda não têm modelo de
dados nem endpoints correspondentes. RF04 (notificação de encomendas) tem o cadastro
implementado, mas o disparo da notificação em si está marcado como `TODO` no código.

## 7. Inconsistência de locale

`.env.example` define `APP_LOCALE=en`/`APP_FALLBACK_LOCALE=en`, enquanto o RNF04 pede
interface em português e todas as mensagens de negócio da API já estão em português
(hardcoded nos controllers/services, não via arquivos de tradução do Laravel). Não é
um bug funcional, mas vale alinhar a configuração ao restante do projeto.
