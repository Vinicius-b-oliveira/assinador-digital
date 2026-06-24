<?php

use App\Enums\DocumentStatus;
use App\Enums\SignatoryStatus;
use App\Models\Document;
use App\Models\Signatory;
use Illuminate\Support\Str;

test('a signatory belongs to a document and casts its status', function () {
    $document = Document::factory()->create();
    $signatory = Signatory::factory()->for($document)->create();

    expect($signatory->document->is($document))->toBeTrue()
        ->and($signatory->status)->toBe(SignatoryStatus::Pending)
        ->and(Str::isUuid($signatory->token))->toBeTrue();
});

test('signatory tokens are generated when missing', function () {
    $signatory = Signatory::factory()->create(['token' => null]);

    expect(Str::isUuid($signatory->token))->toBeTrue();
});

test('pending scope returns only pending signatories', function () {
    Signatory::factory()->pending()->create();
    Signatory::factory()->signed()->create();
    Signatory::factory()->declined()->create();

    expect(Signatory::pending()->pluck('status')->all())->toBe([SignatoryStatus::Pending]);
});

test('document factory can create an ordered signing flow', function () {
    $document = Document::factory()->readyToSign()->create();

    expect($document->status)->toBe(DocumentStatus::Pending)
        ->and($document->signatories)->toHaveCount(2)
        ->and($document->signatories->pluck('order')->all())->toBe([1, 2]);
});
