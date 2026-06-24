<?php

use App\Enums\SignatoryStatus;
use App\Jobs\SendSignatureReminderJob;
use App\Mail\DocumentCompletedMail;
use App\Mail\SignatureRecordedMail;
use App\Mail\SigningInvitationMail;
use App\Models\Document;
use App\Models\User;
use App\Services\SigningService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
});

function signature(): string
{
    return 'data:image/png;base64,'.base64_encode('assinatura');
}

test('the owner is notified when a signatory signs', function () {
    Mail::fake();
    $owner = User::factory()->create();
    $document = Document::factory()->for($owner)->pending()->withSignatories(2)->create();
    $first = $document->signatories()->orderBy('order')->first();

    $this->post(route('public.sign.sign', $first->token), [
        'signature_data' => signature(),
        'signer_name' => $first->name,
        'accept_terms' => true,
    ])->assertRedirect();

    Mail::assertQueued(
        SignatureRecordedMail::class,
        fn (SignatureRecordedMail $mail) => $mail->signatory->is($first) && $mail->hasTo($owner->email)
    );
});

test('the owner is notified when the document is completed', function () {
    Mail::fake();
    $owner = User::factory()->create();
    $document = Document::factory()->for($owner)->pending()->withSignatories(2)->create();
    [$first, $second] = $document->signatories()->orderBy('order')->get()->all();

    $first->update(['status' => SignatoryStatus::Signed, 'signed_at' => now()]);

    $this->post(route('public.sign.sign', $second->token), [
        'signature_data' => signature(),
        'signer_name' => $second->name,
        'accept_terms' => true,
    ])->assertRedirect();

    Mail::assertQueued(
        DocumentCompletedMail::class,
        fn (DocumentCompletedMail $mail) => $mail->document->is($document) && $mail->hasTo($owner->email)
    );
});

test('the owner can send a reminder to the current signatory', function () {
    Bus::fake();
    $owner = User::factory()->create();
    $document = Document::factory()->for($owner)->pending()->withSignatories(2)->create();
    $first = $document->signatories()->orderBy('order')->first();

    $this->actingAs($owner)
        ->post(route('signatories.remind', $first))
        ->assertRedirect();

    Bus::assertDispatched(
        SendSignatureReminderJob::class,
        fn (SendSignatureReminderJob $job) => $job->signatory->is($first)
    );
});

test('a stranger cannot send a reminder', function () {
    Bus::fake();
    $document = Document::factory()->pending()->withSignatories(1)->create();
    $first = $document->signatories()->first();

    $this->actingAs(User::factory()->create())
        ->post(route('signatories.remind', $first))
        ->assertForbidden();

    Bus::assertNothingDispatched();
});

test('a reminder cannot be sent to a signatory who already signed', function () {
    Bus::fake();
    $owner = User::factory()->create();
    $document = Document::factory()->for($owner)->pending()->withSignatories(2)->create();
    $first = $document->signatories()->orderBy('order')->first();
    $first->update(['status' => SignatoryStatus::Signed, 'signed_at' => now()]);

    $this->actingAs($owner)
        ->post(route('signatories.remind', $first))
        ->assertForbidden();

    Bus::assertNothingDispatched();
});

test('the reminder job re-sends the invitation to the current signatory', function () {
    Mail::fake();
    $document = Document::factory()->pending()->withSignatories(2)->create();
    $first = $document->signatories()->orderBy('order')->first();

    (new SendSignatureReminderJob($first))->handle(app(SigningService::class));

    Mail::assertQueued(
        SigningInvitationMail::class,
        fn (SigningInvitationMail $mail) => $mail->signatory->is($first)
    );
});

test('the reminder job does nothing when it is not the signatory turn', function () {
    Mail::fake();
    $document = Document::factory()->pending()->withSignatories(2)->create();
    $second = $document->signatories()->orderBy('order')->get()->last();

    (new SendSignatureReminderJob($second))->handle(app(SigningService::class));

    Mail::assertNothingQueued();
});
