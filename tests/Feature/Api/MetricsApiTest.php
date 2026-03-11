<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Enums\MetricType;
use HotReloadStudios\Conductor\Models\ConductorMetricSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->setConductorAuth(static fn (Request $request): bool => true);
});

it('returns metrics for default 24h window', function (): void {
    ConductorMetricSnapshot::factory()->create([
        'metric' => MetricType::Throughput,
        'recorded_at' => now()->subHours(12),
    ]);
    ConductorMetricSnapshot::factory()->create([
        'metric' => MetricType::FailureRate,
        'recorded_at' => now()->subHours(6),
    ]);

    $response = $this->getJson('/conductor/api/metrics')
        ->assertOk();

    expect($response->json('window'))->toBe('24h')
        ->and($response->json('throughput'))->toHaveCount(1)
        ->and($response->json('failure_rate'))->toHaveCount(1);
});

it('accepts window parameter', function (): void {
    ConductorMetricSnapshot::factory()->create([
        'metric' => MetricType::Throughput,
        'recorded_at' => now()->subMinutes(30),
    ]);
    ConductorMetricSnapshot::factory()->create([
        'metric' => MetricType::Throughput,
        'recorded_at' => now()->subHours(2),
    ]);

    $response = $this->getJson('/conductor/api/metrics?window=1h')
        ->assertOk();

    expect($response->json('window'))->toBe('1h')
        ->and($response->json('throughput'))->toHaveCount(1);
});

it('groups queue_depth by queue', function (): void {
    ConductorMetricSnapshot::factory()->create([
        'metric' => MetricType::QueueDepth,
        'queue' => 'default',
        'value' => 10,
        'recorded_at' => now()->subHour(),
    ]);
    ConductorMetricSnapshot::factory()->create([
        'metric' => MetricType::QueueDepth,
        'queue' => 'high',
        'value' => 3,
        'recorded_at' => now()->subHour(),
    ]);

    $response = $this->getJson('/conductor/api/metrics')
        ->assertOk();

    expect($response->json('queue_depth'))->toHaveKey('default')
        ->and($response->json('queue_depth'))->toHaveKey('high')
        ->and($response->json('queue_depth.default'))->toHaveCount(1)
        ->and($response->json('queue_depth.high'))->toHaveCount(1);
});
