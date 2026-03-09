<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Database\Factories;

use HotReloadStudios\Conductor\Enums\MetricType;
use HotReloadStudios\Conductor\Models\ConductorMetricSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConductorMetricSnapshot>
 */
final class ConductorMetricSnapshotFactory extends Factory
{
    protected $model = ConductorMetricSnapshot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'metric' => MetricType::Throughput,
            'queue' => null,
            'value' => fake()->randomFloat(2, 0, 1000),
            'recorded_at' => now(),
        ];
    }
}
