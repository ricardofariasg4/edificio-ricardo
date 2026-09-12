# Modelo de dados

## Estratégia: herança tabela-por-subtipo

O banco segue um modelo de **herança de tabela** (Class Table Inheritance): `USUARIOS`
é a tabela-mãe, com `tipo_usuario` como coluna discriminadora
(`sindico|porteiro|morador|prestador|visitante`), e cada subtipo tem sua própria
tabela cuja chave primária é também chave estrangeira para `USUARIOS.id_usuario`
(relação 1:1), guardando apenas os atributos específicos daquele papel.

```
USUARIOS (id_usuario PK, nome, email, senha, cpf, idade, tipo_usuario)
   ├── MORADORES        (id_usuario PK/FK, numero_apto)
   ├── PORTEIROS        (id_usuario PK/FK, turno_de_trabalho)
   ├── SINDICOS         (id_usuario PK/FK)
   ├── PRESTADORES_DE_SERVICO (id_usuario PK/FK, data_ultimo_trabalho)
   └── VISITANTES       (id_usuario PK/FK, visita_de → USUARIOS.id_usuario)
```

Entidades de domínio dependentes:

```
MORADORES (1) ── (N) PETS            [FK id_morador, restrict via app; cascade no banco]
MORADORES (1) ── (N) BOLETOS         [FK id_morador, onDelete: restrict]
MORADORES (1) ── (N) MUDANCAS        [FK id_morador, onDelete: restrict]
USUARIOS  (1) ── (N) ENCOMENDAS      [FK id_usuario]
USUARIOS  (1) ── (N) BOLETOS         [FK id_notificador — quem notificou/cadastrou o boleto]
USUARIOS  (1) ── (N) MUDANCAS        [FK id_autorizador — quem aprovou/recusou]
```

## Tabelas e colunas

### `USUARIOS`
| Coluna | Tipo | Observação |
|---|---|---|
| `id_usuario` | unsigned int, PK, auto-increment | |
| `email` | string, unique | |
| `senha` | string | hash bcrypt (cast `hashed` no model) |
| `nome` | varchar(100) | |
| `cpf` | char(11), unique | normalizado sem máscara via `CpfExtractor` |
| `idade` | tinyint unsigned, nullable | oculto na serialização (`$hidden`) |
| `tipo_usuario` | enum(`sindico,porteiro,morador,prestador,visitante`), nullable | discriminador de papel |
| `email_verified_at`, `remember_token`, timestamps | — | padrão Laravel |

### `MORADORES`
`id_usuario` (PK/FK → `USUARIOS`, cascade), `numero_apto` (smallint unsigned).

### `PORTEIROS`
`id_usuario` (PK/FK → `USUARIOS`, cascade), `turno_de_trabalho` (char(1)).

### `SINDICOS`
`id_usuario` (PK/FK → `USUARIOS`, cascade).

### `PRESTADORES_DE_SERVICO`
`id_usuario` (PK/FK → `USUARIOS`, cascade), `data_ultimo_trabalho` (datetime, nullable).

### `VISITANTES`
`id_usuario` (PK/FK → `USUARIOS`, cascade), `visita_de` (FK → `USUARIOS.id_usuario`,
cascade) — identifica de qual morador/usuário o visitante é convidado.

### `PETS`
`id_pet` (PK), `nome` (nullable), `peso` (tinyint unsigned, nullable), `vacinado`
(boolean, nullable), `cpf` (char(11), nullable, unique — tutor do pet), `id_morador`
(FK → `MORADORES.id_usuario`, cascade).

### `ENCOMENDAS`
`id_encomenda` (PK), `codigo_rastreio` (varchar(45), indexado), `data_recebimento`
(datetime), `id_usuario` (FK → `USUARIOS.id_usuario`, cascade — destinatário).

### `BOLETOS`
`id_boleto` (PK), `id_morador` (FK → `MORADORES.id_usuario`, **restrict** — impede
apagar morador com boleto vinculado no nível de banco), `status_pagamento` (tinyint
unsigned, nullable — `0` = não pago, `1` = pago), `vencimento` (date, nullable),
`valor` (decimal(10,2) unsigned, nullable), `id_notificador` (FK →
`USUARIOS.id_usuario`, sem ação em cascata).

### `MUDANCAS`
`id_mudanca` (PK), `data` (datetime, nullable), `status` (enum, nullable — ver
evolução abaixo), `observacao` (text, nullable — motivo da recusa), `id_morador` (FK
→ `MORADORES.id_usuario`, restrict), `id_autorizador` (FK →
`USUARIOS.id_usuario`, nullable, sem ação em cascata).

**Evolução do enum `status`:**
1. Criação original: `pendente | aprovado | em_andamento | finalizado`.
2. Migration `2026_05_04_000001_add_recusado_to_mudancas_status_enum.php` adiciona
   `recusado` ao enum (`pendente | aprovado | em_andamento | finalizado | recusado`).
   O `down()` primeiro reverte quaisquer linhas `recusado` para `pendente` antes de
   encolher o enum, evitando erro de truncamento no rollback.

## Migrations, em ordem cronológica

1. `0001_01_01_000001_create_cache_table.php` / `..._create_jobs_table.php` — tabelas
   padrão de infraestrutura do Laravel (cache, filas).
2. `2025_10_08_021228_create_USUARIOS_table.php` e demais `create_*_table` — criação
   de todas as tabelas de domínio (uma migration por tabela, geradas a partir de um
   banco existente com `kitloong/laravel-migrations-generator`).
3. `2025_10_08_021231_add_foreign_keys_to_*` — um arquivo por tabela, adicionando as
   chaves estrangeiras (separadas da criação da tabela, padrão comum de ferramentas de
   geração de migration a partir de schema existente).
4. `2026_01_07_005757_create_personal_access_tokens_table.php` — tabela do Sanctum
   (instalada, mas sem uso ativo — ver [Infraestrutura](07-infraestrutura-e-ambiente.md)).
5. `2026_05_04_000001_add_recusado_to_mudancas_status_enum.php` — adiciona o status
   `recusado`, parte da issue [#4](https://github.com/ricardofariasg4/edificio-ricardo/issues/4).

## Seeders e dados de exemplo

`database/seeders/EdificioRicardoSeeder.php` popula um ambiente de exemplo: 1 síndico,
3 porteiros, 20 moradores, 5 visitantes, 5 prestadores, 5 pets, 5 mudanças, 5
encomendas e 20 boletos — útil para testes manuais e para gerar diagramas de dados
reais.

## Divergências entre o DER conceitual e a implementação

- A entidade `NOTIFICACAO` do DER original **não foi implementada** como tabela — a
  decisão registrada no PEX foi usar o mecanismo nativo de notificações do Laravel.
- Não existem tabelas para "manutenções prediais" (RF05) nem para "entregas por
  aplicativo" (RF03) — esses requisitos ainda não têm modelo de dados correspondente.
- Não há tabela de agendamento de prestadores de serviço (RF06) além do cadastro do
  próprio prestador como subtipo de usuário.
