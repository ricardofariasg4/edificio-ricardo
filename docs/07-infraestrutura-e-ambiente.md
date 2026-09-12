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

## Testes

- `phpunit.xml` usa SQLite em memória para o ambiente de teste (diferente do MySQL
  usado em desenvolvimento/produção).
- Cobertura atual: apenas os testes de exemplo gerados pelo scaffold do Laravel
  (`tests/Feature/ExampleTest.php` — checa que `GET /` retorna 200 —, e
  `tests/Unit/ExampleTest.php` — asserção trivial). **Não há testes automatizados**
  cobrindo Controllers, Services, Repositories, Gates ou o fluxo de mudanças. Ver
  [Débitos técnicos](08-debitos-tecnicos-e-limitacoes.md#ausência-de-testes-automatizados).
