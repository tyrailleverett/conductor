<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Models\ConductorEvent;
use HotReloadStudios\Conductor\Models\ConductorEventRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->setConductorAuth(static fn (Request $request): bool => true);
});

it('returns a paginated list of events', function (): void {
    ConductorEvent::factory()->count(5)->create();

    $this->getJson('/conductor/api/events')
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonStructure(['data', 'meta', 'links']);
});

it('returns event detail with runs', function (): void {
    $event = ConductorEvent::factory()->create();
    ConductorEventRun::factory()->count(2)->create(['event_id' => $event->id]);

    $response = $this->getJson("/conductor/api/events/{$event->uuid}")
        ->assertOk();

    expect($response->json('data.runs'))->toHaveCount(2)
        ->and($response->json('data.id'))->toBe($event->uuid);
});
