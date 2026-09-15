# Autenticação e autorização

## Autenticação

- **Guard**: `web` (sessão), definido em `config/auth.php`, com o *provider* Eloquent
  apontando para o model `Usuario` (tabela `usuarios`) — não a tabela `users` padrão.
- **`AuthController`** (`app/Http/Controllers/AuthController.php`):
  - `POST /login` — troca `senha` por `password` internamente e usa `Auth::attempt`;
    regenera a sessão após sucesso.
  - `POST /register` — protegida por `['auth', EnsureRegistrationByAuthorized::class]`
    (só um funcionário autenticado pode cadastrar outro usuário); normaliza o CPF via
    `CpfExtractor` e faz hash da senha. **Não define `tipo_usuario`** no cadastro —
    diferente de `UserController::store`, que exige esse campo (ver
    [Débitos técnicos](08-debitos-tecnicos-e-limitacoes.md)).
  - `GET /logout` — invalida a sessão e regenera o token CSRF.
- Senha é armazenada na coluna `senha` (não `password`), com `getAuthPassword()`
  sobrescrito no model `Usuario` para o Laravel usar essa coluna na autenticação.
- **Sanctum** está instalado (`personal_access_tokens` migration presente), mas não há
  uso ativo de tokens de API hoje — toda autenticação é por sessão.

## Middlewares

### `EnsureRegistrationByAuthorized`
Bloqueia a requisição com `403` se `AuthorizedEmployees::tryFrom($user->tipo_usuario)`
não retornar um caso válido, isto é, se o usuário autenticado não for
`sindico`, `porteiro` ou `admin`. Aplicado a:

- `POST /register`
- Escrita de boletos (`POST/PUT/DELETE /invoice*`)
- Escrita de encomendas (`POST/PUT/DELETE /package*`)
- Decisão de mudanças (`POST /move/{id}/decision`) e listagem de pendentes
  (`GET /moves/pending`)

### `CanRegister` (middleware)
Existe em `app/Http/Middleware/CanRegister.php`, mas é um *no-op* (`return
$next($request)`) e não está anexado a nenhuma rota — parece um esqueleto abandonado
em favor do enum `App\Enum\CanRegister` + Gate `register-internal-member`.

## Gates (`app/Providers/AppServiceProvider::boot()`)

Toda autorização fina (quem pode ver/criar/editar/apagar um recurso específico) é
feita por Gates baseados no campo `tipo_usuario` do usuário autenticado — não há
tabela de papéis/permissões separada.

| Domínio | Gate | Regra |
|---|---|---|
| Usuários | `register-internal-member($targetRole)` | Usa `CanRegister` para checar hierarquia de papéis (ver bug em [Débitos técnicos](08-debitos-tecnicos-e-limitacoes.md)) |
| | `update-internal-member($request)` | Só o próprio usuário pode se atualizar |
| | `view-all-users` | admin/síndico/porteiro |
| | `view-user($targetUserId)` | admin/síndico/porteiro, ou o próprio usuário |
| | `delete-user($targetUserId)` | admin/síndico, exceto o próprio usuário |
| Pets | `view-all-pets` | admin/síndico/porteiro |
| | `view-pet`, `register-pet`, `update-pet`, `delete-pet` | admin/síndico/porteiro, ou morador dono do pet |
| Boletos | `view-all-invoices`, `register-invoice`, `update-invoice`, `delete-invoice` | admin/síndico/porteiro |
| | `view-invoice($invoice)` | admin/síndico/porteiro, ou morador dono do boleto |
| Encomendas | `view-all-packages`, `register-package` | admin/síndico/porteiro |
| | `view-package($package)` | admin/síndico/porteiro, ou destinatário |
| Mudanças | `view-all-moves`, `approve-move` | admin/síndico/porteiro (a distinção entre aprovação definitiva x provisória é feita no `MoveService`, não no Gate) |
| | `view-move($move)` | admin/síndico/porteiro, ou morador dono da mudança |
| | `register-move($idMorador)` | admin/síndico, ou o próprio morador |

> **Atenção:** os Gates acima comparam `$user->tipo_usuario` (string vinda do banco)
> com casos do enum `PeopleBuilding` via `in_array()`. Em PHP 8.1+, essa comparação
> **nunca é verdadeira**, porque um valor string nunca é `==` a uma instância de enum.
> Isso significa que, hoje, nenhum desses Gates reconhece corretamente
> admin/síndico/porteiro como tal — ver detalhes e o caminho de correção em
> [Débitos técnicos e limitações conhecidas](08-debitos-tecnicos-e-limitacoes.md#1-comparação-quebrada-entre-tipo_usuario-string-e-o-enum-peoplebuilding).
> Recomenda-se tratar este ponto com prioridade alta antes de qualquer entrega, pois
> compromete a autorização de toda a API.

## Enums usados no controle de acesso

- **`PeopleBuilding`** (string): `admin, sindico, porteiro, morador, visitante,
  prestador, pet` — usado (com o problema acima) para checar o papel do usuário.
- **`AuthorizedEmployees`** (string): `sindico, porteiro, admin` — usado apenas pelo
  middleware `EnsureRegistrationByAuthorized`, via `tryFrom()` (compara string com
  string, funciona corretamente).
- **`CanRegister`** (int, hierárquico): `sindico=0 < porteiro=1 < morador=2 <
  visitante=prestador=pet=3` — usado pelo Gate `register-internal-member` para decidir
  quem pode cadastrar quem (regra pretendida: só é possível cadastrar um papel
  hierarquicamente "abaixo" do seu). Ver bug de tipo em
  [Débitos técnicos](08-debitos-tecnicos-e-limitacoes.md).
