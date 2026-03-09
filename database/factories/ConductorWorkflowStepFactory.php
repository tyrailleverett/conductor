<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Database\Factories;

use HotReloadStudios\Conductor\Enums\StepStatus;
use HotReloadStudios\Conductor\Models\ConductorWorkflow;
use HotReloadStudios\Conductor\Models\ConductorWorkflowStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConductorWorkflowStep>
 */
final class ConductorWorkflowStepFactory extends Factory
{
    protected $model = ConductorWorkflowStep::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workflow_id' => ConductorWorkflow::factory(),
            'conductor_job_id' => null,
            'name' => fake()->words(2, asText: true),
            'step_index' => 0,
            'status' => StepStatus::Pending,
            'input' => null,
            'output' => null,
            'attempts' => 0,
        ];
    }

    public function running(): static
    {
        return $this->state([
            'status' => StepStatus::Running,
            'started_at' => now(),
            'attempts' => 1,
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => StepStatus::Completed,
            'started_at' => now()->subSeconds(3),
            'completed_at' => now(),
            'duration_ms' => 3000,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => StepStatus::Failed,
            'started_at' => now()->subSecond(),
            'error_message' => 'Step failed',
            'attempts' => 1,
        ]);
    }

    public function skipped(): static
    {
        return $this->state([
            'status' => StepStatus::Skipped,
        ]);
    }
}
