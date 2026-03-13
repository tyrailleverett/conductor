<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Models\ConductorWebhookLog;
use HotReloadStudios\Conductor\Models\ConductorWebhookSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->setConductorAuth(static fn (Request $request): bool => true);
});

it('returns webhook sources and recent logs', function (): void {
    ConductorWebhookSource::factory()->create([
        'source' => 'demo',
        'function_class' => 'App\\Conductor\\Functions\\SeededDemoWebhookFunction',
    ]);

    ConductorWebhookLog::factory()->count(2)->create([
        'source' => 'demo',
    ]);

    $response = $this->getJson('/conductor/api/webhooks')
        ->assertOk();

    expect($response->json('data.sources'))->toHaveCount(1)
        ->and($response->json('data.logs'))->toHaveCount(2)
        ->and($response->json('data.sources.0.source'))->toBe('demo');
});

it('filters webhook logs by source', function (): void {
    ConductorWebhookSource::factory()->create(['source' => 'demo']);
    ConductorWebhookSource::factory()->create(['source' => 'github']);

    ConductorWebhookLog::factory()->create(['source' => 'demo']);
    ConductorWebhookLog::factory()->create(['source' => 'github']);

    $response = $this->getJson('/conductor/api/webhooks?source=demo')
        ->assertOk();

    expect($response->json('data.logs'))->toHaveCount(1)
        ->and($response->json('data.logs.0.source'))->toBe('demo');
});
