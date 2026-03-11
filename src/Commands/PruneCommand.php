<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Commands;

use HotReloadStudios\Conductor\Models\ConductorEvent;
use HotReloadStudios\Conductor\Models\ConductorEventRun;
use HotReloadStudios\Conductor\Models\ConductorJob;
use HotReloadStudios\Conductor\Models\ConductorJobLog;
use HotReloadStudios\Conductor\Models\ConductorMetricSnapshot;
use HotReloadStudios\Conductor\Models\ConductorWebhookLog;
use HotReloadStudios\Conductor\Models\ConductorWorker;
use HotReloadStudios\Conductor\Models\ConductorWorkflow;
use HotReloadStudios\Conductor\Models\ConductorWorkflowStep;
use Illuminate\Console\Command;

final class PruneCommand extends Command
{
    protected $signature = 'conductor:prune {--days= : Number of days to retain}';

    protected $description = 'Delete Conductor records older than the configured retention period';

    public function handle(): int
    {
        $days = $this->option('days') !== null
            ? (int) $this->option('days')
            : (int) config('conductor.prune_after_days', 7);

        $cutoff = now()->subDays($days);

        $metricSnapshotCount = ConductorMetricSnapshot::where('recorded_at', '<', $cutoff)->delete();
        $webhookLogCount = ConductorWebhookLog::where('received_at', '<', $cutoff)->delete();

        // Delete child records before parents to avoid FK violations (SQLite doesn't enforce cascade by default).
        $staleEventIds = ConductorEvent::where('dispatched_at', '<', $cutoff)->pluck('id');
        ConductorEventRun::whereIn('event_id', $staleEventIds)->delete();
        $eventCount = ConductorEvent::where('dispatched_at', '<', $cutoff)->delete();

        $staleWorkflowIds = ConductorWorkflow::where('created_at', '<', $cutoff)->pluck('id');
        ConductorWorkflowStep::whereIn('workflow_id', $staleWorkflowIds)->delete();
        $workflowCount = ConductorWorkflow::where('created_at', '<', $cutoff)->delete();

        $staleJobIds = ConductorJob::where('created_at', '<', $cutoff)->pluck('id');
        ConductorJobLog::whereIn('job_id', $staleJobIds)->delete();
        $jobCount = ConductorJob::where('created_at', '<', $cutoff)->delete();

        $workerCount = ConductorWorker::where('last_heartbeat_at', '<', $cutoff)->delete();

        $this->info("Pruned records older than {$days} days.");
        $this->table(
            ['Table', 'Deleted'],
            [
                ['conductor_metric_snapshots', $metricSnapshotCount],
                ['conductor_webhook_logs', $webhookLogCount],
                ['conductor_events (+ runs)', $eventCount],
                ['conductor_workflows (+ steps)', $workflowCount],
                ['conductor_jobs (+ logs)', $jobCount],
                ['conductor_workers', $workerCount],
            ],
        );

        return self::SUCCESS;
    }
}
