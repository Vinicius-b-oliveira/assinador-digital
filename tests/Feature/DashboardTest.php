<?php

use App\Models\Document;
use App\Models\Signature;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

test('guests are redirected from the dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('dashboard aggregates stats scoped to the authenticated user', function () {
    $user = User::factory()->create();

    Document::factory()->for($user)->draft()->count(2)->create();
    Document::factory()->for($user)->pending()->count(3)->create();
    Document::factory()->for($user)->completed()->create();
    Document::factory()->for($user)->cancelled()->create();

    Document::factory()->completed()->count(4)->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Dashboard', false)
                ->where('stats.total', 7)
                ->where('stats.draft', 2)
                ->where('stats.pending', 3)
                ->where('stats.completed', 1)
                ->where('stats.cancelled', 1)
                ->where('stats.completionRate', 14)
                ->has('stats.recentDocuments', 5)
        );
});

test('dashboard returns empty stats for a user without documents', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('stats.total', 0)
                ->where('stats.completionRate', 0)
                ->where('stats.signaturesCollected', 0)
                ->has('stats.recentDocuments', 0)
        );
});

test('dashboard counts signatures collected across owned documents', function () {
    $user = User::factory()->create();

    $document = Document::factory()->for($user)->pending()->withSignatories()->create();
    $signatory = $document->signatories()->first();
    Signature::factory()->create([
        'signatory_id' => $signatory->id,
        'document_id' => $document->id,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('stats.signaturesCollected', 1));
});
