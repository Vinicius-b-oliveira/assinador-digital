# Boas Práticas — Assinador Digital
> Referência de arquitetura para manter consistência entre a dupla durante o desenvolvimento.

---

## Estrutura de Pastas

```
app/
├── DTOs/
│   ├── DocumentDTO.php
│   ├── SignatoryDTO.php
│   └── SignatureDTO.php
├── Http/
│   ├── Controllers/
│   │   ├── DocumentController.php
│   │   ├── SignatoryController.php
│   │   └── Public/
│   │       └── SigningController.php   ← sem auth
│   ├── Requests/
│   │   ├── StoreDocumentRequest.php
│   │   ├── UpdateDocumentRequest.php
│   │   ├── StoreSignatoryRequest.php
│   │   └── SignDocumentRequest.php
├── Models/
│   ├── Document.php
│   ├── Signatory.php
│   └── Signature.php
├── Policies/
│   ├── DocumentPolicy.php
│   └── SignatoryPolicy.php
├── Services/
│   ├── DocumentService.php
│   ├── SignatoryService.php
│   ├── SigningService.php
│   └── Storage/
│       └── DocumentStorageService.php  ← abstração do S3
```

---

## Controllers — orquestração apenas

Controllers não contêm lógica de negócio. Recebem o request validado, delegam ao Service e retornam o Inertia render.

```php
// ✅ Correto
class DocumentController extends Controller
{
    public function __construct(private DocumentService $service) {}

    public function store(StoreDocumentRequest $request): Response
    {
        $document = $this->service->create($request->validated(), $request->user());

        return to_route('documents.show', $document);
    }
}

// ❌ Errado — lógica no controller
public function store(Request $request): Response
{
    $request->validate([...]);

    $path = Storage::put('documents', $request->file('pdf'));
    $document = Document::create([..., 'file_path' => $path]);
    activity()->log('documento criado');

    return to_route('documents.show', $document);
}
```

---

## Form Requests — validação e autorização de entrada

Todo input do usuário passa por um FormRequest. A autorização básica de acesso à rota também fica aqui quando não depende de um model específico.

```php
class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // só usuários autenticados
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'pdf'         => ['required', 'file', 'mimes:pdf', 'max:20480'], // 20MB
        ];
    }
}
```

---

## Policies — autorização por recurso

Policies controlam quem pode fazer o quê com um recurso específico. Registradas automaticamente pelo Laravel por convenção de nome.

```php
class DocumentPolicy
{
    // Só o dono pode ver
    public function view(User $user, Document $document): bool
    {
        return $user->id === $document->user_id;
    }

    // Só pode editar se ainda for rascunho
    public function update(User $user, Document $document): bool
    {
        return $user->id === $document->user_id
            && $document->status === 'draft';
    }

    // Só pode enviar se tiver ao menos um signatário
    public function send(User $user, Document $document): bool
    {
        return $user->id === $document->user_id
            && $document->status === 'draft'
            && $document->signatories()->exists();
    }
}

// Uso no controller
public function update(UpdateDocumentRequest $request, Document $document): Response
{
    $this->authorize('update', $document);
    // ...
}
```

---

## Services — lógica de negócio

Toda lógica que vai além de uma query simples fica no Service. Services podem chamar outros Services e são injetados via construtor.

