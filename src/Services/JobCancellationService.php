<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Services;

use HotReloadStudios\Conductor\Enums\JobStatus;
use HotReloadStudios\Conductor\Models\ConductorJob;
use InvalidArgumentException;
use LogicException;

final class JobCancellationService
{
    /**
     * Cancel a job. Supports pending jobs (immediate), running cancellable jobs (cooperative),
     * and throws for non-cancellable running or terminal jobs.
     *
     * @throws InvalidArgumentException When the job is in a terminal state.
     * @throws LogicException When the job is running but does not support cooperative cancellation.
     */
    public function cancel(ConductorJob $job): void
    {
        if ($job->status === JobStatus::Pending) {
            $job->update([
                'status' => JobStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            return;
        }

        if ($job->status === JobStatus::Running) {
            if ($job->cancellable_at === null) {
                throw new LogicException('This job does not support cooperative cancellation.');
            }

            $job->update([
                'status' => JobStatus::CancellationRequested,
                'cancellation_requested_at' => now(),
            ]);

            return;
        }

        if ($job->status->isTerminal()) {
            throw new InvalidArgumentException('Terminal jobs cannot be cancelled.');
        }
    }
}
