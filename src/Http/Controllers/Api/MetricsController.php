<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Http\Controllers\Api;

use HotReloadStudios\Conductor\Enums\MetricType;
use HotReloadStudios\Conductor\Models\ConductorMetricSnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MetricsController
{
    /** @var array<string> */
    private const array VALID_WINDOWS = ['1h', '24h', '7d'];

    public function index(Request $request): JsonResponse
    {
        $window = $request->query('window', '24h');

        if (! in_array($window, self::VALID_WINDOWS, true)) {
            $window = '24h';
        }

        $start = match ($window) {
            '1h' => now()->subHour(),
            '7d' => now()->subWeek(),
            default => now()->subDay(),
        };

        $snapshots = ConductorMetricSnapshot::query()
            ->where('recorded_at', '>=', $start)
            ->orderBy('recorded_at')
            ->get();

        $throughput = [];
        $failureRate = [];
        $queueDepth = [];

        foreach ($snapshots as $snapshot) {
            $entry = [
                'value' => $snapshot->value,
                'recorded_at' => $snapshot->recorded_at->toIso8601String(),
            ];

            if ($snapshot->metric === MetricType::Throughput) {
                $throughput[] = $entry;
            } elseif ($snapshot->metric === MetricType::FailureRate) {
                $failureRate[] = $entry;
            } elseif ($snapshot->metric === MetricType::QueueDepth) {
                $queue = $snapshot->queue ?? 'default';
                $queueDepth[$queue][] = $entry;
            }
        }

        return response()->json([
            'window' => $window,
            'throughput' => $throughput,
            'failure_rate' => $failureRate,
            'queue_depth' => $queueDepth,
        ]);
    }
}
