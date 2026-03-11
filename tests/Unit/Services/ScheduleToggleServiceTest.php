<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Models\ConductorSchedule;
use HotReloadStudios\Conductor\Services\ScheduleToggleService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('toggles an active schedule to inactive', function (): void {
    $schedule = ConductorSchedule::factory()->create(['is_active' => true]);

    $service = new ScheduleToggleService();
    $result = $service->toggle($schedule);

    expect($result->is_active)->toBeFalse();
    expect($schedule->fresh()?->is_active)->toBeFalse();
});

it('toggles an inactive schedule to active', function (): void {
    $schedule = ConductorSchedule::factory()->create(['is_active' => false]);

    $service = new ScheduleToggleService();
    $result = $service->toggle($schedule);

    expect($result->is_active)->toBeTrue();
    expect($schedule->fresh()?->is_active)->toBeTrue();
});
