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
- [x] Primeiro commit + push do repositório
- [x] Branch `develop` criada e configurada como default

### Dev A — Documentos (branch `feat/documents`)

- [x] Migration `documents` + Enum `DocumentStatus`
- [x] Model `Document` (scopes, casts, LogsActivity, SoftDeletes) — relations de signatários ficam para o Dev B (ver seam abaixo)
- [x] Factory + states (`draft`, `pending`, `completed`, `cancelled`) — `withSignatories`/`readyToSign` ficam para o Dev B
- [x] `DocumentStorageService` (store/delete via disk s3 + `inlineResponse` para stream) — usamos stream protegido pela policy no lugar de `temporaryUrl` (MinIO interno `minio:9000` não é acessível pelo browser)
- [x] `DocumentService` (create, list paginada/filtrada, update, delete soft)
- [x] `DocumentPolicy` (view, update, delete, send) — `send` por ora só checa dono+draft; falta o "tem ao menos 1 signatário" (Dev B)
- [x] `StoreDocumentRequest` + `UpdateDocumentRequest`
- [x] `DocumentDTO` (fromModel, collection) — `signatoryCount`/`signedCount` default 0 até o Dev B
- [x] `DocumentController` (index, create, store, show, edit, update, destroy, file)
- [x] Tela: `Pages/Documents/Index.tsx` (lista + filtros + paginação)
- [x] Tela: `Pages/Documents/Create.tsx` (upload PDF + progresso)
- [x] Tela: `Pages/Documents/Show.tsx` (preview do PDF via iframe + download + excluir)
- [x] Tela: `Pages/Documents/Edit.tsx` (editar título/descrição em rascunho) — adicionada além do escopo original
- [x] Testes Pest cobrindo CRUD, policy e rota de stream (14 testes)
- [x] Base `Controller` com trait `AuthorizesRequests` (Laravel 11+ removeu do skeleton)

> **Integração Dev A para Dev B.** A branch de Documentos é auto-contida; ao mergear os signatários, o Dev B precisa costurar três pontos (marcados como `TODO(DevB)` no código):
>
> 1. **`app/Models/Document.php`** — adicionar relations `signatories(): HasMany` (com `->orderBy('order')`) e `signatures(): HasMany`.
> 2. **`app/Http/Controllers/DocumentController.php`** — nos métodos `index`/`show`, trocar a query por `withCount(['signatories', 'signatures'])` para os contadores do DTO refletirem valores reais (o DTO já lê `signatories_count`/`signatures_count` com fallback 0; nenhuma mudança no DTO necessária).
> 3. **`database/factories/DocumentFactory.php`** — adicionar os states `withSignatories(int)` e `readyToSign()` usando `Signatory::factory()`.
> 4. **`app/Policies/DocumentPolicy.php`** — no método `send`, somar a checagem `$document->signatories()->exists()`.

### Dev B — Signatários

- [x] Migration `signatories` + Enum `SignatoryStatus`
- [x] Model `Signatory` (relations, scopes `pending`, casts, token uuid)
- [x] Factory + states (`pending`, `signed`, `declined`)
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

- [x] `User::documents()` relation adicionada (junto com o model `Document`)
- [ ] Decidir UX da reordenação de signatários (drag-and-drop vs. setas)
- [ ] Validar limite real de upload de PDF (atual: 20MB) com a banca/orientador
- [ ] `DocumentService::delete` faz soft delete e **preserva** o arquivo no S3; criar rotina de purge (force delete) que limpe o storage quando for descartar de vez
- [x] Exemplos de `activitylog` nas docs corrigidos para a API v5 (`getActivitylogOptions()` + namespaces `Models\Concerns\LogsActivity` / `Support\LogOptions`)
- [ ] Paginação na `Documents/Index.tsx` usa `dangerouslySetInnerHTML` para os labels do Laravel (`«`/`»`); avaliar trocar por componente de paginação tipado se incomodar
- [ ] Verificar schema da tabela `activity_log` (não tem `batch_uuid`; ok para log simples, revisitar se for usar `LogBatch`)

---

## Como atualizar

1. Antes de começar uma tarefa, marque como `[~]` e adicione seu nome entre parênteses se ajudar.
2. Ao concluir, marque `[x]`.
3. Bloqueios viram `[!]` com uma linha logo abaixo explicando o motivo.
4. Itens novos vão para "Tech debt e dúvidas" se forem fora do escopo da semana corrente.
