<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Listeners\WorkerHeartbeatListener;
use HotReloadStudios\Conductor\Models\ConductorWorker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\Looping;
use Illuminate\Queue\Events\WorkerStopping;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $property = new ReflectionProperty(WorkerHeartbeatListener::class, 'currentWorker');
    $property->setValue(null, null);
});

afterEach(function (): void {
    $property = new ReflectionProperty(WorkerHeartbeatListener::class, 'currentWorker');
    $property->setValue(null, null);
});

it('creates a worker record on first queue loop', function (): void {
    event(new Looping('database', 'default'));

    expect(ConductorWorker::count())->toBe(1);
});

it('updates heartbeat on each loop iteration', function (): void {
    event(new Looping('database', 'default'));

    $firstHeartbeat = ConductorWorker::first()->last_heartbeat_at->copy();

    // Advance time slightly and fire another loop
    $this->travel(2)->seconds();

    event(new Looping('database', 'default'));

    $worker = ConductorWorker::first();
    expect($worker->last_heartbeat_at->isAfter($firstHeartbeat))->toBeTrue();
});

it('sets current job uuid on job processing', function (): void {
    // Ensure worker is registered first
    event(new Looping('database', 'default'));

    $mockJob = Mockery::mock(Illuminate\Contracts\Queue\Job::class);
    $mockJob->allows('getQueue')->andReturn('default');
    $mockJob->allows('payload')->andReturn(['uuid' => 'test-job-uuid-123']);

    event(new JobProcessing('database', $mockJob));

    $worker = ConductorWorker::first();
    expect($worker->current_job_uuid)->toBe('test-job-uuid-123');
});

it('clears current job on job completion', function (): void {
    event(new Looping('database', 'default'));

    $mockJob = Mockery::mock(Illuminate\Contracts\Queue\Job::class);
    $mockJob->allows('getQueue')->andReturn('default');
    $mockJob->allows('payload')->andReturn(['uuid' => 'test-job-uuid-456']);

    event(new JobProcessing('database', $mockJob));
    event(new JobProcessed('database', $mockJob));

    $worker = ConductorWorker::first();
    expect($worker->current_job_uuid)->toBeNull();
});

it('clears current job on job failure', function (): void {
    event(new Looping('database', 'default'));

    $mockJob = Mockery::mock(Illuminate\Contracts\Queue\Job::class);
    $mockJob->allows('getQueue')->andReturn('default');
    $mockJob->allows('payload')->andReturn(['uuid' => 'test-job-uuid-789']);

    event(new JobProcessing('database', $mockJob));
    event(new JobFailed('database', $mockJob, new RuntimeException('fail')));

    $worker = ConductorWorker::first();
    expect($worker->current_job_uuid)->toBeNull();
});

it('deletes worker record on worker stopping', function (): void {
    event(new Looping('database', 'default'));

    expect(ConductorWorker::count())->toBe(1);

    event(new WorkerStopping(0));

    expect(ConductorWorker::count())->toBe(0);
});
