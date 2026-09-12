# Arquitetura

## Padrão em camadas

A aplicação segue consistentemente o padrão **Controller → Service → Repository → Model**,
com injeção de dependência via container do Laravel:

```
Rota (routes/web.php)
   │
   ▼
Controller (app/Http/Controllers)
   │  - valida a requisição (HowToValidate ou regras inline)
   │  - autoriza via Gate::authorize()/Gate::allows()
   │  - trata exceptions de domínio → JsonResponse
   ▼
Service (app/Services)
   │  - regras de negócio (ex.: pet só é criado se vacinado,
   │    mudança nasce "pendente", fluxo de aprovação em duas etapas)
   ▼
Repository (app/Repositories) — Interface + implementação Eloquent
   │  - único ponto de acesso a dados
   │  - traduz exceptions do Eloquent em exceptions de domínio
   ▼
Model (app/Models) — Eloquent, tabelas em MAIÚSCULO
   │
   ▼
MySQL
```

Não são usadas **Form Requests** dedicadas; a validação é centralizada em
`app/Helpers/HowToValidate.php` (métodos estáticos que retornam arrays de regras do
Laravel Validator) ou feita inline no controller.

Não há uma camada de **Policies** ativa: existe `app/Policies/UserPolicy.php`, mas ela
não está registrada em nenhum `ServiceProvider` nem referenciada via `Gate::policy()` —
a autorização real acontece por **Gates** (closures) definidos em
`app/Providers/AppServiceProvider::boot()`. Ver
[Autenticação e autorização](04-autenticacao-e-autorizacao.md).

## Diretórios principais (`app/`)

| Diretório | Responsabilidade |
|---|---|
| `Models/` | Entidades Eloquent (`Usuario`, `Morador`, `Porteiro`, `Sindico`, `PrestadorDeServico`, `Visitante`, `Pet`, `Encomenda`, `Boleto`, `Mudanca`) |
| `Http/Controllers/` | Camada HTTP: recebe `Request`, delega a um `Service`, devolve `JsonResponse` |
| `Http/Middleware/` | `EnsureRegistrationByAuthorized` (bloqueio por `tipo_usuario`) e `CanRegister` (stub não utilizado) |
| `Services/` | Regras de negócio por domínio (`UserService`, `PetService`, `InvoiceService`, `PackageService`, `MoveService`) |
| `Repositories/` | Um par `*RepositoryInterface` + implementação Eloquent por entidade, todos herdando `BaseRepository` (CRUD genérico) |
| `Helpers/` | `HowToValidate` (regras de validação centralizadas) e `CpfExtractor` (normalização de CPF) |
| `Providers/AppServiceProvider.php` | Bindings de interface → implementação de repositório + definição de todos os Gates |
| `Exceptions/` | Exceptions de domínio: `EntityNotFoundException`, `EntityCreateException`, `EntityUpdateException`, `EntityDeleteException` |
| `Enum/` | `PeopleBuilding`, `AuthorizedEmployees`, `BuildingLocations` (não utilizado), `CanRegister` |
| `Policies/` | `UserPolicy` — presente no código, mas não registrada/ativa (código morto) |
| `Strategy/CadastroStrategy/` | Interface `CadastroUsuarioStrategy` sem implementação concreta (padrão Strategy iniciado, não adotado) |

## Injeção de dependência

`AppServiceProvider::register()` faz o bind de cada interface de repositório à sua
implementação concreta:

```php
$this->app->bind(UserRepositoryInterface::class, UserRepository::class);
$this->app->bind(PetRepositoryInterface::class, PetRepository::class);
$this->app->bind(InvoiceRepositoryInterface::class, InvoiceRepository::class);
$this->app->bind(PackageRepositoryInterface::class, PackageRepository::class);
$this->app->bind(MoveRepositoryInterface::class, MoveRepository::class);
```

Os `Services` dependem apenas das interfaces (nunca da implementação concreta), o que
permite substituir a fonte de dados (ex.: em testes) sem alterar a camada de negócio.

## Tratamento de erros

`BaseRepository` (herdado por todos os repositórios) captura
`ModelNotFoundException` do Eloquent e exceptions genéricas, relançando como
exceptions de domínio:

- `find()`/`update()`/`delete()` → `EntityNotFoundException` (404) quando o registro não existe.
- `create()` → `EntityCreateException` em erro de criação.
- `update()` → `EntityUpdateException` em erro de atualização.
- `delete()` → `EntityDeleteException` em erro de exclusão.

Os Controllers capturam explicitamente `ValidationException`, `AuthorizationException`
e, em parte dos casos, `EntityNotFoundException`/`EntityCreateException`, retornando
JSON com o código HTTP apropriado. Nem todos os controllers capturam
`EntityUpdateException`/`EntityDeleteException` — nesses casos a exception vaza para o
handler padrão do Laravel (erro 500 genérico). Ver detalhes em
[Débitos técnicos](08-debitos-tecnicos-e-limitacoes.md).

## Autenticação

O guard padrão (`config/auth.php`) usa sessão (`driver: session`) com o Eloquent
Provider apontando para o model `Usuario` (tabela `USUARIOS`, não a tabela `users`
padrão do Laravel). O pacote `laravel/sanctum` está instalado (inclusive com a
migration `personal_access_tokens`), mas não há uso ativo de tokens de API — toda a
autenticação hoje é por sessão via `routes/web.php` (`routes/api.php` está vazio).

## Stack tecnológica

- **Backend**: Laravel 12, PHP ^8.2 (imagem Docker usa `php:8.3-cli`).
- **Banco de dados**: MySQL 8.0.
- **Frontend** (previsto na proposta original): Vue.js — não encontrado código de
  frontend implementado neste momento no repositório (`resources/` contém apenas o
  scaffold padrão do Laravel).
- **Padrões de projeto**: Repository, Service Layer, Gate-based authorization.
