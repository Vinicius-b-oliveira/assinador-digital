# Plano de Projeto — Assinador Digital
> Stack: Laravel 13 · Breeze · Inertia.js · React 19 · TypeScript · Tailwind 4 · shadcn/ui · Laravel Sail (Docker)

---

## 1. Stack Completa

| Camada | Tecnologia |
|---|---|
| Framework backend | Laravel 13 |
| Starter kit | Laravel Breeze (Inertia + React) |
| Bridge frontend/backend | Inertia.js v3 |
| Frontend | React 19 + TypeScript |
| Estilização | Tailwind CSS v4 + shadcn/ui |
| Ambiente de desenvolvimento | Laravel Sail (Docker) |
| Banco de dados | PostgreSQL (dev via Sail e produção) |
| Storage de arquivos | MinIO (dev via Sail) → Cloudflare R2 (produção) |
| Filas (e-mail) | Laravel Queue + database driver |
| Hospedagem | Railway (gratuito) |
| Controle de versão | GitHub |

> **Nota:** O Breeze v2.4 com React traz React 19, TypeScript e Tailwind 4 configurados. **shadcn/ui não vem incluso** — precisa ser inicializado separadamente (ver §11). Também é necessário **atualizar `@vitejs/plugin-react` para `^6`** após o `breeze:install`, porque a versão default (`^4`) só suporta vite 7 e o Laravel 13 instala vite 8.

> **Nota:** Com o Sail, o banco de desenvolvimento já é PostgreSQL — o mesmo banco da produção no Railway. Isso elimina a clássica armadilha de "funciona no SQLite mas quebra em produção".

---

## 2. Instalação Inicial com Sail

### 2.1 Criar o projeto com Sail integrado

```bash
# Criar o projeto já com Sail, PostgreSQL, Mailpit e MinIO
curl -s "https://laravel.build/assinador-digital?with=pgsql,mailpit,minio" | bash

cd assinador-digital
```

> O comando acima cria o projeto e configura o `docker-compose.yml` com os serviços `pgsql`, `mailpit` e `minio`. Redis foi omitido intencionalmente — filas e cache rodam no próprio PostgreSQL via driver `database`, sem necessidade de serviço extra.

> **Cuidado com o `breeze:install`:** o comando dispara `npm install` por baixo, ignorando o pnpm. Em projetos novos, a árvore default do Laravel 13 traz `vite@^8` + `@vitejs/plugin-react@^4.7` (que só suporta até vite 7) e a instalação por npm falha com `ERESOLVE`. Solução: depois de rodar o `breeze:install`, atualizar o `package.json` para usar `@vitejs/plugin-react@^6` (compatível com vite 8), apagar `node_modules/` e rodar `./vendor/bin/sail pnpm install` para gerar o `pnpm-lock.yaml` correto. Também é necessário ajustar `pnpm-workspace.yaml` com `allowBuilds: { esbuild: true }` para o pnpm v11 permitir os build scripts do esbuild.

### 2.2 Instalar o Breeze dentro do container

```bash
# Subir os containers
./vendor/bin/sail up -d

# Instalar o Breeze via Sail (PHP roda dentro do Docker)
./vendor/bin/sail composer require laravel/breeze --dev
./vendor/bin/sail artisan breeze:install

# Quando perguntado, selecionar:
# ✅ Stack: React com Inertia
# ✅ TypeScript: sim
# ✅ Testing: Pest

# Rodar as migrations
./vendor/bin/sail artisan migrate

# Habilitar pnpm via corepack (uma vez por máquina)
./vendor/bin/sail shell
corepack enable
exit

# Instalar dependências JS e subir o Vite
./vendor/bin/sail pnpm install
./vendor/bin/sail pnpm run dev
```

### 2.3 Alias recomendado (qualidade de vida)

Adicionar no `~/.bashrc` ou `~/.zshrc` de cada membro da dupla:

```bash
alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'
```

A partir daí, todos os comandos ficam mais curtos:

```bash
sail up -d           # subir containers
sail down            # parar containers
sail artisan migrate # rodar migrations
sail composer ...    # composer dentro do container
sail pnpm ...        # pnpm dentro do container
```

### 2.4 Serviços disponíveis no ambiente de dev

| Serviço | URL local |
|---|---|
| Aplicação Laravel | http://localhost |
| PostgreSQL | localhost:5432 |
| Mailpit (e-mails) | http://localhost:8025 |
| MinIO (storage) | http://localhost:9000 |
| MinIO Console | http://localhost:9001 |