```php
class SigningService
{
    public function __construct(
        private DocumentStorageService $storage,
        private SignatoryService $signatoryService,
    ) {}

    public function sign(Signatory $signatory, string $signatureData, string $ip): Signature
    {
        // 1. Verificar se é a vez deste signatário
        $this->assertIsCurrentTurn($signatory);

        // 2. Registrar a assinatura
        $signature = Signature::create([
            'signatory_id'   => $signatory->id,
            'document_id'    => $signatory->document_id,
            'signature_data' => $signatureData,
            'signed_at'      => now(),
        ]);

        // 3. Atualizar status do signatário
        $signatory->update(['status' => 'signed', 'signed_at' => now(), 'ip_address' => $ip]);

        // 4. Log de auditoria
        activity()
            ->performedOn($signatory->document)
            ->withProperties(['ip' => $ip, 'signatory' => $signatory->email])
            ->log('documento assinado');

        // 5. Avançar o fluxo
        $this->signatoryService->advanceFlow($signatory->document);

        return $signature;
    }

    private function assertIsCurrentTurn(Signatory $signatory): void
    {
        $currentTurn = $signatory->document
            ->signatories()
            ->pending()
            ->orderBy('order')
            ->first();

        if ($currentTurn?->id !== $signatory->id) {
            throw new \DomainException('Não é a vez deste signatário.');
        }
    }
}
```

---

## Models — relations, scopes e casts

Models são declarativos. Sem lógica de negócio, sem chamadas a serviços externos.

```php
class Document extends Model
{
    use LogsActivity, SoftDeletes;

    protected $casts = [
        'status' => DocumentStatus::class, // enum backed
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function signatories(): HasMany
    {
        return $this->hasMany(Signatory::class)->orderBy('order');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class);
    }

    // Scopes
    public function scopePending(Builder $query): void
    {
        $query->where('status', DocumentStatus::Pending);
    }

    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', DocumentStatus::Completed);
    }

    public function scopeOwnedBy(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }

    // Activitylog
    protected static $logAttributes = ['title', 'status', 'description'];
    protected static $logOnlyDirty = true;
}
```

---

## DTOs — dados tipados para o Inertia

DTOs substituem API Resources. São classes simples que estruturam os dados antes de passar ao `Inertia::render()`.

```php
// app/DTOs/DocumentDTO.php
final class DocumentDTO
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $title,
        public readonly ?string $description,
        public readonly string  $status,
        public readonly string  $createdAt,
        public readonly int     $signatoryCount,
        public readonly int     $signedCount,
    ) {}

    public static function fromModel(Document $document): self
    {
        return new self(
            id:             $document->id,
            title:          $document->title,
            description:    $document->description,
            status:         $document->status->value,
            createdAt:      $document->created_at->toDateString(),
            signatoryCount: $document->signatories->count(),
            signedCount:    $document->signatories->where('status', 'signed')->count(),
        );
    }

    public static function collection(Collection $documents): array
    {
        return $documents->map(fn ($d) => self::fromModel($d))->toArray();
    }
}

// Uso no controller
public function index(Request $request): Response
{
    $documents = Document::ownedBy($request->user())
        ->with('signatories')
        ->latest()
        ->paginate(15);

    return Inertia::render('Documents/Index', [
        'documents' => DocumentDTO::collection($documents->items()),
        'pagination' => $documents->toArray(),
    ]);
}
```

---

## Abstração de Serviços Externos

Serviços externos (S3, e-mail) são acessados apenas através de uma classe de abstração. O restante do código não conhece S3, MinIO ou R2 diretamente.

```php
// app/Services/Storage/DocumentStorageService.php
class DocumentStorageService
{
    public function upload(UploadedFile $file): string
    {
        $filename = Str::uuid() . '.pdf';
        Storage::disk('s3')->put("documents/{$filename}", $file->getContent());
        return $filename;
    }

    public function temporaryUrl(string $filename, int $minutes = 30): string
    {
        return Storage::disk('s3')->temporaryUrl(
            "documents/{$filename}",
            now()->addMinutes($minutes)
        );
    }

    public function delete(string $filename): void
    {
        Storage::disk('s3')->delete("documents/{$filename}");
    }
}
```

---

## Testes com Pest

Cada Service e Controller tem seu teste. Use factories para dados e `actingAs()` para autenticação.

