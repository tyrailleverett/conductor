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

        if ($this->extractSerializedJobClass($serialized) !== $job->class) {
            throw new InvalidArgumentException('Retry payload does not match the stored job class.');
        }

        $originalJob = unserialize($serialized);

        if (! is_object($originalJob) || $originalJob::class !== $job->class) {
            throw new InvalidArgumentException('Retry payload could not be restored safely.');
        }

        if (property_exists($originalJob, 'conductorJobId')) {
            $originalJob->conductorJobId = $job->id;
        }

        $job->update([
            'status' => JobStatus::Pending,
            'failed_at' => null,
            'error_message' => null,
            'stack_trace' => null,
            'completed_at' => null,
            'cancelled_at' => null,
            'duration_ms' => null,
        ]);

        dispatch($originalJob)->onQueue($job->queue)->onConnection($job->connection);
    }

    private function extractSerializedJobClass(string $serializedJob): ?string
    {
        if (! preg_match('/^O:\\d+:"([^"]+)":\\d+:/', $serializedJob, $matches)) {
            return null;
        }

        return $matches[1];
    }
}