> O **Mailpit** captura todos os e-mails disparados localmente — útil para testar convites e notificações sem SMTP real. O **MinIO Console** permite visualizar os arquivos uploadados diretamente pelo browser, com as credenciais `sail` / `password`.

---

## 3. Cleanup Inicial

Tanto o Laravel quanto o Breeze geram arquivos de demonstração e boilerplate que não serão usados. Fazer esse cleanup antes de começar a desenvolver evita confusão e mantém o projeto enxuto desde o início.

---

### 3.1 Rotas — `routes/web.php`

O Laravel gera uma rota raiz que renderiza a página de welcome. O Breeze sobrescreve com a sua própria versão. Substituir por rotas reais do projeto:

```php
// ❌ Remover — rota de demonstração do Breeze
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'    => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

// ✅ Substituir por
Route::get('/', fn () => Inertia::render('Welcome'))->name('home');

Route::middleware('auth')->group(function () {
    // rotas autenticadas virão aqui
});
```

O arquivo `routes/api.php` não é carregado por padrão no Laravel 11+. Se não for usar API REST, confirmar que ele não está sendo registrado em `bootstrap/app.php` — e se não estiver, não mexer.

---

### 3.2 Frontend — páginas e componentes do Breeze

**Páginas para substituir:**

```
resources/js/Pages/Welcome.tsx    → reescrever como landing page do projeto
resources/js/Pages/Dashboard.tsx  → reescrever como painel de documentos
```

**Componentes do Breeze: já foram removidos no setup inicial.** Toda a UI (Auth, Profile, Layouts) foi refatorada para usar primitivos do shadcn (`Button`, `Input`, `Label`, `Dialog`, `DropdownMenu`, `Sheet`, `Checkbox`). A única pasta de componentes do projeto é `resources/js/components/` (lowercase), com `components/ui/` para os primitivos.

**O que está pronto e não precisa mexer:**

```
resources/js/Layouts/AuthenticatedLayout.tsx  → topbar com dropdown de usuário + menu mobile (Sheet)
resources/js/Layouts/GuestLayout.tsx          → wrapper para login/registro
resources/js/Pages/Auth/                      → login, registro, reset, confirmação, verificação
resources/js/Pages/Profile/                   → edição de nome/e-mail/senha + exclusão de conta
```

---

### 3.3 Backend — arquivos gerados pelo Laravel

**`app/Models/User.php`** — gerado com o mínimo. Adicionar o que o projeto precisa:

```php
// Ativar verificação de e-mail
class User extends Authenticatable implements MustVerifyEmail
{
    // Adicionar relation com documentos
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
```

**`app/Http/Controllers/Controller.php`** — arquivo base vazio. Manter, não mexer.

**`database/migrations/`** — o Laravel gera migrations de `users`, `cache` e `jobs`. Manter todas — são necessárias para auth, queue e cache via banco.

**`database/seeders/DatabaseSeeder.php`** — gerado com um usuário de exemplo comentado. Limpar e deixar preparado para seeders reais:

```php
// ❌ Remover comentário de exemplo
// User::factory(10)->create();
// User::factory()->create(['name' => 'Test User', ...]);

// ✅ Deixar limpo para adicionar seeders reais depois
public function run(): void
{
    // $this->call([DocumentSeeder::class]);
}
```

**`database/factories/UserFactory.php`** — manter, é usado nos testes.

---

### 3.3.1 Tailwind v4 — configuração em CSS, não em JS

O Tailwind v4 **não usa mais `tailwind.config.js` nem `postcss.config.js`**. Toda configuração vive no CSS:

- Tema, fontes, cores, custom variants: `@theme`, `@theme inline`, `@custom-variant` no `resources/css/app.css`.
- Scan de templates: `@source "../views"`, `@source "../js"`.
- Plugins (forms etc.): `@plugin "@tailwindcss/forms"`.
- Build: feito pelo `@tailwindcss/vite` plugin no `vite.config.js` (sem PostCSS).

Após o `breeze:install` (que pode gerar `tailwind.config.js` e `postcss.config.js` legados):

1. Remover `tailwind.config.js` e `postcss.config.js`.
2. Adicionar `@tailwindcss/vite` ao `vite.config.js`.
3. Substituir o conteúdo de `resources/css/app.css` por `@import "tailwindcss"` + `@theme`/`@source`/`@plugin` necessários.

