<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Enums\MetricType;

it('has the expected cases', function (): void {
    expect(MetricType::cases())->toHaveCount(3)
        ->and(MetricType::Throughput->value)->toBe('throughput')
        ->and(MetricType::FailureRate->value)->toBe('failure_rate')
        ->and(MetricType::QueueDepth->value)->toBe('queue_depth');
});
