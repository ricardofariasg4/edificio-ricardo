# Débitos técnicos e limitações conhecidas

Este documento reúne problemas identificados durante a leitura da codebase, muitos
dos quais já foram corrigidos ao longo das issues do backlog (marcados com
**Corrigido** e a issue correspondente). Itens sem essa marcação seguem em aberto —
registrados para priorização futura.

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

> **Bug adicional descoberto ao corrigir o acima:** o primeiro fix (adicionar
> `fromPeopleBuilding()`/`fromString()` mantendo o backing `int`) revelou que
> `CanRegister` nunca poderia ter funcionado: PHP não permite valores duplicados
> entre casos de um enum *backed* (`VISITANTE=PRESTADOR=PET=3` é inválido — lança
> `Error: Duplicate value in enum`). O bug do item acima mascarava esse segundo bug,
> porque o Gate quebrava antes mesmo de instanciar o enum. Corrigido removendo o
> backing type e usando um método `level(): int` (via `match`) para expressar a
> hierarquia, já que PHP permite múltiplos casos de um enum puro mapearem para o
> mesmo "nível" sem restrição.

> **Nota sobre `AuthController::register` vs. `UserController::store`:** só a rota
> `POST /user` (`UserController::store`) valida a hierarquia via `register-internal-member`.
> A rota `POST /register` (`AuthController::register`) é protegida apenas pelo
> middleware `EnsureRegistrationByAuthorized`, que verifica se o ator é funcionário
> (síndico/porteiro/admin) mas **não** valida qual papel específico ele pode
> cadastrar — um porteiro autenticado pode registrar um síndico por essa rota. Não
> corrigido aqui por ser uma inconsistência de design pré-existente entre as duas
> rotas de cadastro (não introduzida pela correção desta issue); documentado e
> coberto por teste (`AuthControllerTest::test_qualquer_funcionario_pode_registrar_via_endpoint_register`)
> para deixar o comportamento explícito.

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

