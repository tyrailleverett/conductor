<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Database\Factories;

use HotReloadStudios\Conductor\Models\ConductorWorker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConductorWorker>
 */
final class ConductorWorkerFactory extends Factory
{
    protected $model = ConductorWorker::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'worker_uuid' => fake()->uuid(),
            'worker_name' => 'worker-'.fake()->randomNumber(3),
            'queue' => 'default',
            'connection' => 'database',
            'hostname' => fake()->domainName(),
            'process_id' => fake()->numberBetween(1000, 99999),
            'current_job_uuid' => null,
            'last_heartbeat_at' => now(),
        ];
    }

    public function busy(): static
    {
        return $this->state([
            'current_job_uuid' => fake()->uuid(),
        ]);
    }

    public function stale(): static
    {
        return $this->state([
            'last_heartbeat_at' => now()->subMinutes(5),
        ]);
    }
}
