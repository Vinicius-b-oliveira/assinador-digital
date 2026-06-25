<?php

use App\Models\Document;
use App\Models\Signatory;
use App\Models\Signature;

test('a signature belongs to a signatory and a document', function () {
    $document = Document::factory()->pending()->create();
    $signatory = Signatory::factory()->for($document)->signed()->create();
    $signature = Signature::factory()
        ->for($signatory)
        ->for($document)
        ->create();

    expect($signature->signatory->is($signatory))->toBeTrue()
        ->and($signature->document->is($document))->toBeTrue()
        ->and($signature->signed_at)->not->toBeNull();
});

test('a signatory has one signature', function () {
    $signatory = Signatory::factory()->signed()->create();
    $signature = Signature::factory()
        ->for($signatory)
        ->for($signatory->document)
        ->create();

    expect($signatory->refresh()->signature->is($signature))->toBeTrue();
});

test('a document exposes its collected signatures', function () {
    $document = Document::factory()->pending()->create();
    $signatory = Signatory::factory()->for($document)->signed()->create();
    Signature::factory()->for($signatory)->for($document)->create();

    expect($document->refresh()->signatures)->toHaveCount(1);
});
