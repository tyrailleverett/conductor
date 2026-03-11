<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Models\ConductorWorker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->setConductorAuth(static fn (Request $request): bool => true);
    Config::set('queue.default', 'database');
});

it('returns worker list', function (): void {
    $workers = ConductorWorker::factory()->count(2)->create();

    $response = $this->getJson('/conductor/api/workers')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(2)
        ->and($response->json('data.0.status'))->toBeIn(['idle', 'busy', 'offline']);
});

it('returns sync driver notice when using sync driver', function (): void {
    Config::set('queue.default', 'sync');

    $response = $this->getJson('/conductor/api/workers')
        ->assertOk();

    expect($response->json('sync_driver'))->toBeTrue()
        ->and($response->json('data'))->toBe([]);
});
