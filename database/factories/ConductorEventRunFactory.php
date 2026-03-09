<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Database\Factories;

use HotReloadStudios\Conductor\Enums\EventRunStatus;
use HotReloadStudios\Conductor\Models\ConductorEvent;
use HotReloadStudios\Conductor\Models\ConductorEventRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConductorEventRun>
 */
final class ConductorEventRunFactory extends Factory
{
    protected $model = ConductorEventRun::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => ConductorEvent::factory(),
            'conductor_job_id' => null,
            'function_class' => 'App\\Functions\\ExampleFunction',
            'status' => EventRunStatus::Pending,
            'error_message' => null,
            'attempts' => 0,
        ];
    }

    public function running(): static
    {
        return $this->state([
            'status' => EventRunStatus::Running,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => EventRunStatus::Completed,
            'started_at' => now()->subSeconds(2),
            'completed_at' => now(),
            'duration_ms' => 2000,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => EventRunStatus::Failed,
            'error_message' => 'Function failed',
            'attempts' => 1,
        ]);
    }
}
