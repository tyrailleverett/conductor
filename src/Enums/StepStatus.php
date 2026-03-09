<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Enums;

enum StepStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed, self::Failed, self::Skipped => true,
            default => false,
        };
    }
}
