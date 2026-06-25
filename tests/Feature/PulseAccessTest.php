<?php

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->withoutVite();
});

test('the database cache round-trips objects so pulse cards can render', function () {
    Cache::store('database')->put('pulse-serialization-check', collect(['ok']), 60);

    expect(Cache::store('database')->get('pulse-serialization-check'))
        ->toBeInstanceOf(Collection::class);
});

test('guests are redirected to login from the pulse dashboard', function () {
    $this->get('/pulse')->assertRedirect(route('login'));
});

test('authenticated non-admin users are forbidden from the pulse dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get('/pulse')
        ->assertForbidden();
});

test('admin users can access the pulse dashboard', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/pulse')
        ->assertOk();
});