```php
// tests/Feature/DocumentTest.php
use App\Models\{Document, User};

it('cria um documento com sucesso', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post('/documents', [
            'title' => 'Contrato de Prestação de Serviços',
            'pdf'   => UploadedFile::fake()->create('contrato.pdf', 1024, 'application/pdf'),
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('documents', [
        'user_id' => $user->id,
        'title'   => 'Contrato de Prestação de Serviços',
        'status'  => 'draft',
    ]);
});

it('não permite editar documento já enviado', function () {
    $user     = User::factory()->create();
    $document = Document::factory()->for($user)->pending()->create();

    $response = $this->actingAs($user)
        ->put("/documents/{$document->id}", ['title' => 'Novo título']);

    $response->assertForbidden();
});

it('signatário não pode assinar fora da sua vez', function () {
    $document   = Document::factory()->withSignatories(2)->pending()->create();
    $second     = $document->signatories->last();

    $response = $this->post("/sign/{$second->token}", [
        'signature_data' => 'data:image/png;base64,...',
    ]);

    $response->assertForbidden();
});
```

---

## Factories — dados realistas para testes

Toda factory deve gerar dados que representem cenários reais. Usar Faker com critério — evitar `lorem ipsum` onde um dado real faria mais sentido.

**Regras:**
- Cada factory cobre o estado padrão (`draft` para Document, `pending` para Signatory)
- Estados adicionais via `state()` — nunca criar métodos mágicos fora do padrão do Laravel
- Factories com relacionamentos usam `for()` — nunca criam IDs na mão
- Dados sensíveis (tokens, IPs) gerados com os mesmos helpers que o código de produção usa

```php
// database/factories/DocumentFactory.php
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'             => User::factory(),
            'title'               => $this->faker->sentence(4),
            'description'         => $this->faker->optional()->paragraph(),
            'file_path'           => 'documents/' . Str::uuid() . '.pdf',
            'file_original_name'  => $this->faker->word() . '.pdf',
            'status'              => DocumentStatus::Draft,
        ];
    }

    // Estados de status
    public function draft(): static
    {
        return $this->state(['status' => DocumentStatus::Draft]);
    }

    public function pending(): static
    {
        return $this->state(['status' => DocumentStatus::Pending]);
    }

    public function completed(): static
    {
        return $this->state(['status' => DocumentStatus::Completed]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => DocumentStatus::Cancelled]);
    }

    // Estado com signatários já criados — útil para testes de fluxo
    public function withSignatories(int $count = 2): static
    {
        return $this->has(
            Signatory::factory()->count($count)->sequence(
                fn (Sequence $sequence) => ['order' => $sequence->index + 1]
            ),
            'signatories'
        );
    }

    // Documento completo: pending + signatários com ordem definida
    public function readyToSign(): static
    {
        return $this->pending()->withSignatories(2);
    }
}
```

```php
// database/factories/SignatoryFactory.php
class SignatoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'name'        => $this->faker->name(),
            'email'       => $this->faker->unique()->safeEmail(),
            'order'       => 1,
            'token'       => Str::uuid(),
            'status'      => SignatoryStatus::Pending,
            'signed_at'   => null,
            'ip_address'  => null,
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status'    => SignatoryStatus::Pending,
            'signed_at' => null,
            'ip_address' => null,
        ]);
    }

    public function signed(): static
    {
        return $this->state([
            'status'     => SignatoryStatus::Signed,
            'signed_at'  => now(),
            'ip_address' => $this->faker->ipv4(),
        ]);
    }

    public function declined(): static
    {
        return $this->state([
            'status'    => SignatoryStatus::Declined,
            'signed_at' => null,
        ]);
    }
}
```

```php
// database/factories/SignatureFactory.php
class SignatureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'signatory_id'   => Signatory::factory()->signed(),
            'document_id'    => Document::factory()->pending(),
            'signature_data' => 'data:image/png;base64,' . base64_encode('fake-signature'),
            'signed_at'      => now(),
        ];
    }
}
```

**Uso nos testes:**

