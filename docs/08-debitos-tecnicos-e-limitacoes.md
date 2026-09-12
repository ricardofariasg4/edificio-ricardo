# Débitos técnicos e limitações conhecidas

Este documento reúne problemas identificados durante a leitura da codebase atual.
Nenhum deles foi corrigido como parte desta documentação — o objetivo aqui é apenas
registrar e explicar, para priorização futura.

## 1. Comparação quebrada entre `tipo_usuario` (string) e o enum `PeopleBuilding`

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
  (`$user->tipo_usuario === PeopleBuilding::MORADOR`, comparação **estrita**, também
  sempre falsa) — isso significa que a regra "não deletar morador com boleto
  pendente" (documentada em [Regras de negócio](05-regras-de-negocio.md#usuários-userservice))
  **não está sendo aplicada** pela camada de serviço; quem acaba barrando a exclusão
  hoje é só a constraint `ON DELETE RESTRICT` do banco, que gera um erro de SQL não
  tratado em vez da mensagem de negócio pretendida.

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

## 5. Ausência de testes automatizados

`tests/Feature/ExampleTest.php` e `tests/Unit/ExampleTest.php` são apenas o scaffold
padrão do Laravel (checagem de `GET /` e uma asserção trivial). **Não há nenhum teste**
cobrindo:

- Os Gates de autorização (o que teria capturado o bug do item 1 antes de chegar em
  produção).
- O fluxo de decisão de mudanças (`MoveService::makeDecision` — aprovação definitiva x
  provisória, obrigatoriedade de observação na recusa).
- As regras de negócio de `PetService` (vacinação obrigatória e irreversível) e
  `UserService` (bloqueio de exclusão de morador com boleto pendente).
- Os endpoints REST em si (validação, códigos de status HTTP retornados).

Recomenda-se priorizar testes de Feature para os Gates (item 1) e para o fluxo de
mudanças, dado que são os pontos com maior risco de regressão silenciosa.

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
