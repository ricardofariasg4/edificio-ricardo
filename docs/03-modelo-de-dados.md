# Modelo de dados

> A nomenclatura de tabelas/colunas foi padronizada na issue
> [#12](https://github.com/ricardofariasg4/edificio-ricardo/issues/12) para seguir a
> convenção do Laravel (tabelas em `snake_case` minúsculo, PK `id` autoincremento).
> Ver [Débitos técnicos](08-debitos-tecnicos-e-limitacoes.md) para o histórico da
> mudança e o motivo de `moradores`/`prestadores_de_servico` ainda precisarem de
> `protected $table` explícito.

## Estratégia: herança tabela-por-subtipo

O banco segue um modelo de **herança de tabela** (Class Table Inheritance): `usuarios`
é a tabela-mãe, com `tipo_usuario` como coluna discriminadora
(`sindico|porteiro|morador|prestador|visitante`), e cada subtipo tem sua própria
tabela com um `id` autoincremento próprio e uma coluna `usuario_id` (FK única para
`usuarios.id`, relação 1:1), guardando apenas os atributos específicos daquele papel.

```
usuarios (id PK, nome, email, senha, cpf, idade, tipo_usuario)
   ├── moradores               (id PK, usuario_id FK única, numero_apto)
   ├── porteiros               (id PK, usuario_id FK única, turno_de_trabalho)
   ├── sindicos                (id PK, usuario_id FK única)
   ├── prestadores_de_servico  (id PK, usuario_id FK única, data_ultimo_trabalho)
   └── visitantes              (id PK, usuario_id FK única, visita_de → usuarios.id)
```

Entidades de domínio dependentes:

```
moradores (1) ── (N) pets      [FK id_morador → moradores.usuario_id, restrict via app; cascade no banco]
moradores (1) ── (N) boletos   [FK id_morador → moradores.usuario_id, onDelete: restrict]
moradores (1) ── (N) mudancas  [FK id_morador → moradores.usuario_id, onDelete: restrict]
usuarios  (1) ── (N) encomendas [FK id_usuario → usuarios.id]
usuarios  (1) ── (N) boletos    [FK id_notificador → usuarios.id — quem notificou/cadastrou o boleto]
usuarios  (1) ── (N) mudancas   [FK id_autorizador → usuarios.id — quem aprovou/recusou]
```

**Ponto importante:** `id_morador` (em `pets`/`boletos`/`mudancas`) referencia
`moradores.usuario_id` — a coluna única que identifica o usuário por trás do
morador — e **não** o `id` autoincremento próprio de `moradores`. Isso preserva o
comportamento anterior à padronização, quando a PK de `moradores` era o próprio
`id_usuario` compartilhado com `usuarios`.

## Tabelas e colunas

### `usuarios`
| Coluna | Tipo | Observação |
|---|---|---|
| `id` | unsigned bigint, PK, auto-increment | |
| `email` | string, unique | |
| `senha` | string | hash bcrypt (cast `hashed` no model) |
| `nome` | varchar(100) | |
| `cpf` | char(11), unique | normalizado sem máscara via `CpfExtractor` |
| `idade` | tinyint unsigned, nullable | oculto na serialização (`$hidden`) |
| `tipo_usuario` | enum(`sindico,porteiro,morador,prestador,visitante`), nullable | discriminador de papel |
| `email_verified_at`, `remember_token`, timestamps | — | padrão Laravel |

### `moradores`
`id` (PK, auto-increment próprio), `usuario_id` (FK única → `usuarios.id`, cascade),
`numero_apto` (smallint unsigned).

### `porteiros`
`id` (PK, auto-increment próprio), `usuario_id` (FK única → `usuarios.id`, cascade),
`turno_de_trabalho` (char(1)).

### `sindicos`
`id` (PK, auto-increment próprio), `usuario_id` (FK única → `usuarios.id`, cascade).

### `prestadores_de_servico`
`id` (PK, auto-increment próprio), `usuario_id` (FK única → `usuarios.id`, cascade),
`data_ultimo_trabalho` (datetime, nullable).

### `visitantes`
`id` (PK, auto-increment próprio), `usuario_id` (FK única → `usuarios.id`, cascade),
`visita_de` (FK → `usuarios.id`, cascade) — identifica de qual morador/usuário o
visitante é convidado.

### `pets`
`id` (PK), `nome` (nullable), `peso` (tinyint unsigned, nullable), `vacinado`
(boolean, nullable), `cpf` (char(11), nullable, unique — tutor do pet), `id_morador`
(FK → `moradores.usuario_id`, cascade).

### `encomendas`
`id` (PK), `codigo_rastreio` (varchar(45), indexado), `data_recebimento`
(datetime), `id_usuario` (FK → `usuarios.id`, cascade — destinatário).

### `boletos`
`id` (PK), `id_morador` (FK → `moradores.usuario_id`, **restrict** — impede apagar
morador com boleto vinculado no nível de banco), `status_pagamento` (tinyint
unsigned, nullable — `0` = não pago, `1` = pago), `vencimento` (date, nullable),
`valor` (decimal(10,2) unsigned, nullable), `id_notificador` (FK → `usuarios.id`,
sem ação em cascata).

### `mudancas`
`id` (PK), `data` (datetime, nullable), `status` (enum, nullable — ver evolução
abaixo), `observacao` (text, nullable — motivo da recusa), `id_morador` (FK →
`moradores.usuario_id`, restrict), `id_autorizador` (FK → `usuarios.id`, nullable,
sem ação em cascata).

**Evolução do enum `status`:**
1. Criação original: `pendente | aprovado | em_andamento | finalizado`.
2. Migration `2026_05_04_000001_add_recusado_to_mudancas_status_enum.php` adiciona
   `recusado` ao enum (`pendente | aprovado | em_andamento | finalizado | recusado`).
   O `down()` primeiro reverte quaisquer linhas `recusado` para `pendente` antes de
   encolher o enum, evitando erro de truncamento no rollback.

### `AMBIENTES` (issue [#9](https://github.com/ricardofariasg4/edificio-ricardo/issues/9))
`id_ambiente` (PK), `nome` (varchar(100)), `descricao` (text, nullable),
`capacidade` (unsigned int, nullable) — catálogo de ambientes comuns do prédio
(salão de festas, churrasqueira, etc.), gerenciado por síndico/admin.

### `RESERVAS` (issue #9)
`id_reserva` (PK), `id_ambiente` (FK → `AMBIENTES.id_ambiente`, cascade),
`id_usuario` (FK → `USUARIOS.id_usuario`, cascade), `data` (date — dia
reservado), `status` (enum: `confirmada`, `fila_espera`, `cancelada`),
`posicao_fila` (unsigned int, nullable — só relevante quando
`status = fila_espera`; menor valor = mais próximo do início da fila).

Não há constraint de unicidade no banco para "uma única `confirmada` por
`id_ambiente`+`data`" (MySQL não suporta índice único parcial de forma simples) —
regra garantida em `ReservaService`, mesmo padrão usado para outras regras de
negócio do projeto (ver [Regras de negócio](05-regras-de-negocio.md#reservas-de-ambientes-reservaservice--issue-9)).

### `notifications`
Tabela genérica do mecanismo `Illuminate\Notifications` do Laravel (issue
[#2](https://github.com/ricardofariasg4/edificio-ricardo/issues/2)): `id` (uuid, PK),
`type` (FQCN da classe de notificação), `notifiable_type`/`notifiable_id`
(polimórfico — hoje sempre `App\Models\Usuario`), `data` (JSON), `read_at`
(nullable), timestamps. Ver
[Infraestrutura e ambiente](07-infraestrutura-e-ambiente.md#notificações).

## Migrations, em ordem cronológica

1. `0001_01_01_000001_create_cache_table.php` / `..._create_jobs_table.php` — tabelas
   padrão de infraestrutura do Laravel (cache, filas).
2. `2025_10_08_021228_create_usuarios_table.php` e demais `create_*_table` — criação
   de todas as tabelas de domínio (uma migration por tabela; nomes de tabela/coluna
   padronizados na issue [#12](https://github.com/ricardofariasg4/edificio-ricardo/issues/12),
   originalmente geradas a partir de um banco existente com
   `kitloong/laravel-migrations-generator`).
3. `2025_10_08_021231_add_foreign_keys_to_*` — um arquivo por tabela, adicionando as
   chaves estrangeiras (separadas da criação da tabela, padrão comum de ferramentas de
   geração de migration a partir de schema existente).
4. `2026_01_07_005757_create_personal_access_tokens_table.php` — tabela do Sanctum
   (instalada, mas sem uso ativo — ver [Infraestrutura](07-infraestrutura-e-ambiente.md)).
5. `2026_05_04_000001_add_recusado_to_mudancas_status_enum.php` — adiciona o status
   `recusado`, parte da issue [#4](https://github.com/ricardofariasg4/edificio-ricardo/issues/4).
6. `2026_09_14_000001_create_notifications_table.php` — tabela do sistema de
   notificações, issue [#2](https://github.com/ricardofariasg4/edificio-ricardo/issues/2).
7. `2026_09_15_000001_create_AMBIENTES_table.php` e
   `2026_09_15_000002_create_RESERVAS_table.php` — catálogo de ambientes comuns e
   reservas, issue [#9](https://github.com/ricardofariasg4/edificio-ricardo/issues/9)
   (FKs já incluídas na própria migration de criação, diferente do padrão
   create+add_foreign_keys separados usado nas tabelas geradas originalmente).
8. `2026_09_14_000001_create_notifications_table.php` — tabela do sistema de
   notificações, issue [#2](https://github.com/ricardofariasg4/edificio-ricardo/issues/2).

## Seeders e dados de exemplo

`database/seeders/EdificioRicardoSeeder.php` popula um ambiente de exemplo: 1 síndico,
3 porteiros, 20 moradores, 5 visitantes, 5 prestadores, 5 pets, 5 mudanças, 5
encomendas e 20 boletos — útil para testes manuais e para gerar diagramas de dados
reais.

## Divergências entre o DER conceitual e a implementação

- A entidade `NOTIFICACAO` do DER original **não foi implementada** como tabela
  própria — a tabela `notifications` (mecanismo nativo do Laravel) cobre esse papel
  desde a issue #2 (ver acima).
- Não existem tabelas para "manutenções prediais" (RF05) nem para "entregas por
  aplicativo" (RF03) — essas notificações (issue #2) são disparadas diretamente por
  quem as registra via API, sem uma entidade de domínio própria persistindo o evento
  em si (só a notificação resultante).
- Não há tabela de agendamento de prestadores de serviço (RF06) além do cadastro do
  próprio prestador como subtipo de usuário.
