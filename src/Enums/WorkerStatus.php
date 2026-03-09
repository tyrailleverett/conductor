<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Enums;

enum WorkerStatus: string
{
    case Idle = 'idle';
    case Busy = 'busy';
    case Offline = 'offline';
}
