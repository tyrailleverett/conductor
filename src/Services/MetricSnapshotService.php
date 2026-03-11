<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Services;

use HotReloadStudios\Conductor\Enums\JobStatus;
use HotReloadStudios\Conductor\Enums\MetricType;
use HotReloadStudios\Conductor\Models\ConductorJob;
use HotReloadStudios\Conductor\Models\ConductorMetricSnapshot;

final class MetricSnapshotService
{
    public function capture(): void
    {
        $since = now()->subMinute();

        $completedCount = ConductorJob::where('completed_at', '>=', $since)->count();
        ConductorMetricSnapshot::create([
            'metric' => MetricType::Throughput,
            'value' => $completedCount,
            'recorded_at' => now(),
        ]);

        $failedCount = ConductorJob::where('failed_at', '>=', $since)->count();
        $total = $completedCount + $failedCount;
        $failureRate = $total > 0 ? $failedCount / $total : 0;
        ConductorMetricSnapshot::create([
            'metric' => MetricType::FailureRate,
            'value' => $failureRate,
            'recorded_at' => now(),
        ]);

        $pendingQueues = ConductorJob::where('status', JobStatus::Pending)
            ->selectRaw('queue, COUNT(*) as count')
            ->groupBy('queue')
            ->pluck('count', 'queue');

        foreach ($pendingQueues as $queue => $count) {
            ConductorMetricSnapshot::create([
                'metric' => MetricType::QueueDepth,
                'queue' => $queue,
                'value' => $count,
                'recorded_at' => now(),
            ]);
        }
    }
}
