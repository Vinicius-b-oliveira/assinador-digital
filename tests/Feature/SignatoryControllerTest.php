<?php

use App\Models\Document;
use App\Models\Signatory;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

test('the owner can add a signatory to a draft document', function () {
    $document = Document::factory()->draft()->create();

    $this->actingAs($document->user)
        ->post(route('documents.signatories.store', $document), [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
        ])
        ->assertRedirect(route('documents.show', $document));

    $this->assertDatabaseHas('signatories', [
        'document_id' => $document->id,
        'name' => 'Maria Silva',
        'email' => 'maria@example.com',
        'order' => 1,
    ]);
});

test('signatory emails must be unique within the same document', function () {
    $document = Document::factory()->draft()->create();
    Signatory::factory()->for($document)->create(['email' => 'maria@example.com']);

    $this->actingAs($document->user)
        ->post(route('documents.signatories.store', $document), [
            'name' => 'Outra Maria',
            'email' => 'maria@example.com',
        ])
        ->assertSessionHasErrors('email');
});

test('a stranger cannot manage signatories', function () {
    $document = Document::factory()->draft()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('documents.signatories.store', $document), [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
        ])
        ->assertForbidden();
});

test('signatories cannot be managed after the document leaves draft', function () {
    $document = Document::factory()->pending()->create();

    $this->actingAs($document->user)
        ->post(route('documents.signatories.store', $document), [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
        ])
        ->assertForbidden();
});

test('the owner can update a draft signatory', function () {
    $signatory = Signatory::factory()->create();

    $this->actingAs($signatory->document->user)
        ->put(route('signatories.update', $signatory), [
            'name' => 'Maria Atualizada',
            'email' => 'maria.atualizada@example.com',
        ])
        ->assertRedirect(route('documents.show', $signatory->document));

    expect($signatory->fresh()->email)->toBe('maria.atualizada@example.com');
});

test('the owner can remove a signatory and compact the order', function () {
    $document = Document::factory()->draft()->withSignatories(3)->create();
    $second = $document->signatories()->where('order', 2)->firstOrFail();

    $this->actingAs($document->user)
        ->delete(route('signatories.destroy', $second))
        ->assertRedirect(route('documents.show', $document));

    expect($document->fresh()->signatories->pluck('order')->all())->toBe([1, 2]);
});

test('the owner can reorder all signatories in a draft document', function () {
    $document = Document::factory()->draft()->withSignatories(3)->create();
    $ids = $document->signatories->pluck('id')->reverse()->values()->all();

    $this->actingAs($document->user)
        ->put(route('documents.signatories.reorder', $document), [
            'signatories' => $ids,
        ])
        ->assertRedirect(route('documents.show', $document));

    expect($document->fresh()->signatories->pluck('id')->all())->toBe($ids);
});

test('reorder must include every document signatory', function () {
    $document = Document::factory()->draft()->withSignatories(2)->create();

    $this->actingAs($document->user)
        ->put(route('documents.signatories.reorder', $document), [
            'signatories' => [$document->signatories->first()->id],
        ])
        ->assertSessionHasErrors('signatories');
});