### 3.4 Arquivos de configuração — limpeza do `.env`

O `.env` gerado tem várias entradas comentadas ou com valores padrão que conflitam com o Sail. Ajustar:

```env
# Identidade
APP_NAME="Assinador Digital"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Banco — Sail usa pgsql
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=assinador_digital
DB_USERNAME=sail
DB_PASSWORD=password

# Filas — banco em vez de sync
QUEUE_CONNECTION=database

# E-mail — Mailpit via Sail
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_FROM_ADDRESS="noreply@assinador.local"
MAIL_FROM_NAME="Assinador Digital"

# Storage — MinIO via Sail
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=sail
AWS_SECRET_ACCESS_KEY=password
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=local
AWS_ENDPOINT=http://minio:9000
AWS_USE_PATH_STYLE_ENDPOINT=true

# Remover ou deixar vazio — não serão usados
REDIS_HOST=     # removido — sem Redis no projeto
CACHE_STORE=database
SESSION_DRIVER=database
```

---

### 3.5 Arquivos que podem ser removidos com segurança

```
README.md                    → substituir por documentação real do projeto
CHANGELOG.md                 → não existe por padrão, mas se criado pelo Breeze, remover
.github/workflows/           → se gerado, revisar — CI/CD pode ser configurado depois
resources/views/welcome.blade.php → não é mais usada com Inertia, pode remover
```

**`bootstrap/app.php`** — não remover, mas revisar se `withRouting()` está registrando `api.php` desnecessariamente:

```php
// Se não for usar API REST, garantir que api.php não está registrado
->withRouting(
    web: __DIR__.'/../routes/web.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    // api: __DIR__.'/../routes/api.php',  ← comentar ou remover se não usar
)
```

---

### 3.6 Configurações pós-cleanup

**Fila para e-mails:**

```bash
sail artisan queue:table
sail artisan migrate
```

**Driver S3 para storage:**

```bash
sail composer require league/flysystem-aws-s3-v3
```

**Criar bucket no MinIO antes de testar uploads:**

Acessar `http://localhost:9001` com `sail` / `password`, criar um bucket chamado `local` e definir a política como `public` para desenvolvimento.

**Lint após cleanup:**

```bash
# PHP
sail exec laravel.test ./vendor/bin/pint

# JS/TS
sail pnpm run lint
```

---

## 4. Modelagem do Banco de Dados

```
users
├── id
├── name
├── email
├── password
└── timestamps

documents
├── id
├── user_id (dono/criador)
├── title
├── description (nullable)
├── file_path
├── file_original_name
├── status (draft | pending | completed | cancelled)
└── timestamps

signatories
├── id
├── document_id
├── name
├── email
├── order (inteiro — define a ordem de assinatura)
├── token (uuid — link único de assinatura)
├── status (pending | signed | declined)
├── signed_at (nullable)
├── ip_address (nullable)
└── timestamps

signatures
├── id
├── signatory_id
├── document_id
├── signature_data (texto — SVG ou base64 da assinatura desenhada)
├── signed_at
└── timestamps
```

> A tabela de auditoria **não é criada manualmente** — ela é gerada pelo `spatie/laravel-activitylog` via migration própria do pacote. A tabela `activity_log` cobre tudo: subject (o documento), causer (usuário ou null para signatários externos), description (evento) e properties (JSON para IP, dados extras).

---

## 5. Telas da Aplicação

### Área pública (sem login)
- **Landing page** — apresentação do sistema
- **Login / Registro / Reset de senha** — geradas pelo Breeze
- **Página de assinatura por token** — signatário acessa via link único no e-mail, sem precisar ter conta

### Área autenticada (dono dos documentos)
- **Dashboard** — lista de documentos com status e filtros
- **Novo documento** — upload de PDF + título + descrição
- **Detalhe do documento** — visualização do PDF, lista de signatários, histórico de auditoria
- **Gerenciar signatários** — adicionar, reordenar, remover signatários antes de enviar
- **Enviar para assinatura** — confirmar e disparar e-mails
- **Perfil** — editar nome, e-mail e senha (gerado pelo Breeze)

---

## 6. Funcionalidades por Módulo

### 6.1 Gestão de Documentos (CRUD principal)
- Upload de PDF com validação de tipo e tamanho
- Visualização inline do PDF (PDF.js ou iframe)
- Edição de título e descrição enquanto em rascunho
- Exclusão lógica (soft delete) quando ainda em rascunho
- Listagem com paginação e filtro por status

