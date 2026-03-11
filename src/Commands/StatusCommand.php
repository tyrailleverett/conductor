<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Commands;

use HotReloadStudios\Conductor\Enums\WorkerStatus;
use HotReloadStudios\Conductor\Models\ConductorJob;
use HotReloadStudios\Conductor\Models\ConductorWorker;
use Illuminate\Console\Command;

final class StatusCommand extends Command
{
    protected $signature = 'conductor:status';

    protected $description = 'Display a health summary of the Conductor worker and job system';

    public function handle(): int
    {
        $queueConnection = config('queue.default');
        $queueDriver = config("queue.connections.{$queueConnection}.driver", 'unknown');
        $this->info("Queue connection: {$queueConnection} (driver: {$queueDriver})");

        $functionCount = count((array) config('conductor.functions', []));
        $this->info("Registered functions: {$functionCount}");

        /** @var \Illuminate\Database\Eloquent\Collection<int, ConductorWorker> $workers */
        $workers = ConductorWorker::all();

        if ($workers->isEmpty()) {
            $this->info('No workers registered.');
        } else {
            $workerRows = $workers->map(function (ConductorWorker $worker): array {
                return [
                    $worker->worker_name,
                    $worker->queue,
                    $worker->hostname,
                    $worker->derivedStatus()->value,
                    $worker->last_heartbeat_at->diffForHumans(),
                ];
            })->toArray();

            $this->table(
                ['Worker Name', 'Queue', 'Hostname', 'Status', 'Last Heartbeat'],
                $workerRows,
            );
        }

        /** @var \Illuminate\Support\Collection<string, int> $jobCounts */
        $jobCounts = ConductorJob::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        if ($jobCounts->isNotEmpty()) {
            $this->table(
                ['Status', 'Count'],
                $jobCounts->map(fn (int $count, string $status): array => [$status, $count])->values()->toArray(),
            );
        }

        $hasOfflineWorkers = $workers->contains(
            fn (ConductorWorker $worker): bool => $worker->derivedStatus() === WorkerStatus::Offline,
        );

        if ($hasOfflineWorkers) {
            $this->warn('Warning: One or more workers are offline.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
