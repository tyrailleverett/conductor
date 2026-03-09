<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Enums\WorkerStatus;
use HotReloadStudios\Conductor\Models\ConductorWorker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('derives idle status when heartbeat is fresh and no job', function (): void {
    $worker = ConductorWorker::factory()->create([
        'current_job_uuid' => null,
        'last_heartbeat_at' => now(),
    ]);

    expect($worker->derivedStatus())->toBe(WorkerStatus::Idle);
});

it('derives busy status when current_job_uuid is set', function (): void {
    $worker = ConductorWorker::factory()->busy()->create();

    expect($worker->derivedStatus())->toBe(WorkerStatus::Busy);
});

it('derives offline status when heartbeat is stale', function (): void {
    $workerTimeout = (int) config('conductor.worker_timeout');
    $worker = ConductorWorker::factory()->create([
        'current_job_uuid' => null,
        'last_heartbeat_at' => now()->subSeconds($workerTimeout + 1),
    ]);

    expect($worker->derivedStatus())->toBe(WorkerStatus::Offline);
});
