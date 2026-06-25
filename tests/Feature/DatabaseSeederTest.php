<?php

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\Signature;
use App\Models\User;

test('the seeder creates the admin dev user with the default password', function () {
    $this->seed();

    $dev = User::where('email', 'dev@dev.com')->first();

    expect($dev)->not->toBeNull()
        ->and($dev->is_admin)->toBeTrue()
        ->and(Hash::check('password', $dev->password))->toBeTrue();
});

test('the seeder creates documents covering every status', function () {
    $this->seed();

    foreach (DocumentStatus::cases() as $status) {
        expect(Document::where('status', $status)->exists())->toBeTrue();
    }
});

test('the seeder records signatures for signed signatories', function () {
    $this->seed();

    expect(Signature::count())->toBeGreaterThan(0)
        ->and(Document::completed()->whereHas('signatures')->exists())->toBeTrue();
});
