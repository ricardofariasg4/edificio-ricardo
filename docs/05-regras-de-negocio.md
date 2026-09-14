# Regras de negócio

Regras aplicadas na camada `Services`, após validação e autorização do Controller.

## Usuários (`UserService`)

- **Exclusão bloqueada por inadimplência**: ao deletar um usuário do tipo `morador`,
  o serviço verifica se há boletos com `status_pagamento = 0` (constante
  `UserService::UNPAID_INVOICE_STATUS`); se houver, lança `EntityDeleteException` e
  impede a exclusão. Caso não haja pendência, os boletos do morador são apagados antes
  do próprio usuário.
- No nível de banco, a FK `BOLETOS.id_morador → MORADORES.id_usuario` é `ON DELETE
  RESTRICT`, então mesmo com o bug acima o banco impede a exclusão física de um
  morador com boletos vinculados (a diferença é que o erro chega como exceção de SQL
  não tratada, e não como a mensagem de negócio amigável pretendida).

## Pets (`PetService`)

- **Cadastro exige vacinação**: `createPet` só persiste o pet se `vacinado === true`;
  caso contrário lança `EntityCreateException('Pet', 'O pet deve estar vacinado para
  ser cadastrado.')`.
- **Vacinação é irreversível no sistema**: `updatePet` impede que `vacinado` seja
  setado para `false` numa atualização — uma vez vacinado, o pet não pode ser marcado
  como não vacinado novamente via API.

## Boletos (`InvoiceService`)

- **Notificador automático**: ao criar um boleto, `id_notificador` é preenchido
  automaticamente com o usuário autenticado (`Auth::id()`) — não é um campo informado
  pelo cliente da API.
- Consulta por morador (`getInvoicesByMorador`) é usada pelo `InvoiceController` para
  moradores verem apenas os próprios boletos (a listagem completa é restrita a
  admin/síndico/porteiro via Gate `view-all-invoices`).

## Encomendas (`PackageService`)

