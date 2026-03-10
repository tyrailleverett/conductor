<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Enums\WorkflowStatus;
use HotReloadStudios\Conductor\Models\ConductorWorkflow;
use HotReloadStudios\Conductor\Models\ConductorWorkflowStep;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('casts status to WorkflowStatus enum', function (): void {
    $workflow = ConductorWorkflow::factory()->create();

    expect($workflow->status)->toBeInstanceOf(WorkflowStatus::class);
});

it('casts input and output to arrays', function (): void {
    $workflow = ConductorWorkflow::factory()->create([
        'input' => ['key' => 'value'],
        'output' => ['result' => 'ok'],
    ]);

    expect($workflow->input)->toBeArray()
        ->and($workflow->output)->toBeArray();
});

it('has a steps relationship ordered by step_index', function (): void {
    $workflow = ConductorWorkflow::factory()->create();
    ConductorWorkflowStep::factory()->create(['workflow_id' => $workflow->id, 'step_index' => 2]);
    ConductorWorkflowStep::factory()->create(['workflow_id' => $workflow->id, 'step_index' => 0]);
    ConductorWorkflowStep::factory()->create(['workflow_id' => $workflow->id, 'step_index' => 1]);

    $indexes = $workflow->steps->pluck('step_index')->all();

    expect($indexes)->toBe([0, 1, 2]);
});

it('determines terminal status', function (): void {
    expect(ConductorWorkflow::factory()->completed()->make()->isTerminal())->toBeTrue()
        ->and(ConductorWorkflow::factory()->failed()->make()->isTerminal())->toBeTrue()
        ->and(ConductorWorkflow::factory()->cancelled()->make()->isTerminal())->toBeTrue()
        ->and(ConductorWorkflow::factory()->make()->isTerminal())->toBeFalse()
        ->and(ConductorWorkflow::factory()->running()->make()->isTerminal())->toBeFalse();
});

it('resolves route key by uuid', function (): void {
    $workflow = new ConductorWorkflow;

    expect($workflow->getRouteKeyName())->toBe('uuid');
});

it('scopes runnable workflows', function (): void {
    ConductorWorkflow::factory()->create(['status' => WorkflowStatus::Pending]);
    ConductorWorkflow::factory()->running()->create(['next_run_at' => null]);
    ConductorWorkflow::factory()->running()->create(['next_run_at' => now()->subMinute()]);
    ConductorWorkflow::factory()->running()->create(['next_run_at' => now()->addHour()]);
    ConductorWorkflow::factory()->completed()->create();

    expect(ConductorWorkflow::runnable()->count())->toBe(2);
});