### 6.2 Fluxo de Signatários
- Adicionar signatários por nome e e-mail
- Definir ordem de assinatura (assinatura sequencial)
- Ao enviar: gerar token único por signatário e disparar e-mail com link
- Bloquear edição do documento após envio

### 6.3 Assinatura pelo Signatário
- Acesso via link único no e-mail (sem login)
- Visualização do PDF antes de assinar
- Canvas para desenhar a assinatura com o mouse ou touch
- Confirmação com nome completo e checkbox de aceite
- Registro de IP e timestamp
- Ao assinar: verificar se é a vez do signatário (ordem) e avançar o fluxo
- Notificação ao criador quando todos assinaram

### 6.4 Auditoria
- Log de cada evento: criação, envio, assinatura, recusa, conclusão
- Exibição na tela de detalhe do documento
- Dados salvos: ator, e-mail, IP, timestamp

### 6.5 Notificações por E-mail
- Convite para assinar (com link único)
- Lembrete de pendência (manual, via botão)
- Notificação ao criador: alguém assinou
- Notificação ao criador: documento concluído (todos assinaram)

---

## 7. Arquitetura do Projeto

A arquitetura segue as boas práticas idiomáticas do Laravel. Cada camada tem responsabilidade clara:

```
Request → FormRequest (validação)
        → Controller (orquestração, fino)
        → Policy (autorização)
        → Service (lógica de negócio)
        → Model/Eloquent (queries via scopes e relations)
        → DTO (estrutura de retorno)
        → Inertia::render() (resposta)
```

| Camada | Responsabilidade |
|---|---|
| `FormRequest` | Validação e autorização básica de entrada |
| `Policy` | Autorização de acesso por recurso (`view`, `update`, `delete`) |
| `Controller` | Receber request, chamar Service, retornar Inertia render — sem lógica |
| `Service` | Lógica de negócio, orquestração entre models e serviços externos |
| `Model` | Relations, scopes, casts — sem lógica de negócio |
| `DTO` | Estrutura tipada de dados para passar ao Inertia |
| `app/Services/Storage` | Abstração do S3 (MinIO/R2) — o resto do código não conhece S3 diretamente |

---

## 8. Divisão de Tarefas (Fullstack)

A divisão é por **feature**, não por camada. Cada dev entrega a feature completa — backend e frontend — o que evita dependências bloqueantes entre a dupla.

### Dev A — Features de Documento

**Feature: Gestão de Documentos**
- Migration, Model e scopes de Document
- DocumentService (upload, listagem, exclusão)
- DocumentController + FormRequest + Policy
- DTO de Document
- Telas: Dashboard, criar documento, detalhe do documento

**Feature: Geração do PDF final**
- Integração com spatie/laravel-pdf
- Tela de download do documento assinado

**Infra**
- Setup inicial com Sail e cleanup do Breeze
- Configuração do MinIO (dev) e R2 (produção)
- Deploy no Railway

### Dev B — Features de Assinatura

**Feature: Signatários**
- Migration, Model e scopes de Signatory
- SignatoryService (adicionar, reordenar, remover, geração de token)
- SignatoryController + FormRequest + Policy
- DTO de Signatory
- Telas: gerenciar signatários, enviar para assinatura

**Feature: Fluxo de Assinatura**
- SigningService (validação de ordem, registro de assinatura, conclusão)
- Mailables + Queue (convite, lembrete, notificações)
- Controller público de assinatura (sem auth)
- DTO de Signature
- Tela pública de assinatura por token + canvas (signature_pad)

**Infra**
- Configuração do spatie/activitylog (trait nos models + logs manuais)
- Tela de auditoria / histórico no detalhe do documento

> Cada feature deve ser desenvolvida em branch separada e aberta via Pull Request — isso facilita revisão cruzada e mantém o histórico organizado.

---

## 9. Ordem de Desenvolvimento Recomendada

