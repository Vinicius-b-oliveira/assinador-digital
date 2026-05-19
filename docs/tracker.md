# Tracker

Status compartilhado da dupla. Atualizar ao iniciar e finalizar cada tarefa.

Convenção de status: `[ ]` pendente · `[~]` em andamento · `[x]` concluído · `[!]` bloqueado.

---

## Fundação

### Setup (compartilhado)

- [x] Sail + Breeze instalados e cleanup do scaffold
- [x] Tailwind v4 + shadcn/ui configurados
- [x] Pacotes base instalados (activitylog, pdf, resend, flysystem-s3, signature_pad)
- [x] Bucket MinIO criado automaticamente via `minio-init` no compose
- [x] Páginas de Auth/Profile refatoradas para shadcn (pasta `Components/` removida)
- [ ] Primeiro commit + push do repositório
- [ ] Branch `develop` criada e configurada como default
- [ ] Convidar o outro dev no GitHub

### Dev A — Documentos

- [ ] Migration `documents` + Enum `DocumentStatus`
- [ ] Model `Document` (relations, scopes, casts, LogsActivity, SoftDeletes)
- [ ] Factory + states (`draft`, `pending`, `completed`, `cancelled`, `withSignatories`, `readyToSign`)
- [ ] `DocumentStorageService` (upload/temporaryUrl/delete via disk s3)
- [ ] `DocumentService` (create, list, delete)
- [ ] `DocumentPolicy` (view, update, delete, send)
- [ ] `StoreDocumentRequest` + `UpdateDocumentRequest`
- [ ] `DocumentDTO` (fromModel, collection)
- [ ] `DocumentController` (index, create, store, show, destroy)
- [ ] Tela: `Pages/Documents/Index.tsx` (lista + filtros + paginação)
- [ ] Tela: `Pages/Documents/Create.tsx` (upload PDF)
- [ ] Tela: `Pages/Documents/Show.tsx` (visualização do PDF)
- [ ] Testes Pest cobrindo CRUD e policy

### Dev B — Signatários

- [ ] Migration `signatories` + Enum `SignatoryStatus`
- [ ] Model `Signatory` (relations, scopes `pending`, casts, token uuid)
- [ ] Factory + states (`pending`, `signed`, `declined`)
- [ ] `SignatoryService` (add, reorder, remove, gerar token, advanceFlow)
- [ ] `SignatoryPolicy` (manage no dono, enquanto draft)
- [ ] `StoreSignatoryRequest`
- [ ] `SignatoryDTO`
- [ ] `SignatoryController` (store, update, destroy, reorder)
- [ ] Tela: gerenciar signatários (dentro do `Documents/Show.tsx` ou rota dedicada)
- [ ] Testes Pest

---

## Envio e e-mails

### Dev A

- [ ] Geração do PDF final via spatie/laravel-pdf (resumo de assinaturas, IP, timestamp)
- [ ] Endpoint de download do PDF assinado
- [ ] Tela de download/preview do PDF concluído

### Dev B

- [ ] Mailable: `SigningInvitationMail` (convite com link único)
- [ ] Mailable: `DocumentCompletedMail` (notifica o dono)
- [ ] Mailable: `SignatureRecordedMail` (notifica o dono a cada assinatura)
- [ ] Action de envio: gera tokens, marca como `pending`, dispara invitations (queued)
- [ ] Job de lembrete manual (queued, disparado por botão na tela do documento)
- [ ] Testes Pest dos mailables e do fluxo de envio

---

## Assinatura e auditoria

### Dev B (líder; Dev A apoia)

- [ ] Rota pública `/sign/{token}` (sem auth)
- [ ] `SigningController` (show, sign, decline)
- [ ] `SigningService` (validar ordem, registrar assinatura, avançar fluxo)
- [ ] `SignDocumentRequest` (signature_data, aceite)
- [ ] `SignatureDTO`
- [ ] Tela: `Pages/Public/Sign.tsx` (visualização do PDF + canvas + confirmação)
- [ ] Componente `SignaturePad.tsx` (signature_pad lib, suporte mouse/touch)
- [ ] Log de auditoria em cada evento (criação, envio, assinatura, recusa, conclusão)
- [ ] Tela de histórico no `Documents/Show.tsx`
- [ ] Testes Pest: ordem, token inválido, decline, conclusão

---

## Polimento e deploy

### Compartilhado

- [ ] Estados de erro (404, 403, 500, validações)
- [ ] Deploy no Railway: serviço Laravel + Postgres + Procfile para queue worker
- [ ] Cloudflare R2 configurado em produção
- [ ] Resend configurado em produção
- [ ] Domínio + HTTPS
- [ ] Teste de fluxo completo end-to-end em produção

---

## Tech debt e dúvidas

Use esta seção para registrar coisas que apareceram durante o desenvolvimento e que precisam ser endereçadas depois — bugs pequenos, refatoramentos, decisões em aberto.

- [ ] `User::documents()` relation precisa ser adicionada quando o model `Document` existir
- [ ] Decidir UX da reordenação de signatários (drag-and-drop vs. setas)
- [ ] Validar limite real de upload de PDF (atual: 20MB) com a banca/orientador

---

## Como atualizar

1. Antes de começar uma tarefa, marque como `[~]` e adicione seu nome entre parênteses se ajudar.
2. Ao concluir, marque `[x]`.
3. Bloqueios viram `[!]` com uma linha logo abaixo explicando o motivo.
4. Itens novos vão para "Tech debt e dúvidas" se forem fora do escopo da semana corrente.
