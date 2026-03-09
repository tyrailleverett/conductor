<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Database\Factories;

use HotReloadStudios\Conductor\Enums\LogLevel;
use HotReloadStudios\Conductor\Models\ConductorJob;
use HotReloadStudios\Conductor\Models\ConductorJobLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConductorJobLog>
 */
final class ConductorJobLogFactory extends Factory
{
    protected $model = ConductorJobLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_id' => ConductorJob::factory(),
            'level' => LogLevel::Info,
            'message' => fake()->sentence(),
            'logged_at' => now(),
        ];
    }
}
