<?php

use App\Models\Document;
use App\Models\Signature;
use App\Pulse\Cards\DatabaseHealth;
use App\Pulse\Cards\DocumentsByStatus;
use App\Pulse\Cards\SignaturesCollected;

test('database health reports the connection online with latency', function () {
    $health = (new DatabaseHealth)->health();

    expect($health['online'])->toBeTrue()
        ->and($health['connection'])->toBe(config('database.default'))
        ->and($health['latency'])->toBeFloat()
        ->and($health['latency'])->toBeGreaterThanOrEqual(0.0);
});

test('documents by status counts every status and computes the completion rate', function () {
    Document::factory()->completed()->count(3)->create();
    Document::factory()->pending()->create();

    $card = new DocumentsByStatus;

    expect($card->statuses())->toBe([
        'draft' => 0,
        'pending' => 1,
        'completed' => 3,
        'cancelled' => 0,
    ]);

    expect($card->completionRate())->toBe(75.0);
});

test('completion rate is zero without documents', function () {
    expect((new DocumentsByStatus)->completionRate())->toBe(0.0);
});

test('signatures collected groups the last seven days without gaps', function () {
    Signature::factory()->count(2)->create(['signed_at' => now()]);
    Signature::factory()->create(['signed_at' => now()->subDay()]);
    Signature::factory()->create(['signed_at' => now()->subDays(30)]);

    $perDay = (new SignaturesCollected)->perDay();

    expect($perDay)->toHaveCount(7)
        ->and($perDay->sum('total'))->toBe(3)
        ->and($perDay->last()['total'])->toBe(2);
});
