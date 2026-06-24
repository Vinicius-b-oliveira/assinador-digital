<?php

use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Mail::fake();
    $this->withoutVite();
});

function pngSignature(): string
{
    return 'data:image/png;base64,'.base64_encode('assinatura');
}

test('creating a document logs a created event', function () {
    $document = Document::factory()->create();

    expect(Activity::forSubject($document)->pluck('event'))->toContain('created');
});

test('sending a document logs a sent event', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->draft()->withSignatories(1)->create();

    $this->actingAs($user)->post(route('documents.send', $document));

    expect(Activity::forSubject($document)->pluck('event'))->toContain('sent');
});

test('signing logs a signed event with ip and signatory properties', function () {
    $document = Document::factory()->pending()->withSignatories(2)->create();
    $first = $document->signatories()->orderBy('order')->first();

    $this->post(route('public.sign.sign', $first->token), [
        'signature_data' => pngSignature(),
        'signer_name' => $first->name,
        'accept_terms' => true,
    ]);

    $activity = Activity::forSubject($document)->forEvent('signed')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties->get('signatory'))->toBe($first->name)
        ->and($activity->properties->get('ip'))->not->toBeNull();
});

test('declining logs a declined event', function () {
    $document = Document::factory()->pending()->withSignatories(2)->create();
    $first = $document->signatories()->orderBy('order')->first();

    $this->post(route('public.sign.decline', $first->token));

    expect(Activity::forSubject($document)->pluck('event'))->toContain('declined');
});

test('completing a document logs a completed event', function () {
    $document = Document::factory()->pending()->withSignatories(1)->create();
    $first = $document->signatories()->first();

    $this->post(route('public.sign.sign', $first->token), [
        'signature_data' => pngSignature(),
        'signer_name' => $first->name,
        'accept_terms' => true,
    ]);

    expect(Activity::forSubject($document)->pluck('event'))->toContain('completed');
});

test('a status only change does not create a noisy updated log', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->draft()->withSignatories(1)->create();

    $this->actingAs($user)->post(route('documents.send', $document));

    expect(Activity::forSubject($document)->pluck('event'))->not->toContain('updated');
});

test('the document show page exposes the activity timeline', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->draft()->withSignatories(1)->create();
    $this->actingAs($user)->post(route('documents.send', $document));

    $this->actingAs($user)
        ->get(route('documents.show', $document))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->has('activities')
                ->where('activities', fn ($activities) => collect($activities)->pluck('event')->contains('sent'))
        );
});