```
Semana 1 — Fundação
├── Setup com Sail + cleanup do Breeze
├── Instalação e configuração dos pacotes (activitylog, pdf, resend, signature_pad)
├── Migrations e Models
├── CRUD de documentos (upload, listagem, detalhe)
└── Telas de Dashboard e formulário de documento

Semana 2 — Fluxo de signatários
├── CRUD de signatários
├── Lógica de envio e geração de tokens
├── E-mails de convite
└── Telas de gerenciamento de signatários

Semana 3 — Assinatura e auditoria
├── Página pública de assinatura por token
├── Canvas de assinatura
├── Lógica de ordem e conclusão do documento
└── Audit log e histórico na tela de detalhe

Semana 4 — Polimento e deploy
├── Notificações (lembrete manual, conclusão)
├── Validações e estados de erro
├── Deploy no Railway
└── Testes de fluxo completo
```

---

## 10. Deploy no Railway (sem custo)

O Railway oferece um plano gratuito com $5 de crédito mensal — suficiente para um projeto acadêmico com uso moderado.

### 9.1 Relação entre Sail (dev) e Railway (produção)

O Sail é exclusivamente para desenvolvimento local — o Railway não usa o `docker-compose.yml` do Sail. Em produção, o Railway detecta o projeto Laravel via **Nixpacks** e monta o container automaticamente.

A grande vantagem do Sail aqui é que o banco de dev já é PostgreSQL, o mesmo da produção — garantindo paridade de ambiente sem esforço.

### 9.2 Serviços no Railway

- Um serviço Laravel (web + queue worker)
- Um serviço PostgreSQL (add-on nativo do Railway)

**Variáveis de ambiente essenciais no Railway:**
```
APP_ENV=production
APP_KEY=              ← gerar com: sail artisan key:generate --show
APP_URL=              ← URL gerada pelo Railway
DB_CONNECTION=pgsql
DB_HOST=              ← fornecido pelo Railway
DB_PORT=5432
DB_DATABASE=          ← fornecido pelo Railway
DB_USERNAME=          ← fornecido pelo Railway
DB_PASSWORD=          ← fornecido pelo Railway
QUEUE_CONNECTION=database
MAIL_MAILER=smtp      ← usar Resend
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=    ← chave do R2
AWS_SECRET_ACCESS_KEY=← chave do R2
AWS_DEFAULT_REGION=auto
AWS_BUCKET=           ← nome do bucket no R2
AWS_ENDPOINT=         ← https://<account>.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
```

**Nixpacks — criar um `Procfile` na raiz para rodar o queue worker junto:**
```
web: php artisan serve --host=0.0.0.0 --port=$PORT
worker: php artisan queue:work --sleep=3 --tries=3
```

---

## 11. Pacotes

### Composer (PHP)

| Pacote | Para que serve |
|---|---|
| `spatie/laravel-activitylog` | Auditoria de eventos — log automático em models e log manual para eventos de negócio |
| `spatie/laravel-pdf` | Geração do PDF final com resumo de assinaturas (quem assinou, quando, IP) |
| `resend/resend-laravel` | E-mail transacional gratuito (3.000/mês) para produção |
| `league/flysystem-aws-s3-v3` | Driver S3 do Laravel — usado tanto com MinIO (dev) quanto com R2 (produção) |

**Instalação:**
```bash
sail composer require spatie/laravel-activitylog spatie/laravel-pdf resend/resend-laravel league/flysystem-aws-s3-v3
sail artisan activitylog:publish --migrations
sail artisan migrate
```

**Uso do activitylog no projeto:**
```php
// Log automático — adicionar trait no Model (activitylog v5)
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Document extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'description', 'status'])
            ->logOnlyDirty();
    }
}

// Log manual para eventos de negócio (assinatura, envio, recusa)
activity()
    ->performedOn($document)
    ->withProperties(['ip' => request()->ip(), 'signatory' => $signatory->email])
    ->log('documento assinado');
```

### pnpm (JavaScript)

| Pacote | Para que serve |
|---|---|
| `signature_pad` | Canvas de assinatura — suporte a mouse e touch, leve e sem dependências |

**Instalação:**
```bash
sail pnpm add signature_pad
```

> **shadcn/ui não vem no Breeze.** Inicializar manualmente:
> ```bash
> sail pnpm dlx shadcn@latest init
> sail pnpm dlx shadcn@latest add button input label card dialog dropdown-menu form
> ```
> Os primitivos vão para `resources/js/components/ui/` (lowercase — convenção do shadcn) e coexistem com `resources/js/components/` (PascalCase do Breeze) até que as páginas de Auth/Profile sejam refatoradas.

> **Storage:** Cloudflare R2 oferece 10GB gratuitos com egress grátis. Criar conta em cloudflare.com, criar um bucket e gerar as chaves de API em R2 → Manage API Tokens.
