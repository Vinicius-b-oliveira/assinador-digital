# Assinador Digital

Upload de PDFs, gestão de signatários com ordem definida e coleta de assinaturas por link via e-mail.

## Stack

Laravel 13 · Breeze (Inertia + React 19 + TS) · Tailwind v4 · shadcn/ui · PostgreSQL · MinIO · Mailpit · Pest · pnpm · Sail.

## Quick start

Pré-requisitos: Docker e Docker Compose.

```bash
cp .env.example .env

docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd)":/var/www/html -w /var/www/html \
  laravelsail/php85-composer:latest composer install --ignore-platform-reqs

./vendor/bin/sail up -d
./vendor/bin/sail pnpm install
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail pnpm run dev
```

O bucket do MinIO é criado automaticamente no `sail up` pelo serviço `minio-init`.

Sugestão: adicionar `alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'` no shell.

## Endpoints

| Serviço       | URL                                                                        |
| ------------- | -------------------------------------------------------------------------- |
| App           | http://localhost                                                           |
| Vite HMR      | http://localhost:5173 (não acessar direto, o Vite injeta os assets na app) |
| Mailpit       | http://localhost:8025                                                      |
| MinIO Console | http://localhost:8900 (login `sail` / `password`)                          |

## Comandos do dia a dia

```bash
sail artisan test                          # Pest
sail exec laravel.test ./vendor/bin/pint   # format PHP
sail pnpm run lint                         # ESLint + Prettier (auto-fix)
sail pnpm run build                        # build de produção
```

## Padrões do projeto

- **Arquitetura:** Request → FormRequest → Controller (fino) → Policy → Service → Model → DTO → `Inertia::render()`. Detalhes em [docs/boas-praticas.md](docs/boas-praticas.md).
- **Storage S3** acessado só por `DocumentStorageService` (nunca `Storage::` direto fora dele).
- **Pastas React:** `Pages/` e `Layouts/` em PascalCase (resolvidos por nome pelo Inertia); `components/` (com `components/ui/` para primitivos shadcn) e `lib/` em lowercase.
