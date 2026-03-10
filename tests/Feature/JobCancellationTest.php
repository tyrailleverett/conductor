<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Enums\JobStatus;
use HotReloadStudios\Conductor\Models\ConductorJob;
use HotReloadStudios\Conductor\Services\JobCancellationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('cancels a pending job', function (): void {
    $conductorJob = ConductorJob::factory()->create(['status' => JobStatus::Pending]);

    $service = app(JobCancellationService::class);
    $service->cancel($conductorJob);

    $conductorJob->refresh();

    expect($conductorJob->status)->toBe(JobStatus::Cancelled)
        ->and($conductorJob->cancelled_at)->not->toBeNull();
});

it('requests cancellation for a running cancellable job', function (): void {
    $conductorJob = ConductorJob::factory()->running()->create([
        'cancellable_at' => now(),
    ]);

    $service = app(JobCancellationService::class);
    $service->cancel($conductorJob);

    $conductorJob->refresh();

    expect($conductorJob->status)->toBe(JobStatus::CancellationRequested);
});

it('rejects cancellation for a running non-cancellable job', function (): void {
    $conductorJob = ConductorJob::factory()->running()->create([
        'cancellable_at' => null,
    ]);

    $service = app(JobCancellationService::class);

    expect(fn () => $service->cancel($conductorJob))->toThrow(LogicException::class);
});

it('rejects cancellation for a terminal job', function (): void {
    $conductorJob = ConductorJob::factory()->completed()->create();

    $service = app(JobCancellationService::class);

    expect(fn () => $service->cancel($conductorJob))->toThrow(InvalidArgumentException::class);
});
