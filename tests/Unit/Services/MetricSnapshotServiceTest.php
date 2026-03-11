<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Enums\MetricType;
use HotReloadStudios\Conductor\Models\ConductorJob;
use HotReloadStudios\Conductor\Models\ConductorMetricSnapshot;
use HotReloadStudios\Conductor\Services\MetricSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('captures throughput metric', function (): void {
    ConductorJob::factory()->count(5)->completed()->create();

    $service = new MetricSnapshotService();
    $service->capture();

    $snapshot = ConductorMetricSnapshot::where('metric', MetricType::Throughput)->first();

    expect($snapshot)->not->toBeNull()
        ->and((float) $snapshot->value)->toBe(5.0);
});

it('captures failure rate metric', function (): void {
    ConductorJob::factory()->count(3)->completed()->create();
    ConductorJob::factory()->count(2)->failed()->create();

    $service = new MetricSnapshotService();
    $service->capture();

    $snapshot = ConductorMetricSnapshot::where('metric', MetricType::FailureRate)->first();

    expect($snapshot)->not->toBeNull()
        ->and(round((float) $snapshot->value, 2))->toBe(0.40);
});

it('captures queue depth per queue', function (): void {
    ConductorJob::factory()->count(3)->create(['queue' => 'default']);
    ConductorJob::factory()->count(2)->create(['queue' => 'high']);

    $service = new MetricSnapshotService();
    $service->capture();

    $defaultSnapshot = ConductorMetricSnapshot::where('metric', MetricType::QueueDepth)
        ->where('queue', 'default')
        ->first();
    $highSnapshot = ConductorMetricSnapshot::where('metric', MetricType::QueueDepth)
        ->where('queue', 'high')
        ->first();

    expect($defaultSnapshot)->not->toBeNull()
        ->and((float) $defaultSnapshot->value)->toBe(3.0)
        ->and($highSnapshot)->not->toBeNull()
        ->and((float) $highSnapshot->value)->toBe(2.0);
});

it('handles zero jobs gracefully', function (): void {
    $service = new MetricSnapshotService();
    $service->capture();

    $throughput = ConductorMetricSnapshot::where('metric', MetricType::Throughput)->first();
    $failureRate = ConductorMetricSnapshot::where('metric', MetricType::FailureRate)->first();

    expect($throughput)->not->toBeNull()
        ->and((float) $throughput->value)->toBe(0.0)
        ->and($failureRate)->not->toBeNull()
        ->and((float) $failureRate->value)->toBe(0.0);
});