- Cadastro simples de encomenda (`codigo_rastreio`, `data_recebimento`, destinatário).
- **Notificação automática (RF04)**: `createPackage` dispara `PackageArrivedNotification`
  para o `id_usuario` destinatário logo após persistir a encomenda (issue
  [#2](https://github.com/ricardofariasg4/edificio-ricardo/issues/2)).

## Notificações (`NotificationService`) — issue #2

Usa o mecanismo nativo `Illuminate\Notifications` do Laravel (canal `database`,
síncrono — sem fila). `Usuario` já usa o trait `Notifiable`.

- **RF03 — entrega por aplicativo**: `notifyDelivery` envia `DeliveryNotification`
  a um único destinatário (morador/visitante). Disparado por
  `POST /notifications/delivery`, restrito ao Gate `send-delivery-notification`
  (apenas porteiro/admin, conforme o requisito original).
- **RF04 — encomenda recebida**: `notifyPackageArrived` envia
  `PackageArrivedNotification` ao destinatário da encomenda; chamado
  automaticamente por `PackageService::createPackage` (ver acima).
- **RF05 — manutenção predial programada**: `notifyMaintenanceScheduled` envia
  `MaintenanceScheduledNotification` para **todos** os usuários com
  `tipo_usuario = morador`. Disparado por `POST /notifications/maintenance`,
  restrito ao Gate `send-maintenance-notification` (síndico/porteiro/admin).
- **RF-Extra-1 — mudança aguardando aprovação**: `notifyMoveApprovalRequired` envia
  `MoveApprovalRequiredNotification` para **todos** os usuários com
  `tipo_usuario` em `sindico`/`porteiro`; chamado automaticamente por
  `MoveService::createMove` sempre que uma mudança é agendada.
- **Consulta**: `GET /notifications` lista as notificações do usuário autenticado
  (mais recentes primeiro, paginadas, com contagem de não lidas em `meta.unread_count`);
  `POST /notification/{id}/read` marca uma notificação específica como lida. Ambos
  exigem apenas sessão autenticada — cada usuário só enxerga as próprias
  notificações (`$user->notifications()`, escopado pelo relacionamento polimórfico).

## Mudanças (`MoveService`) — RF07

Fluxo central do sistema, cobrindo o requisito RF07 do levantamento original.

### Criação
`createMove` força `status = 'pendente'` na criação, **independente do valor enviado
no payload** — uma mudança nunca nasce já aprovada. Em seguida (RF-Extra-1, issue
[#2](https://github.com/ricardofariasg4/edificio-ricardo/issues/2)), notifica todos
os síndicos e porteiros de que há uma mudança aguardando decisão — ver
[Notificações](#notificações-notificationservice--issue-2).

### Decisão (`makeDecision`)

Chamado a partir de `POST /move/{id}/decision`, com `decision ∈ {aprovado, recusado}`
e, quando recusada, `observacao` obrigatória (validada tanto por
`HowToValidate::getMoveDecisionRules()` — `required_if:decision,recusado` — quanto por
uma checagem manual de string vazia no controller).

Regra de duas etapas de aprovação:

| Decisão | Quem decide | Status resultante |
|---|---|---|
| `aprovado` | Síndico ou Admin | `aprovado` (aprovação **definitiva**) |
| `aprovado` | Porteiro | `em_andamento` (aprovação **provisória**, aguardando ratificação do síndico) |
| `recusado` | Qualquer autorizado (Gate `approve-move`: admin/síndico/porteiro) | `recusado`, com `observacao` persistida |

Em qualquer decisão, `id_autorizador` é atualizado para o `id_usuario` de quem tomou a
decisão. Ao recusar, a `observacao` é normalizada (`trim`) antes de salvar; ao
aprovar, `observacao` é sempre gravada como `null` (o campo é exclusivo do fluxo de
recusa).

### Listagem de mudanças pendentes — `GET /moves/pending`
Restrita pelo Gate `approve-move` (admin/síndico/porteiro); retorna todas as mudanças
com `status = 'pendente'`.

### Listagem de mudanças recusadas — `GET /moves/rejected` (issue #4)
- Se o usuário autenticado passa no Gate `view-all-moves` (admin/síndico/porteiro),
  recebe **todas** as mudanças com `status = 'recusado'` **e** `observacao` não nula
  e não vazia (`whereNotNull` + `TRIM(observacao) <> ''`).
- Caso contrário (morador), recebe apenas as suas próprias mudanças recusadas com
  observação preenchida (`findRejectedByMorador`).
- O filtro por observação preenchida existe para garantir que só apareçam mudanças
  recusadas com um motivo registrado, coerente com o objetivo da issue: permitir ao
  morador entender *por que* sua mudança foi recusada.

### Edição/exclusão direta
`update` e `destroy` do `MoveController` **não são implementados** — retornam
`501 Not Implemented`, com a mensagem indicando que o fluxo de decisão deve ser usado.
Isso é intencional: uma mudança não deve ser editada/apagada diretamente, apenas
avançar pelo fluxo pendente → decisão.

### Decisão automática por ausência de aprovação (`autoDecidePendingMoves`) — issue #5

Cobre o caso do síndico não decidir a tempo. Executado pelo comando
`php artisan moves:auto-decide` (agendado de hora em hora via `Schedule::command`
em `routes/console.php` — depende de um cron rodando `schedule:run` no ambiente,
o que ainda não está configurado no `docker-compose.yml`), avalia toda mudança com
`data` a 24h ou menos do acontecimento que ainda esteja `pendente` ou `em_andamento`:

| Status antes | Condição | Status depois |
|---|---|---|
| `em_andamento` (aprovação provisória do porteiro) | sem ratificação do síndico até 24h antes | `aprovado` (ratificação automática) |
| `pendente` (nenhuma aprovação) | sem nenhuma decisão até 24h antes | `recusado`, com `observacao = 'Ausência de aprovação'` |

Mudanças já `aprovado`/`recusado`, ou com `data` a mais de 24h de distância, não são
tocadas.

## Reservas de ambientes (`ReservaService`) — issue #9

Catálogo de ambientes comuns (`AmbienteService`/`AmbienteController`, CRUD
restrito ao Gate `manage-ambientes` — síndico/admin) e reservas desses ambientes
por data, com fila de espera automática.

- **Consulta de disponibilidade**: `GET /ambiente/{id}/disponibilidade` (qualquer
  autenticado) retorna as datas com reserva `confirmada` daquele ambiente
  (opcionalmente filtradas por `?mes=YYYY-MM`), para o cliente calcular as datas
  livres por exclusão.
- **Solicitação de reserva** (`createReservation`, `POST /reserva`): se não houver
  reserva `confirmada` para aquele `id_ambiente`+`data`, a nova reserva já nasce
  `confirmada`. Se já houver, a nova reserva entra na fila de espera
  (`status=fila_espera`) com `posicao_fila` sequencial (1, 2, 3...). Um mesmo
  usuário não pode ter duas solicitações ativas (`confirmada` ou `fila_espera`)
  para o mesmo ambiente+data — a segunda tentativa é rejeitada com
  `EntityCreateException`.
- **Cancelamento e promoção automática da fila** (`cancelReservation`,
  `DELETE /reserva/{id}`, Gate `cancel-reserva`: dono da reserva ou
  admin/síndico/porteiro): a reserva é marcada `cancelada`. Se ela estava
  `confirmada`, o próximo da fila para aquele ambiente+data (menor
  `posicao_fila`) é promovido automaticamente para `confirmada`
  (`posicao_fila` volta a `null`) e recebe uma notificação
  (`ReservationPromotedNotification`, reaproveitando o sistema de notificações
  da issue [#2](https://github.com/ricardofariasg4/edificio-ricardo/issues/2)).
  Se não houver ninguém na fila, o cancelamento não tem efeito colateral.
- **Listagem** (`GET /reservas`): morador vê apenas as próprias reservas;
  admin/síndico/porteiro veem todas (Gate `view-all-reservas`).
