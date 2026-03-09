<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates all conductor tables', function (): void {
    expect(Schema::hasTable('conductor_jobs'))->toBeTrue()
        ->and(Schema::hasTable('conductor_job_logs'))->toBeTrue()
        ->and(Schema::hasTable('conductor_workflows'))->toBeTrue()
        ->and(Schema::hasTable('conductor_workflow_steps'))->toBeTrue()
        ->and(Schema::hasTable('conductor_events'))->toBeTrue()
        ->and(Schema::hasTable('conductor_event_runs'))->toBeTrue()
        ->and(Schema::hasTable('conductor_schedules'))->toBeTrue()
        ->and(Schema::hasTable('conductor_workers'))->toBeTrue()
        ->and(Schema::hasTable('conductor_webhook_sources'))->toBeTrue()
        ->and(Schema::hasTable('conductor_webhook_logs'))->toBeTrue()
        ->and(Schema::hasTable('conductor_metric_snapshots'))->toBeTrue();
});

it('creates expected columns on conductor_jobs', function (): void {
    expect(Schema::hasColumns('conductor_jobs', [
        'id',
        'uuid',
        'class',
        'display_name',
        'status',
        'queue',
        'connection',
        'tags',
        'payload',
        'attempts',
        'max_attempts',
        'cancellable_at',
        'cancellation_requested_at',
        'cancelled_at',
        'started_at',
        'completed_at',
        'failed_at',
        'duration_ms',
        'error_message',
        'stack_trace',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

it('creates expected indexes on conductor_jobs', function (): void {
    $indexes = collect(Schema::getIndexes('conductor_jobs'));

    $hasUuidUnique = $indexes->contains(fn (array $index) => in_array('uuid', $index['columns']) && $index['unique']);
    $hasStatusQueueIndex = $indexes->contains(fn (array $index) => in_array('status', $index['columns']) && in_array('queue', $index['columns']));

    expect($hasUuidUnique)->toBeTrue()
        ->and($hasStatusQueueIndex)->toBeTrue();
});
