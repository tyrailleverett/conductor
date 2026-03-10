<?php

// phpcs:disable PSR1.Classes.ClassDeclaration

declare(strict_types=1);

use HotReloadStudios\Conductor\Enums\WorkflowStatus;
use HotReloadStudios\Conductor\Jobs\WorkflowStepJob;
use HotReloadStudios\Conductor\Models\ConductorWorkflow;
use HotReloadStudios\Conductor\Workflow;
use HotReloadStudios\Conductor\WorkflowContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

final class DispatchTestWorkflow extends Workflow
{
    public function __construct(public readonly string $name = 'default') {}

    public function handle(WorkflowContext $ctx): void
    {
        $ctx->step('step_0', fn (): string => $this->name);
    }
}

it('creates a conductor_workflows record on dispatch', function (): void {
    Queue::fake();

    DispatchTestWorkflow::dispatch();

    expect(ConductorWorkflow::count())->toBe(1);

    $workflow = ConductorWorkflow::first();
    expect($workflow->status)->toBe(WorkflowStatus::Pending)
        ->and($workflow->class)->toBe(DispatchTestWorkflow::class);
});

it('dispatches a WorkflowStepJob', function (): void {
    Queue::fake();

    DispatchTestWorkflow::dispatch();

    Queue::assertPushed(WorkflowStepJob::class);
});

it('stores serialized constructor arguments as input', function (): void {
    Queue::fake();

    DispatchTestWorkflow::dispatch('my-name');

    $workflow = ConductorWorkflow::first();

    expect($workflow->input)->not->toBeNull()
        ->and($workflow->input)->toBeArray()
        ->and($workflow->input[0])->toBe('my-name');
});
