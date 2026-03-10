<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Enums;

enum ScheduleRunStatus: string
{
    case Completed = 'completed';
    case Failed = 'failed';
}
