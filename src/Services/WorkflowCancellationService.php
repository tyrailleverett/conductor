<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Services;

use HotReloadStudios\Conductor\Enums\StepStatus;
use HotReloadStudios\Conductor\Enums\WorkflowStatus;
use HotReloadStudios\Conductor\Models\ConductorWorkflow;
use HotReloadStudios\Conductor\Models\ConductorWorkflowStep;
use InvalidArgumentException;

final class WorkflowCancellationService
{
    public function __construct(private readonly JobCancellationService $jobCancellationService) {}

    /**
     * Cancel a workflow and skip all pending steps.
     *
     * @throws InvalidArgumentException When the workflow is already in a terminal state.
     */
    public function cancel(ConductorWorkflow $workflow): void
    {
        if ($workflow->isTerminal()) {
            throw new InvalidArgumentException('Terminal workflows cannot be cancelled.');
        }

        $workflow->update([
            'status' => WorkflowStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        ConductorWorkflowStep::where('workflow_id', $workflow->id)
            ->where('status', StepStatus::Pending)
            ->update(['status' => StepStatus::Skipped]);

        $runningStep = ConductorWorkflowStep::where('workflow_id', $workflow->id)
            ->where('status', StepStatus::Running)
            ->first();

        if ($runningStep !== null && $runningStep->conductor_job_id !== null) {
            $conductorJob = $runningStep->conductorJob;

            if ($conductorJob !== null && $conductorJob->cancellable_at !== null) {
                $this->jobCancellationService->cancel($conductorJob);
            }
        }
    }
}
