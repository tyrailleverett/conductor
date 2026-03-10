<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Database\Factories;

use HotReloadStudios\Conductor\Enums\ScheduleRunStatus;
use HotReloadStudios\Conductor\Models\ConductorSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConductorSchedule>
 */
final class ConductorScheduleFactory extends Factory
{
    protected $model = ConductorSchedule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'function_class' => 'App\\Functions\\ExampleScheduledFunction',
            'display_name' => fake()->words(3, asText: true),
            'cron_expression' => '0 * * * *',
            'is_active' => true,
            'last_run_at' => null,
            'next_run_at' => now()->addHour(),
            'last_run_status' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }

    public function lastRunFailed(): static
    {
        return $this->state([
            'last_run_at' => now()->subHour(),
            'last_run_status' => ScheduleRunStatus::Failed,
        ]);
    }
}
