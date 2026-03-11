<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Models\ConductorSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->setConductorAuth(static fn (Request $request): bool => true);
});

it('returns all schedules', function (): void {
    ConductorSchedule::factory()->create(['function_class' => 'App\\Functions\\ScheduleOne']);
    ConductorSchedule::factory()->create(['function_class' => 'App\\Functions\\ScheduleTwo']);
    ConductorSchedule::factory()->create(['function_class' => 'App\\Functions\\ScheduleThree']);

    $response = $this->getJson('/conductor/api/schedules')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(3);
});

it('toggles a schedule', function (): void {
    $schedule = ConductorSchedule::factory()->create(['is_active' => true]);

    $response = $this->postJson("/conductor/api/schedules/{$schedule->id}/toggle")
        ->assertOk();

    expect($response->json('data.is_active'))->toBeFalse();

    $this->postJson("/conductor/api/schedules/{$schedule->id}/toggle")
        ->assertOk()
        ->assertJsonPath('data.is_active', true);
});
