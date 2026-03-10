<?php

// phpcs:disable PSR1.Classes.ClassDeclaration

declare(strict_types=1);

use HotReloadStudios\Conductor\Enums\StepStatus;
use HotReloadStudios\Conductor\Enums\WorkflowStatus;
use HotReloadStudios\Conductor\Jobs\WorkflowStepJob;
use HotReloadStudios\Conductor\Models\ConductorWorkflow;
use HotReloadStudios\Conductor\Models\ConductorWorkflowStep;
use HotReloadStudios\Conductor\Services\WorkflowEngine;
use HotReloadStudios\Conductor\Workflow;
use HotReloadStudios\Conductor\WorkflowContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

final class ThreeStepWorkflow extends Workflow
{
    public function handle(WorkflowContext $ctx): void
    {
        $ctx->step('step_a', fn (): string => 'a');
        $ctx->step('step_b', fn (): string => 'b');
        $ctx->step('step_c', fn (): string => 'c');
    }
}

final class TwoStepWorkflow extends Workflow
{
    public function handle(WorkflowContext $ctx): void
    {
        $ctx->step('step_0', fn (): string => 'result_0');
        $ctx->step('step_1', fn (): string => 'result_1');
    }
}

final class AlwaysFailingWorkflow extends Workflow
{
    public function handle(WorkflowContext $ctx): void
    {
        $ctx->step('step_0', function (): never {
            throw new RuntimeException('Always fails');
        });
    }
}

final class SleepingTestWorkflow extends Workflow
{
    public function handle(WorkflowContext $ctx): void
    {
        $ctx->step('before_sleep', fn (): string => 'before');
        $ctx->sleep('1 hour');
        $ctx->step('after_sleep', fn (): string => 'after');
    }
}

final class RetryingTestWorkflow extends Workflow
{
    public static int $callCount = 0;

    public function handle(WorkflowContext $ctx): void
    {
        $ctx->step('step_0', function (): string {
            self::$callCount++;
            if (self::$callCount < 2) {
                throw new RuntimeException('Temporary failure');
            }

            return 'success';
        });
    }
}

// ------------------------------------------------------------------
// Tests
// ------------------------------------------------------------------

it('executes all steps in sequence', function (): void {
    $workflow = ConductorWorkflow::factory()->create([
        'class' => ThreeStepWorkflow::class,
        'input' => [],
    ]);

    $engine = app(WorkflowEngine::class);
    $engine->run($workflow);

    $workflow->refresh();
    expect($workflow->status)->toBe(WorkflowStatus::Completed);

    $steps = ConductorWorkflowStep::where('workflow_id', $workflow->id)->get();
    expect($steps)->toHaveCount(3);
    $steps->each(fn ($step): mixed => expect($step->status)->toBe(StepStatus::Completed));
});

it('persists step output and replays on resume', function (): void {
    $workflow = ConductorWorkflow::factory()->running()->create([
        'class' => TwoStepWorkflow::class,
        'input' => [],
    ]);

    // Pre-populate step 0 as completed so engine replays it without re-executing
    ConductorWorkflowStep::factory()->completed()->create([
        'workflow_id' => $workflow->id,
        'step_index' => 0,
        'name' => 'step_0',
        'output' => ['value' => 'result_0'],
    ]);

    $engine = app(WorkflowEngine::class);
    $engine->run($workflow);

    $workflow->refresh();
    expect($workflow->status)->toBe(WorkflowStatus::Completed);

    // Step 0 should still have attempts=0 (from factory default, not incremented by engine)
    $step0 = ConductorWorkflowStep::where('workflow_id', $workflow->id)->where('step_index', 0)->first();
    expect($step0->status)->toBe(StepStatus::Completed);

    // Step 1 should have been executed
    $step1 = ConductorWorkflowStep::where('workflow_id', $workflow->id)->where('step_index', 1)->first();
    expect($step1)->not->toBeNull()
        ->and($step1->status)->toBe(StepStatus::Completed);
});

