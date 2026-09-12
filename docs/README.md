# Documentação — Edifício Ricardo

Sistema de portaria digital para o Edifício Ricardo (Curitiba), desenvolvido como
Projeto de Extensão do curso de Tecnologia em Análise e Desenvolvimento de Sistemas.
Backend em Laravel 12 / PHP 8.2+, seguindo o padrão Controller → Service → Repository → Model,
com banco de dados MySQL.

Esta pasta reúne a documentação técnica do projeto, complementando o levantamento de
requisitos original (`PEX.pdf`, na raiz do repositório) com o estado real do código.

## Índice

1. [Visão geral do negócio](01-visao-geral-do-negocio.md) — origem do projeto, organização,
   requisitos funcionais e não funcionais, alinhamento com os ODS.
2. [Arquitetura](02-arquitetura.md) — camadas da aplicação, injeção de dependência,
   fluxo de uma requisição.
3. [Modelo de dados](03-modelo-de-dados.md) — entidades, tabelas, relacionamentos e migrations.
4. [Autenticação e autorização](04-autenticacao-e-autorizacao.md) — guard de sessão,
   middlewares e Gates.
5. [Regras de negócio](05-regras-de-negocio.md) — regras por domínio (usuários, pets,
   boletos, encomendas, mudanças).
6. [Endpoints da API](06-api-endpoints.md) — tabela de rotas HTTP expostas.
7. [Infraestrutura e ambiente](07-infraestrutura-e-ambiente.md) — Docker, devcontainer,
   variáveis de ambiente, integrações externas (e a ausência delas hoje).
8. [Débitos técnicos e limitações conhecidas](08-debitos-tecnicos-e-limitacoes.md) —
   bugs identificados, código morto e lacunas de teste, para priorização futura.

> A documentação reflete o estado do código no branch `main` em 2026-09-12
> (após o merge do endpoint de listagem de mudanças recusadas, issue #4).
