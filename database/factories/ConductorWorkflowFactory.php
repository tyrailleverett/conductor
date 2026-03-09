<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Database\Factories;

use HotReloadStudios\Conductor\Enums\WorkflowStatus;
use HotReloadStudios\Conductor\Models\ConductorWorkflow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConductorWorkflow>
 */
final class ConductorWorkflowFactory extends Factory
{
    protected $model = ConductorWorkflow::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'class' => 'App\\Workflows\\ExampleWorkflow',
            'display_name' => fake()->words(3, asText: true),
            'status' => WorkflowStatus::Pending,
            'input' => [],
            'output' => null,
            'current_step_index' => 0,
        ];
    }

    public function running(): static
    {
        return $this->state([
            'status' => WorkflowStatus::Running,
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => WorkflowStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => WorkflowStatus::Failed,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => WorkflowStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