```php
// Criar documento com dono específico
$document = Document::factory()->for($user)->pending()->create();

// Criar documento com signatários em sequência de ordem
$document = Document::factory()->withSignatories(3)->pending()->create();

// Criar signatário para um documento existente
$signatory = Signatory::factory()->for($document)->signed()->create(['order' => 1]);

// Criar múltiplos usuários com documentos
$users = User::factory()->count(5)->has(Document::factory()->count(3))->create();
```

---

## Seeders — ambiente local realista

Seeders servem para dois propósitos distintos — e cada um tem sua classe:

1. **`DatabaseSeeder`** — orquestra tudo, chamado via `sail artisan db:seed`
2. **Seeders específicos** — cada um sabe criar um cenário completo e isolado

**Regra principal:** o seeder deve criar um ambiente onde qualquer funcionalidade pode ser testada manualmente sem precisar criar dados na mão.

```php
// database/seeders/DatabaseSeeder.php
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            DocumentSeeder::class,
        ]);
    }
}
```

```php
// database/seeders/UserSeeder.php
class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Usuário fixo para desenvolvimento — credenciais conhecidas
        User::factory()->create([
            'name'     => 'Dev User',
            'email'    => 'dev@dev.com',
            'password' => bcrypt('password'),
        ]);

        // Usuário secundário para testar permissões cruzadas
        User::factory()->create([
            'name'     => 'Other User',
            'email'    => 'other@dev.com',
            'password' => bcrypt('password'),
        ]);

        // Usuários extras para listagens
        User::factory()->count(5)->create();
    }
}
```

```php
// database/seeders/DocumentSeeder.php
class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $dev   = User::where('email', 'dev@dev.com')->first();
        $other = User::where('email', 'other@dev.com')->first();

        // Cenário 1: rascunhos — pode editar, adicionar signatários
        Document::factory()->for($dev)->count(3)->draft()->create();

        // Cenário 2: aguardando assinatura — fluxo em andamento
        Document::factory()
            ->for($dev)
            ->count(2)
            ->readyToSign()
            ->create();

        // Cenário 3: primeiro signatário já assinou, aguardando segundo
        Document::factory()
            ->for($dev)
            ->pending()
            ->has(
                Signatory::factory()->signed()->state(['order' => 1]),
                'signatories'
            )
            ->has(
                Signatory::factory()->pending()->state(['order' => 2]),
                'signatories'
            )
            ->create();

        // Cenário 4: concluído — todos assinaram
        Document::factory()->for($dev)->count(2)->completed()->create();

        // Cenário 5: cancelado
        Document::factory()->for($dev)->cancelled()->create();

        // Documentos do outro usuário — para testar isolamento de acesso
        Document::factory()->for($other)->count(3)->create();
    }
}
```

**Refresh rápido durante desenvolvimento:**

```bash
# Apaga tudo, recria e popula — usar livremente em dev
sail artisan migrate:fresh --seed

# Rodar só um seeder específico
sail artisan db:seed --class=DocumentSeeder
```

> Nunca usar `migrate:fresh --seed` em produção. Adicionar uma verificação no `DatabaseSeeder` se necessário:
> ```php
> if (app()->isProduction()) abort(403, 'Seeders não são permitidos em produção.');
> ```

---

## Convenções Gerais

**Nomenclatura:**
- Controllers no singular: `DocumentController`, não `DocumentsController`
- Services com sufixo: `DocumentService`, `SigningService`
- DTOs com sufixo: `DocumentDTO`
- FormRequests descritivos: `StoreDocumentRequest`, `SendDocumentRequest`

**Commits:**
- Usar [Conventional Commits](https://www.conventionalcommits.org/): `feat:`, `fix:`, `refactor:`, `test:`
- Um commit por responsabilidade — não misturar backend e frontend no mesmo commit

**Branches:**
- `main` → produção
- `develop` → integração
- `feature/documento-crud`, `feature/fluxo-assinatura` etc.
- PRs sempre para `develop`, nunca direto para `main`

**Enum para status:**
```php
// app/Enums/DocumentStatus.php
enum DocumentStatus: string
{
    case Draft     = 'draft';
    case Pending   = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
```
