<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Models\ConductorWorkflow;
use HotReloadStudios\Conductor\Models\ConductorWorkflowStep;
use HotReloadStudios\Conductor\Services\WorkflowEngine;
use HotReloadStudios\Conductor\WorkflowContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('replays completed steps without re-executing', function (): void {
    $workflow = ConductorWorkflow::factory()->running()->create([
        'class' => 'App\\Workflows\\TestWorkflow',
    ]);

    ConductorWorkflowStep::factory()->completed()->create([
        'workflow_id' => $workflow->id,
        'step_index' => 0,
        'name' => 'step_0',
        'output' => ['value' => 'cached-result'],
    ]);

    $engine = app(WorkflowEngine::class);
    $ctx = new WorkflowContext($workflow, $engine);

    $executed = false;
    $result = $ctx->step('step_0', function () use (&$executed): string {
        $executed = true;

        return 'fresh-result';
    });

    expect($executed)->toBeFalse()
        ->and($result)->toBe(['value' => 'cached-result']);
});

it('skips steps after sleep', function (): void {
    $workflow = ConductorWorkflow::factory()->running()->create([
        'class' => 'App\\Workflows\\TestWorkflow',
    ]);

    $engine = app(WorkflowEngine::class);
    $ctx = new WorkflowContext($workflow, $engine);

    // Sleep for 1 hour — context will pause
    $ctx->sleep(3600);

    $executed = false;
    $ctx->step('step_after_sleep', function () use (&$executed): string {
        $executed = true;

        return 'result';
    });

    expect($executed)->toBeFalse();
});

it('increments step index on each step call', function (): void {
    $workflow = ConductorWorkflow::factory()->running()->create([
        'class' => 'App\\Workflows\\TestWorkflow',
    ]);

    $engine = app(WorkflowEngine::class);
    $ctx = new WorkflowContext($workflow, $engine);

    $ctx->step('step_0', fn (): string => 'a');
    $ctx->step('step_1', fn (): string => 'b');
    $ctx->step('step_2', fn (): string => 'c');

    expect($ctx->getStepIndex())->toBe(3);
});
