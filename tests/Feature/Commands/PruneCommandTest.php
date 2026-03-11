<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Models\ConductorEvent;
use HotReloadStudios\Conductor\Models\ConductorEventRun;
use HotReloadStudios\Conductor\Models\ConductorJob;
use HotReloadStudios\Conductor\Models\ConductorJobLog;
use HotReloadStudios\Conductor\Models\ConductorMetricSnapshot;
use HotReloadStudios\Conductor\Models\ConductorWebhookLog;
use HotReloadStudios\Conductor\Models\ConductorWorker;
use HotReloadStudios\Conductor\Models\ConductorWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prunes records older than configured days', function (): void {
    ConductorJob::factory()->create(['created_at' => now()->subDays(10)]);
    ConductorWorkflow::factory()->create(['created_at' => now()->subDays(10)]);
    ConductorEvent::factory()->create(['dispatched_at' => now()->subDays(10)]);
    ConductorMetricSnapshot::factory()->create(['recorded_at' => now()->subDays(10)]);
    ConductorWorker::factory()->create(['last_heartbeat_at' => now()->subDays(10)]);

    $this->artisan('conductor:prune')->assertExitCode(0);

    expect(ConductorJob::count())->toBe(0)
        ->and(ConductorWorkflow::count())->toBe(0)
        ->and(ConductorEvent::count())->toBe(0)
        ->and(ConductorMetricSnapshot::count())->toBe(0)
        ->and(ConductorWorker::count())->toBe(0);
});

it('preserves recent records', function (): void {
    ConductorJob::factory()->create(['created_at' => now()->subDays(2)]);
    ConductorWorkflow::factory()->create(['created_at' => now()->subDays(2)]);

    $this->artisan('conductor:prune')->assertExitCode(0);

    expect(ConductorJob::count())->toBe(1)
        ->and(ConductorWorkflow::count())->toBe(1);
});

it('accepts a custom days option', function (): void {
    ConductorJob::factory()->create(['created_at' => now()->subDays(2)]);
    ConductorJob::factory()->create(['created_at' => now()->subHours(1)]);

    $this->artisan('conductor:prune', ['--days' => 1])->assertExitCode(0);

    expect(ConductorJob::count())->toBe(1);
});

it('cascade deletes child records with jobs', function (): void {
    $job = ConductorJob::factory()->create(['created_at' => now()->subDays(10)]);
    ConductorJobLog::factory()->create(['job_id' => $job->id]);

    $this->artisan('conductor:prune')->assertExitCode(0);

    expect(ConductorJob::count())->toBe(0)
        ->and(ConductorJobLog::count())->toBe(0);
});

it('cascade deletes event runs with events', function (): void {
    $event = ConductorEvent::factory()->create(['dispatched_at' => now()->subDays(10)]);
    ConductorEventRun::factory()->create(['event_id' => $event->id]);

    $this->artisan('conductor:prune')->assertExitCode(0);

    expect(ConductorEvent::count())->toBe(0)
        ->and(ConductorEventRun::count())->toBe(0);
});

it('prunes webhook logs older than configured days', function (): void {
    ConductorWebhookLog::factory()->create(['received_at' => now()->subDays(10)]);
    ConductorWebhookLog::factory()->create(['received_at' => now()->subDays(2)]);

    $this->artisan('conductor:prune')->assertExitCode(0);

    expect(ConductorWebhookLog::count())->toBe(1);
});
