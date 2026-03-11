<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Http\Controllers\Api;

use HotReloadStudios\Conductor\Http\Resources\ConductorScheduleResource;
use HotReloadStudios\Conductor\Models\ConductorSchedule;
use HotReloadStudios\Conductor\Services\ScheduleToggleService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ScheduleController
{
    public function __construct(
        private readonly ScheduleToggleService $scheduleToggleService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $schedules = ConductorSchedule::query()
            ->orderBy('function_class')
            ->get();

        return ConductorScheduleResource::collection($schedules);
    }

    public function toggle(ConductorSchedule $schedule): ConductorScheduleResource
    {
        $updated = $this->scheduleToggleService->toggle($schedule);

        return new ConductorScheduleResource($updated);
    }
}