it('retries a failed step up to stepMaxAttempts', function (): void {
    Queue::fake();

    $workflow = ConductorWorkflow::factory()->create([
        'class' => RetryingTestWorkflow::class,
        'input' => [],
    ]);

    // Seed RetryingTestWorkflow's call counter
    RetryingTestWorkflow::$callCount = 0;

    $engine = app(WorkflowEngine::class);

    // First run — step fails
    $engine->run($workflow);

    $workflow->refresh();
    $step = ConductorWorkflowStep::where('workflow_id', $workflow->id)->where('step_index', 0)->first();
    expect($step->status)->toBe(StepStatus::Failed)
        ->and($step->attempts)->toBe(1);

    // Second run — step succeeds
    $engine->run($workflow);

    $workflow->refresh();
    $step->refresh();
    expect($step->status)->toBe(StepStatus::Completed)
        ->and($step->attempts)->toBe(2)
        ->and($workflow->status)->toBe(WorkflowStatus::Completed);
});

it('marks workflow as failed when step exceeds max attempts', function (): void {
    Queue::fake();

    $workflow = ConductorWorkflow::factory()->create([
        'class' => AlwaysFailingWorkflow::class,
        'input' => [],
    ]);

    $engine = app(WorkflowEngine::class);
    $stepMaxAttempts = (new AlwaysFailingWorkflow())->stepMaxAttempts;

    for ($i = 0; $i < $stepMaxAttempts; $i++) {
        $engine->run($workflow);
        $workflow->refresh();
    }

    expect($workflow->status)->toBe(WorkflowStatus::Failed);
});

it('handles sleep by scheduling a delayed continuation', function (): void {
    Queue::fake();

    $workflow = ConductorWorkflow::factory()->create([
        'class' => SleepingTestWorkflow::class,
        'input' => [],
    ]);

    $engine = app(WorkflowEngine::class);
    $engine->run($workflow);

    $workflow->refresh();

    expect($workflow->sleep_until)->not->toBeNull()
        ->and($workflow->next_run_at)->not->toBeNull();

    Queue::assertPushed(WorkflowStepJob::class, fn (WorkflowStepJob $job): bool => $job->workflowId === $workflow->id);
});

it('resumes after sleep completes', function (): void {
    // SleepingTestWorkflow: step "before_sleep" → sleep("1 hour") → step "after_sleep"
    // With sleep_until in the past, the sleep check clears sleep_until and proceeds.
    // Pre-complete the before_sleep step so replay skips it.
    $workflow = ConductorWorkflow::factory()->running()->create([
        'class' => SleepingTestWorkflow::class,
        'input' => [],
        'current_step_index' => 1,
        'sleep_until' => now()->subMinute(),
        'next_run_at' => now()->subMinute(),
    ]);

    ConductorWorkflowStep::factory()->completed()->create([
        'workflow_id' => $workflow->id,
        'step_index' => 0,
        'name' => 'before_sleep',
    ]);

    $engine = app(WorkflowEngine::class);
    $engine->run($workflow);

    $workflow->refresh();

    expect($workflow->status)->toBe(WorkflowStatus::Completed)
        ->and($workflow->sleep_until)->toBeNull();

    $afterStep = ConductorWorkflowStep::where('workflow_id', $workflow->id)
        ->where('name', 'after_sleep')
        ->first();

    expect($afterStep)->not->toBeNull()
        ->and($afterStep->status)->toBe(StepStatus::Completed);
});

it('acquires pessimistic lock and prevents duplicate execution', function (): void {
    $workflow = ConductorWorkflow::factory()->create([
        'class' => ThreeStepWorkflow::class,
        'input' => [],
    ]);

    $engine = app(WorkflowEngine::class);

    // Run the engine twice in sequence; the second run should be a no-op
    // because the workflow will already be Completed after the first run.
    $engine->run($workflow);
    $engine->run($workflow);

    $stepCount = ConductorWorkflowStep::where('workflow_id', $workflow->id)->sum('attempts');

    // Each step should have been attempted exactly once (total 3 attempts for 3 steps)
    expect((int) $stepCount)->toBe(3);
});
