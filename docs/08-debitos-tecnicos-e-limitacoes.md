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

```php
Gate::define('register-internal-member', function (Usuario $user, string $targetRole) {
    $userRole = CanRegister::from($user->tipo_usuario);
    return $userRole?->canRegisterInternalMember($targetRole);
});
```

`CanRegister` é um enum *backed* por `int` (`SINDICO=0, PORTEIRO=1, MORADOR=2,
VISITANTE=PRESTADOR=PET=3`), mas `$user->tipo_usuario` é uma `string` (ex.:
`"sindico"`). `CanRegister::from()` espera um valor `int` compatível com o backing
type do enum; passar uma string tende a resultar em erro de tipo em tempo de
execução (`TypeError` ou `ValueError`, dependendo da versão do PHP), quebrando o
cadastro de novos usuários via `UserController::store` (RF01/RF07 de cadastro de
moradores).

## 3. Relacionamentos com chave estrangeira incorreta em `app/Models/`

- `Usuario::encomenda()` usa `hasMany(Encomenda::class, 'id_entregador')`, mas a
  tabela `ENCOMENDAS` só tem a coluna `id_usuario` (não existe `id_entregador`) —
  qualquer chamada a esse relacionamento falha com erro de coluna inexistente no SQL.
- `Boleto::foiNotificadoPor()` usa `belongsTo(Usuario::class, 'id_usuario')`, quando a
  coluna correta de FK para o notificador é `id_notificador` (a própria migration de
  criação de `BOLETOS` usa esse nome). Hoje esse relacionamento retorna sempre o
  próprio morador/dono ao invés do usuário que notificou o boleto.

## 4. Código morto / incompleto

- **`app/Policies/UserPolicy.php`** — duplica a lógica do Gate `register-internal-member`,
  mas não está registrada em nenhum `ServiceProvider` (`Gate::policy()`) nem
  referenciada por nenhum controller. Sugestão: remover, ou migrar o Gate para usar a
  Policy (escolher uma única fonte de verdade).
- **`app/Http/Middleware/CanRegister.php`** — middleware *no-op*, não anexado a
  nenhuma rota.
- **`app/Strategy/CadastroStrategy/CadastroStrategy.php`** — interface
  `CadastroUsuarioStrategy` sem nenhuma implementação concreta nem uso em outro
  arquivo do projeto.
- **`App\Enum\BuildingLocations`** — enum definido (`garagem, vaga, portaria,
  terraco`), mas não referenciado em nenhum outro lugar da aplicação.
- **`AuthController::register`** não define `tipo_usuario` no cadastro, diferente de
  `UserController::store`, que exige o campo — usuários criados pela rota de
  autenticação nascem sem papel definido.

## 5. Cobertura de testes ainda incompleta

> **Parcialmente corrigido**: já existem testes de Feature para autenticação/registro
> (`AuthControllerTest`), Gates e regras de `PetService` (`PetControllerTest`), o
> fluxo de decisão automática de mudanças (`MoveAutoDecisionTest`), o sistema de
> logging (`LogControllerTest`) e a rede de segurança de exceções não tratadas
> (`GlobalExceptionHandlingTest`).

Ainda faltam testes cobrindo:

- `MoveService::makeDecision` (aprovação definitiva x provisória via `POST
  /move/{id}/decision`) e `MoveController::listRejectedMoves`.
- Os endpoints de Boletos (`InvoiceController`/`InvoiceService`) e Encomendas
  (`PackageController`/`PackageService`).
- Os Gates e fluxos de `UserController` (cadastro/atualização/exclusão de usuários),
  incluindo o bug do item 2 acima (`register-internal-member`).

Recomenda-se priorizar testes para o fluxo de decisão de mudanças e para
`register-internal-member`, dado que são os pontos com maior risco de regressão
silenciosa hoje.

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
