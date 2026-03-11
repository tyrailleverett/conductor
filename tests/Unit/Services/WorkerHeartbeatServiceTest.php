<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Models\ConductorWorker;
use HotReloadStudios\Conductor\Services\WorkerHeartbeatService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers a new worker', function (): void {
    $service = new WorkerHeartbeatService();

    $worker = $service->register(
        workerName: 'test-worker',
        queue: 'default',
        connection: 'sync',
    );

    expect(ConductorWorker::count())->toBe(1)
        ->and($worker->worker_name)->toBe('test-worker')
        ->and($worker->queue)->toBe('default')
        ->and($worker->connection)->toBe('sync')
        ->and($worker->hostname)->toBe(gethostname() ?: '')
        ->and($worker->process_id)->toBe((int) getmypid())
        ->and($worker->last_heartbeat_at)->not->toBeNull();
});

it('updates heartbeat timestamp', function (): void {
    $worker = ConductorWorker::factory()->create([
        'last_heartbeat_at' => now()->subSeconds(10),
    ]);

    $originalTimestamp = $worker->last_heartbeat_at->copy();

    $service = new WorkerHeartbeatService();
    $service->heartbeat($worker);

    $worker->refresh();

    expect($worker->last_heartbeat_at->isAfter($originalTimestamp))->toBeTrue();
});

it('sets current job uuid during heartbeat', function (): void {
    $worker = ConductorWorker::factory()->create();
    $service = new WorkerHeartbeatService();

    $service->heartbeat($worker, 'uuid-123');

    $worker->refresh();

    expect($worker->current_job_uuid)->toBe('uuid-123');
});

it('clears current job uuid', function (): void {
    $worker = ConductorWorker::factory()->busy()->create();
    $service = new WorkerHeartbeatService();

    $service->clearJob($worker);

    $worker->refresh();

    expect($worker->current_job_uuid)->toBeNull();
});
