# Visão geral do negócio

> Base: Projeto de Extensão IV (`PEX.pdf`, raiz do repositório).

## Contexto e motivação

O Edifício Ricardo é um prédio residencial de sete andares na região central de
Curitiba, com áreas comuns (portaria, elevadores, garagem, bicicletário, terraço) e
quatro porteiros em turnos distintos, além de equipe de limpeza e manutenção.

Antes deste projeto, a portaria operava de forma manual (agendas físicas, cadernos,
comunicação verbal/interfone), o que gerava perda de informação, falta de
rastreabilidade e dificuldade de organizar encomendas, visitantes e manutenções.

O levantamento de requisitos foi feito por observação direta e entrevistas
semiestruturadas com os porteiros, de forma iterativa: hipóteses iniciais foram
validadas e refinadas com o retorno da equipe operacional.

## Problemas identificados na coleta de dados

- Controle manual de encomendas, com risco de perda de registro.
- Comunicação ineficiente entre portaria e moradores (entregas e serviços por app).
- Ausência de processo formal para agendar mudanças e prestadores de serviço.
- Falta de cadastro digital estruturado de moradores, visitantes e prestadores.
- Dependência de comunicação verbal/interfone, sem rastreabilidade.

## Requisitos funcionais (RF)

| ID | Requisito | Status na implementação |
|----|-----------|--------------------------|
| RF01 | Cadastro de moradores e visitantes com controle de permissão (apenas síndico/porteiro) | Implementado via `UserController::store` + Gate `register-internal-member` (ver [débitos técnicos](08-debitos-tecnicos-e-limitacoes.md) quanto a um bug de tipos nesse Gate) |
| RF02 | Envio de boletos do condomínio com restrição de acesso (apenas síndico/porteiro) | Implementado via `InvoiceController`/`InvoiceService` |
| RF03 | Notificação de entregas de serviços por aplicativo (ifood, rappi etc.), considerando moradores e visitantes, enviada apenas por porteiros | **Não implementado** — não há controller/tabela dedicada a "entregas por aplicativo" distinta de `ENCOMENDAS` |
| RF04 | Notificação de recebimento de encomendas (Correios, Mercado Livre, transportadoras) | Cadastro implementado (`PackageController`/`ENCOMENDAS`); o disparo da notificação em si está marcado como `TODO` em `PackageService::createPackage` |
| RF05 | Notificação de manutenções prediais programadas | **Não implementado** — não há entidade/rotina para manutenções prediais |
| RF06 | Agendamento de prestadores de serviço, por porteiros e moradores | Modelado no DER (`PRESTADORES_DE_SERVICO`) e nas migrations, mas **sem endpoint de agendamento** dedicado (não existe `AppointmentController`/serviço equivalente) |
| RF07 | Agendamento de mudanças com aprovação do síndico (ou provisória do porteiro) | Implementado por completo — ver `MoveController`/`MoveService` e [regras de negócio](05-regras-de-negocio.md#mudanças-rf07) |

## Requisitos não funcionais (RNF)

| ID | Requisito | Observação |
|----|-----------|------------|
| RNF01 | Alta disponibilidade (24/7) | Não há infraestrutura de alta disponibilidade configurada; ambiente atual é single-container via Docker Compose para desenvolvimento |
| RNF02 | Suporte a até 500 unidades cadastradas | Sem otimizações específicas de volume; MySQL padrão comporta o volume tranquilamente |
| RNF03 | Envio de notificações em até 5 segundos | Não se aplica ainda — não há mecanismo de notificação em tempo real implementado (ver RF03/RF05 acima) |
| RNF04 | Interface em português | Mensagens de erro/negócio da API estão em português; `.env.example` define `APP_LOCALE=en` (inconsistência a ajustar) |
| RNF05 | Autenticação com login e senha criptografada | Implementado: `Auth::attempt` + hash de senha (`senha` com cast `hashed` no model `Usuario`) |

## Papéis de usuário (atores)

O sistema modela cinco tipos de usuário através do campo discriminador
`USUARIOS.tipo_usuario`, com uma tabela específica de extensão por papel
(ver [modelo de dados](03-modelo-de-dados.md)):

- **Síndico** — aprovação definitiva de mudanças, gestão de moradores/boletos/encomendas.
- **Porteiro** — operação do dia a dia: cadastro, aprovação provisória de mudanças,
  registro de boletos/encomendas.
- **Morador** — dono de unidade, agenda mudanças e prestadores, cadastra pets,
  visualiza seus próprios boletos/encomendas/mudanças.
- **Prestador de serviço** — modelado no banco, mas sem fluxo de agendamento
  implementado no backend ainda (RF06 pendente).
- **Visitante** — modelado no banco (vinculado a um morador via `visita_de`), usado
  como referência em notificações, sem fluxo de autenticação próprio implementado.

## Alinhamento com os ODS

O projeto está alinhado ao **ODS 11 — Cidades e Comunidades Sustentáveis**: a
digitalização da portaria contribui para ambientes residenciais mais seguros,
organizados e eficientes, além de reduzir a sobrecarga operacional dos porteiros e
aumentar a rastreabilidade das informações.

## Modelagem inicial (DER e modelo físico)

O modelo conceitual (DER) e o modelo físico inicial foram elaborados com BR Modelo e
MySQL Workbench, respectivamente (ver `PEX.pdf`, seções 7–8). A entidade
`NOTIFICACAO` foi deliberadamente deixada de fora da implementação: o time optou por
usar o mecanismo nativo de notificações do Laravel em vez de uma tabela própria,
mantendo a entidade apenas como registro conceitual na documentação original.

O esquema efetivamente implementado no banco (`database/migrations/`) é descrito em
detalhe em [Modelo de dados](03-modelo-de-dados.md).
