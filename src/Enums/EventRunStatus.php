<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Enums;

enum EventRunStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
}
