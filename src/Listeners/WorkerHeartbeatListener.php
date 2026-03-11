<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Listeners;

use HotReloadStudios\Conductor\Models\ConductorWorker;
use HotReloadStudios\Conductor\Services\WorkerHeartbeatService;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\Looping;
use Illuminate\Queue\Events\WorkerStopping;

final class WorkerHeartbeatListener
{
    private static ?ConductorWorker $currentWorker = null;

    public function handleLooping(Looping $event): void
    {
        $service = app(WorkerHeartbeatService::class);

        if (self::$currentWorker === null) {
            self::$currentWorker = $service->register(
                workerName: 'worker-'.getmypid(),
                queue: $event->queue,
                connection: $event->connectionName,
            );
        }

        $service->heartbeat(self::$currentWorker, null);
    }

    public function handleJobProcessing(JobProcessing $event): void
    {
        if (self::$currentWorker === null) {
            $service = app(WorkerHeartbeatService::class);
            self::$currentWorker = $service->register(
                workerName: 'worker-'.getmypid(),
                queue: $event->job->getQueue(),
                connection: $event->connectionName,
            );
        }

        /** @var array{uuid?: string} $payload */
        $payload = $event->job->payload();
        $jobUuid = $payload['uuid'] ?? null;

        app(WorkerHeartbeatService::class)->heartbeat(self::$currentWorker, $jobUuid);
    }

    public function handleJobProcessed(JobProcessed $event): void
    {
        if (self::$currentWorker === null) {
            return;
        }

        app(WorkerHeartbeatService::class)->clearJob(self::$currentWorker);
    }

    public function handleJobFailed(JobFailed $event): void
    {
        if (self::$currentWorker === null) {
            return;
        }

        app(WorkerHeartbeatService::class)->clearJob(self::$currentWorker);
    }

    public function handleWorkerStopping(WorkerStopping $event): void
    {
        if (self::$currentWorker === null) {
            return;
        }

        self::$currentWorker->delete();
        self::$currentWorker = null;
    }
}