> **Consideravelmente melhorada**: 87 testes de Feature, todos passando (validado
> rodando contra MySQL real via Docker, não só SQLite in-memory), cobrindo:
> - Autenticação/registro (`AuthControllerTest`)
> - Gates e regras de `PetService` (`PetControllerTest`)
> - Decisão automática de mudanças (`MoveAutoDecisionTest`)
> - Sistema de logging (`LogControllerTest`)
> - Rede de segurança de exceções (`GlobalExceptionHandlingTest`)
> - Relacionamentos de modelo (`RelationshipsTest`)
> - Fluxo de mudanças e recusas (`MoveControllerTest`)
> - Endpoints de boletos (`InvoiceControllerTest`)
> - Endpoints de encomendas (`PackageControllerTest`)
> - Gates e fluxos de usuários (`UserControllerTest`)
> - **Novo:** Sistema de notificações (`NotificationControllerTest` — issue #2)

Cobre todos os endpoints de CRUD, autorização por papel, e as regras de negócio
críticas (notificador automático, boletos pendentes, etc). Ainda faltam testes
unitários isolados (a suíte é 100% Feature/integração); e os Gates de Pet/Invoice/Package
citados no item 1 não têm um teste dedicado por Gate — a cobertura é via fluxo HTTP
completo.

## 6. Requisitos funcionais do PEX ainda sem implementação

> **RF03, RF04 e RF05 implementados** (issue [#2](https://github.com/ricardofariasg4/edificio-ricardo/issues/2)):
> ver [Notificações](05-regras-de-negocio.md#notificações-notificationservice--issue-2).
> RF-Extra-1 (notificar síndico/porteiro de mudança pendente) também implementado.

Ver tabela completa em [Visão geral do negócio](01-visao-geral-do-negocio.md#requisitos-funcionais-rf):
**RF06** (agendamento de prestadores de serviço) ainda não tem modelo de dados nem
endpoints correspondentes — é o único RF do levantamento original ainda sem
implementação.

## 7. Inconsistência de locale

> **Corrigido**: `.env.example` alinhado para `APP_LOCALE=pt_BR`/`APP_FALLBACK_LOCALE=pt_BR`/`APP_FAKER_LOCALE=pt_BR`,
> coerente com o RNF04 e com as mensagens de negócio (já em português).

## 8. `$incrementing` ausente nos models de papel (Morador/Sindico/Porteiro/Visitante/PrestadorDeServico)

> **Corrigido** durante a validação da issue [#2](https://github.com/ricardofariasg4/edificio-ricardo/issues/2).

**Prioridade: crítica — quebrava silenciosamente qualquer criação desses registros.**

`MORADORES`, `SINDICOS`, `PORTEIROS`, `VISITANTES` e `PRESTADORES_DE_SERVICO` usam
`id_usuario` como chave primária, mas essa coluna é uma FK manual para
`USUARIOS.id_usuario` — **não** é `AUTO_INCREMENT`. O Eloquent, por padrão, assume
`$incrementing = true` para qualquer model. Ao criar um desses registros
(`Morador::create(['id_usuario' => 42, ...])`), o Eloquent ignorava o valor de
`id_usuario` fornecido e, após o `INSERT`, sobrescrevia o atributo com
`lastInsertId()` — que para uma tabela sem coluna auto-increment o MySQL devolve
`0`. O registro ficava correto no banco (o `INSERT` gravava `42`), mas a instância
em memória retornada por `create()` ficava com `id_usuario = 0`, quebrando qualquer
código que usasse o valor de retorno imediatamente (como as factories de teste:
`Morador::factory()->create()->id_usuario` valia `0`, não o id real).

**Correção aplicada:** `public $incrementing = false;` adicionado aos 5 models.

**Como foi descoberto:** só ficou visível ao rodar a suíte de testes contra MySQL
real (o SQLite in-memory usado por padrão em `phpunit.xml` não expõe o mesmo
comportamento de forma tão direta) — reforça o valor de validar periodicamente
contra o banco de produção real, não só o de teste.

> **Nota (issue #12):** a padronização de nomenclatura do banco (item 12 abaixo)
> substituiu esse esquema por completo — as 5 tabelas de papel ganharam um `id`
> autoincremento próprio e `usuario_id` como FK única, eliminando também a
> necessidade de `$incrementing = false`.

## 9. Tabelas de extensão (moradores/sindicos/porteiros/visitantes/prestadores_de_servico) nunca são criadas ao registrar um usuário

**Prioridade sugerida: crítica — bloqueia o fluxo real de uso de RF01/RF02/RF04/RF06/RF07 para qualquer usuário cadastrado pela API.**

Nem `UserController::store` nem `AuthController::register` criam a linha
correspondente na tabela de papel (`moradores`, `sindicos`, etc.) ao cadastrar um
`Usuario` com `tipo_usuario = morador` (ou síndico/porteiro/visitante/prestador).
Confirmado por busca no código: não há nenhuma chamada a `Morador::create()`,
`Sindico::create()` etc. em `app/Services` ou `app/Http/Controllers`.

**Impacto:** um morador cadastrado via `POST /user` ou `POST /register` **não
consegue** usar a maior parte da API depois, porque praticamente todo endpoint que
recebe `id_morador` valida `exists:moradores,usuario_id` (`HowToValidate::getMoveStoreRules`,
`getInvoiceStoreRules`, `getPetStoreRules`) — a validação falha porque a linha em
`moradores` nunca foi criada. Da mesma forma, `UserService::deleteUserById` chama
`$user->morador()->first()->boletos()`, que quebra com `Error: Call to a member
function boletos() on null` para um morador sem entrada em `moradores` (reproduzido
durante os testes desta sessão antes de usarmos `Morador::factory()->create()`
diretamente, que cria as duas tabelas).

**Por que os testes existentes não pegaram isso antes:** os testes de Feature
sempre usaram os factories dedicados (`Morador::factory()->create()`, que cria
`Usuario` + `Morador` juntos) em vez de passar pelo fluxo real de
`POST /user`/`POST /register` seguido de uso da API como um morador de verdade — o
que mascarou a lacuna.

**Caminho de correção sugerido (não aplicado aqui, fora do escopo desta sessão):**
em `UserService::createNewUser` (e no fluxo equivalente de `AuthController::register`),
após criar o `Usuario`, criar a linha correspondente na tabela de papel com base em
`tipo_usuario` — por exemplo, um `match` que chama `Morador::create(['usuario_id' =>
$user->id, 'numero_apto' => ...])` etc. (nomes de coluna atualizados pela
padronização da issue #12). Isso exigiria também repensar quais campos extras cada
papel precisa no payload de cadastro (`numero_apto` para morador,
`turno_de_trabalho` para porteiro, `visita_de` para visitante, `data_ultimo_trabalho`
para prestador) — hoje nenhum desses campos é aceito pelas rotas de cadastro.

## 10. `UserController::show` bloqueava morador de ver o próprio perfil

> **Corrigido** durante a validação da issue [#2](https://github.com/ricardofariasg4/edificio-ricardo/issues/2).

`show()` chamava `Gate::authorize('view-all-users')` **antes** de
`Gate::authorize('view-user', $id)`. Como `view-all-users` exige ser
admin/síndico/porteiro, um morador levava `403` mesmo pedindo o próprio registro
(`GET /user/{seu-próprio-id}`), apesar de `view-user` já contemplar "é o próprio
usuário OU é funcionário". Corrigido removendo a chamada redundante a
`view-all-users` — `view-user` sozinho já implementa a regra completa. Coberto por
`UserControllerTest::test_morador_ve_a_si_mesmo`.

## 11. Gate `update-internal-member` não permite síndico/admin editar outro usuário

**Não corrigido — comportamento documentado, mas a intenção de negócio não está clara.**

```php
Gate::define('update-internal-member', function (Usuario $user, Request $request) {
    return $user->id === (int) $request->route('id');
});
```

`PUT /user/{id}` só autoriza o próprio dono do registro a editá-lo — nem síndico nem
admin conseguem atualizar dados de outro usuário por essa rota, diferente do padrão
usado em quase todos os outros Gates do sistema (que sempre liberam
admin/síndico/porteiro). Pode ser intencional (só o dono edita seus próprios dados
por segurança) ou pode ser um Gate incompleto faltando o "ou é funcionário". Mantido
como está por não haver requisito explícito no PEX sobre isso; comportamento atual
coberto por teste (`UserControllerTest::test_sindico_nao_pode_atualizar_outro_usuario`)
para deixá-lo explícito e evitar regressão silenciosa caso a intenção real seja
diferente.

## 12. Padronização de nomenclatura do banco (issue #12)

> **Corrigido** (issue [#12](https://github.com/ricardofariasg4/edificio-ricardo/issues/12)).

Todas as tabelas foram renomeadas de `SCREAMING_SNAKE_CASE` para `snake_case`
minúsculo, e as 5 tabelas "regulares" com PK própria (`usuarios`, `boletos`,
`encomendas`, `mudancas`, `pets`) tiveram sua chave primária renomeada de
`id_<entidade>` para `id` — eliminando a necessidade de `protected $table` e
`protected $primaryKey` nesses 5 models.

Nas 5 tabelas de papel (`moradores`, `sindicos`, `porteiros`, `visitantes`,
`prestadores_de_servico`), que usavam `id_usuario` como PK e FK simultaneamente
(herança por tabela), foi adicionado um `id` autoincremento próprio e a antiga
`id_usuario` foi renomeada para `usuario_id` (FK única para `usuarios.id`) — ver
[Modelo de dados](03-modelo-de-dados.md) para o mapeamento completo. Isso elimina
`$primaryKey`/`$incrementing` customizados nesses models, ao custo de uma coluna
`id` redundante (o dado semanticamente relevante continua em `usuario_id`).

**Duas exceções que ainda exigem `protected $table`:** `Morador` (tabela
`moradores`) e `PrestadorDeServico` (tabela `prestadores_de_servico`) — a
pluralização automática do Eloquent usa regras em inglês e erra para essas duas
palavras (`morador` → `moradors`; pluraliza só a última palavra de
`prestador_de_servico` → `prestador_de_servicos`). As outras 8 tabelas
(`usuarios`, `sindicos`, `porteiros`, `visitantes`, `boletos`, `encomendas`,
`mudancas`, `pets`) coincidem com a pluralização automática do Eloquent e não
precisam de nenhuma customização.

Colunas de FK semânticas (`id_morador`, `id_autorizador`, `id_notificador`, e o
`id_usuario` "dono" em `encomendas`) foram mantidas como estão — já funcionavam via
`belongsTo(Model::class, 'coluna')` explícito e não exigiam customização de PK.
`id_morador` (em `boletos`/`pets`/`mudancas`) agora referencia `moradores.usuario_id`
em vez de `moradores.id` (o novo surrogate), preservando os valores/semântica
anteriores à padronização.

Validado rodando a suíte completa (87 testes) contra MySQL real via Docker Compose
após `migrate:fresh`, e manualmente via `tinker` conferindo que as relações
(`$morador->usuario`, `$usuario->morador`, `$morador->boletos`,
`$boleto->pertenceAoMorador`) resolvem os registros corretos.
