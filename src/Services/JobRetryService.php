<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Services;

use HotReloadStudios\Conductor\Enums\JobStatus;
use HotReloadStudios\Conductor\Models\ConductorJob;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;

final class JobRetryService
{
    /**
     * Retry a failed job by re-dispatching it to its original queue.
     */
    public function retry(ConductorJob $job): void
    {
        if ($job->status !== JobStatus::Failed) {
            throw new InvalidArgumentException('Only failed jobs can be retried.');
        }

        /** @var array<string, mixed> $payload */
        $payload = $job->payload;

        $encrypted = (string) $payload['retry'];
        $serialized = Crypt::decryptString($encrypted);
        $originalJob = unserialize($serialized);

        $job->update([
            'status' => JobStatus::Pending,
            'failed_at' => null,
            'error_message' => null,
            'stack_trace' => null,
            'completed_at' => null,
            'cancelled_at' => null,
            'duration_ms' => null,
            'attempts' => $job->attempts + 1,
        ]);

        dispatch($originalJob)->onQueue($job->queue)->onConnection($job->connection);
    }
}
