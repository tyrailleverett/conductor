<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Enums;

enum JobStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case CancellationRequested = 'cancellation_requested';
    case Cancelled = 'cancelled';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed, self::Failed, self::Cancelled => true,
            default => false,
        };
    }
}
