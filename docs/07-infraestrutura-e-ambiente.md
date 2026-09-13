# Infraestrutura e ambiente

## Ambiente de desenvolvimento (Docker)

O projeto roda em desenvolvimento via Docker Compose (`docker-compose.yml`, raiz):

- **`app`** — construído a partir de `docker/Dockerfile` (`php:8.3-cli` + extensões
  `pdo_mysql, mbstring, exif, pcntl, bcmath, gd, zip, intl` + Xdebug + Composer 2 +
  Node 22 LTS), roda como usuário não-root `appuser` (UID/GID mapeados via
  `USER_UID`/`USER_GID` para casar com o usuário do host). Expõe as portas `8000`
  (`php artisan serve`) e `5173` (Vite). O comando do container sobe três processos
  concorrentes via `concurrently`: servidor HTTP, `php artisan queue:listen` e Vite.
- **`db`** — MySQL 8.0, com healthcheck via `mysqladmin ping`; a aplicação só sobe
  depois que o banco reporta saudável (`depends_on: condition: service_healthy`).
  Dados persistidos no volume nomeado `db_data`.
- **`docker/entrypoint.sh`** — bootstrap idempotente executado na subida do
  container: instala dependências do Composer se `vendor/` não existir, roda `npm
  install` se `node_modules/` não existir, e copia `.env.example → .env` + gera
  `APP_KEY` se `.env` ainda não existir.
- **`.devcontainer/devcontainer.json`** — integra o VS Code Dev Containers ao mesmo
  `docker-compose.yml`, usando o serviço `app` com workspace em `/var/www/html`
  (permite abrir o projeto diretamente com "Reopen in Container").

## Variáveis de ambiente relevantes (`.env.example`)

| Variável | Valor padrão | Observação |
|---|---|---|
| `DB_CONNECTION` | `mysql` | |
| `DB_DATABASE` | `edificio_ricardo` | |
| `SESSION_DRIVER` | `database` | sessões persistidas no MySQL, não em arquivo/redis |
| `QUEUE_CONNECTION` | `database` | filas via tabela `jobs` |
| `CACHE_STORE` | `database` | |
| `APP_LOCALE` / `APP_FALLBACK_LOCALE` | `en` | inconsistente com o RNF04 (interface em português) — as mensagens de negócio da API já estão em português, mas a config de locale do Laravel permanece em inglês |
| `BROADCAST_CONNECTION` | `log` | sem broadcasting real configurado (ex.: Pusher/Reverb) — relevante para o RF03/RNF03 de notificação em tempo real, ainda não implementados |
| `MAIL_MAILER` | `log` | e-mails apenas logados, sem SMTP configurado |

No `docker-compose.yml`, os valores de banco usados pelo container `app` (`DB_HOST=db`,
usuário `edificio_user`, senha `secret` por padrão) sobrescrevem o `.env` local via
`environment:`, mirando o serviço `db` da própria stack Compose.

## Integrações externas

**Não há integrações externas ativas no momento.** O projeto foi desenhado para
integrar com serviços de notificação de aplicativos de entrega (RF03) e para envio de
notificações em tempo real (RNF03), mas:

- Não existe client HTTP configurado para APIs de terceiros (ifood, rappi,
  Correios/Mercado Livre para rastreio, etc.).
- Não há broadcasting configurado (`BROADCAST_CONNECTION=log`) para eventos em tempo
  real.
- `laravel/sanctum` está instalado (útil para uma futura API consumida por um SPA/app
  mobile), mas nenhuma rota usa tokens Sanctum hoje — a autenticação é 100% por
  sessão.
- `AWS_*` no `.env.example` são apenas o boilerplate padrão do Laravel para o driver
  de storage `s3`; não há uso de S3 configurado no código.

## Logging

Issue [#7](https://github.com/ricardofariasg4/edificio-ricardo/issues/7): a aplicação
não possui (nem precisa, por ora) de um serviço externo de centralização de logs
(Bugsnag, Graylog etc.), então o registro de erros críticos é feito localmente, em
formato consultável:

- **Canal `critical`** (`config/logging.php`): usa o driver `single` do Monolog,
  gravando em `storage/logs/critical.log`, mas com o formatter trocado para
  `Monolog\Formatter\JsonFormatter` via um "tap" (`app/Logging/JsonLineFormatter.php`)
  — cada entrada vira uma linha JSON independente, evitando parsing frágil de texto
  ao ler o arquivo de volta.
- **`Controller::logCriticalAndRespond()`** (`app/Http/Controllers/Controller.php`):
  helper usado pelos catches já existentes nos controllers (Move/Pet/Invoice/Package)
  para registrar o detalhe técnico da exceção (classe, mensagem, arquivo, linha) nesse
  canal e devolver ao cliente **apenas** a mensagem de negócio já curada (ex.: "Boleto
  não encontrado"), sem vazar a mensagem crua da exceção — esse era exatamente o
  problema relatado na issue ("alguns logs críticos são devolvidos para o usuário").
- **Rede de segurança global** (`bootstrap/app.php`, `withExceptions`): qualquer
  exceção que escape sem passar por um `try/catch` de controller (ex.:
  `EntityDeleteException` não capturada em `UserController::destroy`) também é
  registrada no canal `critical` (`reportable`) e, para requisições JSON, nunca
  renderiza mensagem/stack trace crus ao cliente — mesmo com `APP_DEBUG=true` — devolvendo
  uma mensagem genérica (`renderable`). Erros de validação, autorização e exceções
  HTTP "normais" do Symfony (404 de rota etc.) seguem o comportamento padrão do
  Laravel, que já não vaza detalhes sensíveis nesses casos.
- **`GET /logs`** (`LogController` + `LogService`): lê `critical.log`, devolve as
  entradas mais recentes primeiro, paginadas (`?page=`, `?per_page=`). Restrito a
  funcionários autenticados via `EnsureRegistrationByAuthorized`, satisfazendo o
  requisito de autenticação da issue.

Não há rotação/retention automática desse arquivo (driver `single`, não `daily`) —
suficiente para o volume atual do projeto; considerar rotação diária caso o arquivo
cresça muito.

## Testes

- `phpunit.xml` usa SQLite em memória para o ambiente de teste (diferente do MySQL
  usado em desenvolvimento/produção). A imagem `php:8.3-cli` "pura" já traz
  `pdo_sqlite` habilitado por padrão, mas `docker/Dockerfile` (usado em
  desenvolvimento) não instala essa extensão explicitamente — rodar os testes dentro
  do container de desenvolvimento do projeto pode exigir adicioná-la ao Dockerfile.
- Cobertura atual: `tests/Feature/AuthControllerTest.php`, `PetControllerTest.php`,
  `MoveAutoDecisionTest.php`, `LogControllerTest.php` e
  `GlobalExceptionHandlingTest.php`, além dos exemplos padrão do scaffold do Laravel.
  Ainda não há testes para os fluxos de Boletos (`InvoiceController`) e Encomendas
  (`PackageController`), nem para os Gates de usuário (`UserController`).
