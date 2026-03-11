<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Jobs;

use HotReloadStudios\Conductor\Enums\EventRunStatus;
use HotReloadStudios\Conductor\EventFunction;
use HotReloadStudios\Conductor\Models\ConductorEventRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class EventFunctionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [5, 30, 60];

    /**
     * @param  array<mixed>  $payload
     */
    public function __construct(
        public readonly int $eventRunId,
        public readonly string $functionClass,
        public readonly array $payload,
    ) {
        $this->queue = (string) config('conductor.queue.queue');

        $connection = config('conductor.queue.connection');

        if (is_string($connection) && $connection !== '') {
            $this->connection = $connection;
        }
    }

    public function handle(): void
    {
        /** @var ConductorEventRun|null $eventRun */
        $eventRun = ConductorEventRun::find($this->eventRunId);

        if ($eventRun === null) {
            return;
        }

        $startedAt = now();
        $currentAttempt = $eventRun->attempts + 1;

        $eventRun->update([
            'status' => EventRunStatus::Running,
            'started_at' => $startedAt,
            'attempts' => $currentAttempt,
        ]);

        /** @var EventFunction $function */
        $function = new $this->functionClass();

        try {
            $function->handle($this->payload);

            $completedAt = now();
            $durationMs = (int) $startedAt->diffInMilliseconds($completedAt);

            $eventRun->update([
                'status' => EventRunStatus::Completed,
                'completed_at' => $completedAt,
                'duration_ms' => $durationMs,
            ]);
        } catch (Throwable $e) {
            $updates = [
                'error_message' => $e->getMessage(),
            ];

            if ($currentAttempt >= $this->tries) {
                $updates['status'] = EventRunStatus::Failed;
            }

            $eventRun->update($updates);

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        /** @var ConductorEventRun|null $eventRun */
        $eventRun = ConductorEventRun::find($this->eventRunId);

        if ($eventRun === null) {
            return;
        }

        $eventRun->update([
            'status' => EventRunStatus::Failed,
            'error_message' => $e->getMessage(),
        ]);
    }
}
