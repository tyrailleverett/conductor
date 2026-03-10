<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Enums;

enum MetricType: string
{
    case Throughput = 'throughput';
    case FailureRate = 'failure_rate';
    case QueueDepth = 'queue_depth';
}
