<?php

use App\Enums\DocumentStatus;
use App\Mail\SigningInvitationMail;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->withoutVite();
});

test('the owner can send a draft document with signatories', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->draft()->withSignatories(2)->create();
    $first = $document->signatories()->orderBy('order')->first();

    $this->actingAs($user)
        ->post(route('documents.send', $document))
        ->assertRedirect(route('documents.show', $document));

    expect($document->refresh()->status)->toBe(DocumentStatus::Pending);

    Mail::assertQueued(
        SigningInvitationMail::class,
        fn (SigningInvitationMail $mail) => $mail->signatory->is($first) && $mail->hasTo($first->email)
    );
    Mail::assertQueuedCount(1);
});

test('a document cannot be sent without signatories', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->draft()->create();

    $this->actingAs($user)
        ->post(route('documents.send', $document))
        ->assertForbidden();

    expect($document->refresh()->status)->toBe(DocumentStatus::Draft);
    Mail::assertNothingQueued();
});

test('a non owner cannot send a document', function () {
    $document = Document::factory()->draft()->withSignatories(1)->create();

    $this->actingAs(User::factory()->create())
        ->post(route('documents.send', $document))
        ->assertForbidden();

    Mail::assertNothingQueued();
});

test('a document that is not a draft cannot be sent again', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->pending()->withSignatories(2)->create();

    $this->actingAs($user)
        ->post(route('documents.send', $document))
        ->assertForbidden();

    Mail::assertNothingQueued();
});
