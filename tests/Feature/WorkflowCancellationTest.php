<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Enums\StepStatus;
use HotReloadStudios\Conductor\Enums\WorkflowStatus;
use HotReloadStudios\Conductor\Models\ConductorWorkflow;
use HotReloadStudios\Conductor\Models\ConductorWorkflowStep;
use HotReloadStudios\Conductor\Services\WorkflowCancellationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('cancels a pending workflow', function (): void {
    $workflow = ConductorWorkflow::factory()->create([
        'class' => 'App\\Workflows\\TestWorkflow',
        'status' => WorkflowStatus::Pending,
    ]);

    ConductorWorkflowStep::factory()->create(['workflow_id' => $workflow->id, 'step_index' => 0]);
    ConductorWorkflowStep::factory()->create(['workflow_id' => $workflow->id, 'step_index' => 1]);
    ConductorWorkflowStep::factory()->create(['workflow_id' => $workflow->id, 'step_index' => 2]);

    $service = app(WorkflowCancellationService::class);
    $service->cancel($workflow);

    $workflow->refresh();

    expect($workflow->status)->toBe(WorkflowStatus::Cancelled);

    $steps = ConductorWorkflowStep::where('workflow_id', $workflow->id)->get();
    $steps->each(fn ($step): mixed => expect($step->status)->toBe(StepStatus::Skipped));
});

it('cancels a running workflow and skips remaining steps', function (): void {
    $workflow = ConductorWorkflow::factory()->running()->create([
        'class' => 'App\\Workflows\\TestWorkflow',
    ]);

    ConductorWorkflowStep::factory()->completed()->create(['workflow_id' => $workflow->id, 'step_index' => 0]);
    ConductorWorkflowStep::factory()->running()->create(['workflow_id' => $workflow->id, 'step_index' => 1]);
    ConductorWorkflowStep::factory()->create(['workflow_id' => $workflow->id, 'step_index' => 2]);

    $service = app(WorkflowCancellationService::class);
    $service->cancel($workflow);

    $workflow->refresh();

    expect($workflow->status)->toBe(WorkflowStatus::Cancelled);

    $step0 = ConductorWorkflowStep::where('workflow_id', $workflow->id)->where('step_index', 0)->first();
    $step1 = ConductorWorkflowStep::where('workflow_id', $workflow->id)->where('step_index', 1)->first();
    $step2 = ConductorWorkflowStep::where('workflow_id', $workflow->id)->where('step_index', 2)->first();

    expect($step0->status)->toBe(StepStatus::Completed)
        ->and($step1->status)->toBe(StepStatus::Running)
        ->and($step2->status)->toBe(StepStatus::Skipped);
});

it('rejects cancellation of a terminal workflow', function (): void {
    $workflow = ConductorWorkflow::factory()->completed()->create([
        'class' => 'App\\Workflows\\TestWorkflow',
    ]);

    $service = app(WorkflowCancellationService::class);

    expect(fn () => $service->cancel($workflow))->toThrow(InvalidArgumentException::class);
});
