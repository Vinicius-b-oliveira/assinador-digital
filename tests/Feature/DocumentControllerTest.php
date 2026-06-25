<?php

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('s3');
    $this->withoutVite();
});

test('guests are redirected from the documents index', function () {
    $this->get('/documents')->assertRedirect('/login');
});

test('index lists only the authenticated user documents', function () {
    $user = User::factory()->create();
    Document::factory()->for($user)->count(2)->create();
    Document::factory()->count(3)->create();

    $this->actingAs($user)
        ->get('/documents')
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Documents/Index', false)
                ->has('documents.data', 2)
        );
});

test('index filters documents by status', function () {
    $user = User::factory()->create();
    Document::factory()->for($user)->draft()->count(2)->create();
    Document::factory()->for($user)->completed()->create();

    $this->actingAs($user)
        ->get('/documents?status=completed')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('documents.data', 1));
});

test('a user can create a document with a valid pdf', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/documents', [
        'title' => 'Contrato de Prestação de Serviços',
        'description' => 'Versão final',
        'pdf' => UploadedFile::fake()->create('contrato.pdf', 1024, 'application/pdf'),
    ]);

    $document = Document::firstOrFail();

    $response->assertRedirect(route('documents.show', $document));
    expect($document->user_id)->toBe($user->id)
        ->and($document->status)->toBe(DocumentStatus::Draft)
        ->and($document->file_original_name)->toBe('contrato.pdf');
    Storage::disk('s3')->assertExists($document->file_path);
});

test('store validation rejects invalid payloads', function (array $payload, string $invalidField) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/documents', $payload)
        ->assertSessionHasErrors($invalidField);
})->with([
    'sem título' => [['pdf' => null], 'title'],
    'arquivo não-pdf' => [fn () => ['title' => 'X', 'pdf' => UploadedFile::fake()->create('a.txt', 10, 'text/plain')], 'pdf'],
    'pdf acima de 20MB' => [fn () => ['title' => 'X', 'pdf' => UploadedFile::fake()->create('big.pdf', 20481, 'application/pdf')], 'pdf'],
]);

test('owner can view a document but a stranger cannot', function () {
    $document = Document::factory()->create();

    $this->actingAs($document->user)
        ->get(route('documents.show', $document))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Documents/Show', false));

    $this->actingAs(User::factory()->create())
        ->get(route('documents.show', $document))
        ->assertForbidden();
});

test('the file route streams the pdf inline for the owner and forbids strangers', function () {
    $document = Document::factory()->create();
    Storage::disk('s3')->put($document->file_path, 'conteudo-pdf');

    $response = $this->actingAs($document->user)->get(route('documents.file', $document));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('inline');

    $this->actingAs(User::factory()->create())
        ->get(route('documents.file', $document))
        ->assertForbidden();
});

test('the file route returns not found when the stored pdf is missing', function () {
    $document = Document::factory()->create();

    $this->actingAs($document->user)
        ->get(route('documents.file', $document))
        ->assertNotFound();
});

test('the certificate route downloads the pdf for the owner and forbids strangers', function () {
    $document = Document::factory()->completed()->create(['certificate_path' => 'certificates/cert.pdf']);
    Storage::disk('s3')->put($document->certificate_path, 'conteudo-certificado');

    $response = $this->actingAs($document->user)->get(route('documents.certificate', $document));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('attachment');

    $this->actingAs(User::factory()->create())
        ->get(route('documents.certificate', $document))
        ->assertForbidden();
});

test('the certificate route returns not found when the document is not completed', function () {
    $document = Document::factory()->pending()->create(['certificate_path' => 'certificates/cert.pdf']);

    $this->actingAs($document->user)
        ->get(route('documents.certificate', $document))
        ->assertNotFound();
});

test('the certificate route returns not found when there is no certificate', function () {
    $document = Document::factory()->completed()->create(['certificate_path' => null]);

    $this->actingAs($document->user)
        ->get(route('documents.certificate', $document))
        ->assertNotFound();
});

test('a draft document can be updated by its owner', function () {
    $document = Document::factory()->draft()->create();

    $this->actingAs($document->user)
        ->put(route('documents.update', $document), [
            'title' => 'Título atualizado',
            'description' => null,
        ])
        ->assertRedirect(route('documents.show', $document));

    expect($document->fresh()->title)->toBe('Título atualizado');
});

test('a non-draft document cannot be updated', function () {
    $document = Document::factory()->pending()->create();

    $this->actingAs($document->user)
        ->put(route('documents.update', $document), ['title' => 'Tentativa'])
        ->assertForbidden();
});

test('a stranger cannot update a document', function () {
    $document = Document::factory()->draft()->create();

    $this->actingAs(User::factory()->create())
        ->put(route('documents.update', $document), ['title' => 'Hack'])
        ->assertForbidden();
});

test('a draft document is soft deleted by its owner', function () {
    $document = Document::factory()->draft()->create();

    $this->actingAs($document->user)
        ->delete(route('documents.destroy', $document))
        ->assertRedirect(route('documents.index'));

    $this->assertSoftDeleted($document);
});

test('a non-draft document cannot be deleted', function () {
    $document = Document::factory()->completed()->create();

    $this->actingAs($document->user)
        ->delete(route('documents.destroy', $document))
        ->assertForbidden();

    expect($document->fresh())->not->toBeNull();
});

test('a draft document can only be sent when it has signatories', function () {
    $emptyDocument = Document::factory()->draft()->create();
    $readyDocument = Document::factory()->draft()->withSignatories()->create();

    expect($emptyDocument->user->can('send', $emptyDocument))->toBeFalse()
        ->and($readyDocument->user->can('send', $readyDocument))->toBeTrue();
});
