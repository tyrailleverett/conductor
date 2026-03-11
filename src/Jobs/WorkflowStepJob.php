<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Jobs;

use HotReloadStudios\Conductor\Enums\WorkflowStatus;
use HotReloadStudios\Conductor\Models\ConductorWorkflow;
use HotReloadStudios\Conductor\Services\WorkflowEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class WorkflowStepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $maxExceptions = 1;

    public function __construct(public readonly int $workflowId)
    {
        $this->queue = (string) config('conductor.queue.queue');

        $connection = config('conductor.queue.connection');

        if (is_string($connection) && $connection !== '') {
            $this->connection = $connection;
        }
    }

    public function handle(WorkflowEngine $engine): void
    {
        /** @var ConductorWorkflow|null $workflow */
        $workflow = ConductorWorkflow::find($this->workflowId);

        if ($workflow === null || $workflow->isTerminal()) {
            return;
        }

        $engine->run($workflow);
    }

    public function failed(Throwable $e): void
    {
        /** @var ConductorWorkflow|null $workflow */
        $workflow = ConductorWorkflow::find($this->workflowId);

        if ($workflow !== null && ! $workflow->isTerminal()) {
            $workflow->update(['status' => WorkflowStatus::Failed]);
        }

        Log::error('WorkflowStepJob failed: '.$e->getMessage(), [
            'workflow_id' => $this->workflowId,
            'exception' => $e,
        ]);
    }
}
