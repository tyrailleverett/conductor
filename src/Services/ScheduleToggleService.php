<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Services;

use HotReloadStudios\Conductor\Models\ConductorSchedule;

final class ScheduleToggleService
{
    public function toggle(ConductorSchedule $schedule): ConductorSchedule
    {
        $schedule->is_active = ! $schedule->is_active;
        $schedule->save();

        return $schedule;
    }
}
