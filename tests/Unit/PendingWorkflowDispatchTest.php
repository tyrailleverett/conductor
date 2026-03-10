<?php

// phpcs:disable PSR1.Classes.ClassDeclaration

declare(strict_types=1);

use HotReloadStudios\Conductor\Jobs\WorkflowStepJob;
use HotReloadStudios\Conductor\Workflow;
use HotReloadStudios\Conductor\WorkflowContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

final class PendingDispatchTestWorkflow extends Workflow
{
    public function handle(WorkflowContext $ctx): void
    {
        $ctx->step('step_0', fn (): string => 'done');
    }
}

it('stores queue and connection configuration', function (): void {
    Queue::fake();

    $pending = PendingDispatchTestWorkflow::dispatch()->onQueue('high')->onConnection('redis');
    $workflow = $pending->dispatch();

    Queue::assertPushedOn('high', WorkflowStepJob::class, function (WorkflowStepJob $job) use ($workflow): bool {
        return $job->workflowId === $workflow->id
            && $job->connection === 'redis';
    });
});
