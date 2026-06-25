<?php

use App\Enums\DocumentStatus;
use App\Enums\SignatoryStatus;
use App\Mail\SigningInvitationMail;
use App\Models\Document;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->withoutVite();
});

function validSignature(): string
{
    return 'data:image/png;base64,'.base64_encode('assinatura');
}

test('an invalid token returns not found', function () {
    $this->get('/sign/not-a-real-token')->assertNotFound();
});

test('the public signing page is reachable without authentication', function () {
    $document = Document::factory()->pending()->withSignatories(2)->create();
    $first = $document->signatories()->orderBy('order')->first();

    $this->get(route('public.sign.show', $first->token))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Public/Sign', false)
                ->where('state', 'ready')
                ->where('documentTitle', $document->title)
        );
});

test('a signatory cannot sign before it is their turn', function () {
    $document = Document::factory()->pending()->withSignatories(2)->create();
    $second = $document->signatories()->orderBy('order')->get()->last();

    $this->get(route('public.sign.show', $second->token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('state', 'waiting'));

    $this->post(route('public.sign.sign', $second->token), [
        'signature_data' => validSignature(),
        'signer_name' => $second->name,
        'accept_terms' => true,
    ])->assertSessionHasErrors('signature');

    expect($second->refresh()->status)->toBe(SignatoryStatus::Pending);
    $this->assertDatabaseCount('signatures', 0);
});

test('the current signatory can sign and the next one is invited', function () {
    $document = Document::factory()->pending()->withSignatories(2)->create();
    [$first, $second] = $document->signatories()->orderBy('order')->get()->all();

    $this->post(route('public.sign.sign', $first->token), [
        'signature_data' => validSignature(),
        'signer_name' => $first->name,
        'accept_terms' => true,
    ])->assertRedirect(route('public.sign.show', $first->token));

    expect($first->refresh()->status)->toBe(SignatoryStatus::Signed)
        ->and($first->ip_address)->not->toBeNull()
        ->and($document->refresh()->status)->toBe(DocumentStatus::Pending);

    $this->assertDatabaseHas('signatures', [
        'signatory_id' => $first->id,
        'document_id' => $document->id,
        'signer_name' => $first->name,
    ]);

    Mail::assertQueued(
        SigningInvitationMail::class,
        fn (SigningInvitationMail $mail) => $mail->signatory->is($second)
    );
});

test('the document is completed when the last signatory signs', function () {
    $document = Document::factory()->pending()->withSignatories(2)->create();
    [$first, $second] = $document->signatories()->orderBy('order')->get()->all();

    $first->update(['status' => SignatoryStatus::Signed, 'signed_at' => now()]);

    $this->post(route('public.sign.sign', $second->token), [
        'signature_data' => validSignature(),
        'signer_name' => $second->name,
        'accept_terms' => true,
    ])->assertRedirect();

    expect($document->refresh()->status)->toBe(DocumentStatus::Completed);
});

test('a signatory can decline and the document is cancelled', function () {
    $document = Document::factory()->pending()->withSignatories(2)->create();
    $first = $document->signatories()->orderBy('order')->first();

    $this->post(route('public.sign.decline', $first->token))
        ->assertRedirect(route('public.sign.show', $first->token));

    expect($first->refresh()->status)->toBe(SignatoryStatus::Declined)
        ->and($document->refresh()->status)->toBe(DocumentStatus::Cancelled);
});

test('signing requires accepting the terms and a valid signature', function () {
    $document = Document::factory()->pending()->withSignatories(1)->create();
    $first = $document->signatories()->first();

    $this->post(route('public.sign.sign', $first->token), [
        'signature_data' => 'not-a-data-url',
        'signer_name' => '',
        'accept_terms' => false,
    ])->assertSessionHasErrors(['signature_data', 'signer_name', 'accept_terms']);

    expect($first->refresh()->status)->toBe(SignatoryStatus::Pending);
});
