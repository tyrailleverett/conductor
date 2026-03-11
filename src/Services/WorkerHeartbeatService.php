<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Services;

use HotReloadStudios\Conductor\Models\ConductorWorker;
use Illuminate\Support\Str;

final class WorkerHeartbeatService
{
    public function register(string $workerName, string $queue, string $connection): ConductorWorker
    {
        $workerUuid = Str::uuid()->toString();

        /** @var ConductorWorker $worker */
        $worker = ConductorWorker::updateOrCreate(
            [
                'worker_name' => $workerName,
                'hostname' => gethostname() ?: '',
                'process_id' => (int) getmypid(),
            ],
            [
                'worker_uuid' => $workerUuid,
                'queue' => $queue,
                'connection' => $connection,
                'last_heartbeat_at' => now(),
            ],
        );

        return $worker;
    }

    public function heartbeat(ConductorWorker $worker, ?string $currentJobUuid = null): void
    {
        $worker->last_heartbeat_at = now();
        $worker->current_job_uuid = $currentJobUuid;
        $worker->save();
    }

    public function clearJob(ConductorWorker $worker): void
    {
        $worker->current_job_uuid = null;
        $worker->last_heartbeat_at = now();
        $worker->save();
    }
}
