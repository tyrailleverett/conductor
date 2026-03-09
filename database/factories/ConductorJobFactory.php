<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Database\Factories;

use HotReloadStudios\Conductor\Enums\JobStatus;
use HotReloadStudios\Conductor\Models\ConductorJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConductorJob>
 */
final class ConductorJobFactory extends Factory
{
    protected $model = ConductorJob::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'class' => 'App\\Jobs\\ExampleJob',
            'display_name' => fake()->words(3, asText: true),
            'status' => JobStatus::Pending,
            'queue' => 'default',
            'connection' => 'database',
            'tags' => [],
            'payload' => [],
            'attempts' => 0,
            'max_attempts' => 3,
        ];
    }

    public function running(): static
    {
        return $this->state([
            'status' => JobStatus::Running,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => JobStatus::Completed,
            'started_at' => now()->subSeconds(5),
            'completed_at' => now(),
            'duration_ms' => 5000,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => JobStatus::Failed,
            'started_at' => now()->subSeconds(2),
            'failed_at' => now(),
            'error_message' => 'Job failed',
            'attempts' => 1,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => JobStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function cancellationRequested(): static
    {
        return $this->state([
            'status' => JobStatus::CancellationRequested,
            'started_at' => now(),
            'cancellable_at' => now(),
            'cancellation_requested_at' => now(),
        ]);
    }

    /** @param array<string> $tags */
    public function withTags(array $tags): static
    {
        return $this->state([
            'tags' => $tags,
        ]);
    }
}
