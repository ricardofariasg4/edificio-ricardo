# Endpoints da API

Todas as rotas estão em `routes/web.php` (`routes/api.php` existe mas está vazio);
respostas são sempre JSON. Todas as rotas abaixo, exceto as marcadas como
"público", exigem sessão autenticada (middleware `auth`).

## Autenticação

| Método | Rota | Controller@action | Middleware extra | Descrição |
|---|---|---|---|---|
| POST | `/login` | `AuthController@login` | — (público) | Autentica por sessão |
| POST | `/register` | `AuthController@register` | `auth`, `EnsureRegistrationByAuthorized` | Cadastra novo usuário (só funcionário autorizado) |
| GET | `/logout` | `AuthController@logout` | `auth` | Encerra a sessão |

## Usuários

| Método | Rota | Controller@action | Autorização (Gate) |
|---|---|---|---|
| GET | `/users` | `UserController@index` | `view-all-users` |
| POST | `/user` | `UserController@store` | `register-internal-member` |
| GET | `/user/{id}` | `UserController@show` | `view-all-users` + `view-user` |
| PUT | `/user/{id}` | `UserController@update` | `update-internal-member` |
| DELETE | `/user/{id}` | `UserController@destroy` | `delete-user` |

## Boletos (invoices)

| Método | Rota | Controller@action | Middleware extra | Gate |
|---|---|---|---|---|
| GET | `/invoices` | `InvoiceController@index` | — | `view-all-invoices` (ou lista as próprias) |
| GET | `/invoice/{id}` | `InvoiceController@show` | — | `view-invoice` |
| POST | `/invoice` | `InvoiceController@store` | `EnsureRegistrationByAuthorized` | `register-invoice` |
| PUT | `/invoice/{id}` | `InvoiceController@update` | `EnsureRegistrationByAuthorized` | `update-invoice` |
| DELETE | `/invoice/{id}` | `InvoiceController@destroy` | `EnsureRegistrationByAuthorized` | `delete-invoice` |

## Encomendas (packages)

| Método | Rota | Controller@action | Middleware extra | Gate |
|---|---|---|---|---|
| GET | `/packages` | `PackageController@index` | — | `view-all-packages` (ou lista as próprias) |
| GET | `/package/{id}` | `PackageController@show` | — | `view-package` |
| POST | `/package` | `PackageController@store` | `EnsureRegistrationByAuthorized` | `register-package` |
| PUT | `/package/{id}` | `PackageController@update` | `EnsureRegistrationByAuthorized` | `register-package` |
| DELETE | `/package/{id}` | `PackageController@destroy` | `EnsureRegistrationByAuthorized` | `register-package` |

## Mudanças (moves)

| Método | Rota | Controller@action | Middleware extra | Gate |
|---|---|---|---|---|
| GET | `/moves` | `MoveController@index` | — | `view-all-moves` (ou lista as próprias) |
| GET | `/moves/rejected` | `MoveController@listRejectedMoves` | — | `view-all-moves` (ou lista as próprias recusadas com observação) |
| GET | `/move/{id}` | `MoveController@show` | — | `view-move` |
| POST | `/move` | `MoveController@store` | — | `register-move` |
| PUT | `/move/{id}` | `MoveController@update` | — | não implementado (`501`) |
| DELETE | `/move/{id}` | `MoveController@destroy` | — | não implementado (`501`) |
| POST | `/move/{id}/decision` | `MoveController@makeDecision` | `EnsureRegistrationByAuthorized` | `approve-move` |
| GET | `/moves/pending` | `MoveController@listPendingMoves` | `EnsureRegistrationByAuthorized` | `approve-move` |

> `GET /moves/rejected` é o endpoint entregue na issue
> [#4](https://github.com/ricardofariasg4/edificio-ricardo/issues/4). Regras completas
> em [Regras de negócio](05-regras-de-negocio.md#listagem-de-mudanças-recusadas--getmovesrejected-issue-4).

## Pets

| Método | Rota | Controller@action | Gate |
|---|---|---|---|
| GET | `/pets` | `PetController@index` | `view-all-pets` (ou lista os próprios) |
| GET | `/pet/{id}` | `PetController@show` | `view-pet` |
| POST | `/pet` | `PetController@store` | `register-pet` |
| PUT | `/pet/{id}` | `PetController@update` | `update-pet` |
| DELETE | `/pet/{id}` | `PetController@destroy` | `delete-pet` |

## Logs

| Método | Rota | Controller@action | Middleware extra | Descrição |
|---|---|---|---|---|
| GET | `/logs` | `LogController@index` | `EnsureRegistrationByAuthorized` | Lista os logs críticos registrados localmente (issue #7), paginados (`?page=`, `?per_page=`), mais recentes primeiro |

> Endpoint entregue na issue
> [#7](https://github.com/ricardofariasg4/edificio-ricardo/issues/7). Detalhes do
> mecanismo de logging em
> [Infraestrutura e ambiente](07-infraestrutura-e-ambiente.md#logging).

## Notificações

| Método | Rota | Controller@action | Middleware extra | Gate/Descrição |
|---|---|---|---|---|
| GET | `/notifications` | `NotificationController@index` | — | Lista as notificações do usuário autenticado, paginadas (`?page=`, `?per_page=`), mais recentes primeiro |
| POST | `/notification/{id}/read` | `NotificationController@markAsRead` | — | Marca uma notificação (própria) como lida |
| POST | `/notifications/delivery` | `NotificationController@notifyDelivery` | `EnsureRegistrationByAuthorized` | `send-delivery-notification` (RF03 — apenas porteiro/admin) |
| POST | `/notifications/maintenance` | `NotificationController@notifyMaintenance` | `EnsureRegistrationByAuthorized` | `send-maintenance-notification` (RF05 — síndico/porteiro/admin) |

> Endpoints entregues na issue
> [#2](https://github.com/ricardofariasg4/edificio-ricardo/issues/2). Regras completas
> em [Regras de negócio](05-regras-de-negocio.md#notificações-notificationservice--issue-2).
> `POST /notifications/delivery` e `POST /notifications/maintenance` não persistem
> uma entidade própria — apenas disparam a notificação (`201 Created` na resposta é
> semântico, não indica um recurso consultável por id). RF04 (encomenda) e
> RF-Extra-1 (mudança) são notificados automaticamente por seus respectivos fluxos
> de criação, sem endpoint dedicado.

## Diversos

| Método | Rota | Descrição |
|---|---|---|
| GET | `/` | View `welcome` (scaffold padrão do Laravel) |
| GET | `/ping` | Health-check simples, retorna `"pong"` |
| GET | `/dashboard` | Closure simples (`auth`), placeholder |

## Exemplo — fluxo de mudanças

```
POST /move                     (morador agenda; nasce status=pendente)
GET  /moves/pending             (porteiro/síndico veem a fila)
POST /move/{id}/decision        (decision=aprovado, por porteiro → status=em_andamento)
POST /move/{id}/decision        (decision=aprovado, por síndico  → status=aprovado)
   -- ou --
POST /move/{id}/decision        (decision=recusado + observacao  → status=recusado)
GET  /moves/rejected             (morador vê o motivo da recusa)
```
