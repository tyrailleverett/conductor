<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Services;

use HotReloadStudios\Conductor\Enums\ScheduleRunStatus;
use HotReloadStudios\Conductor\Models\ConductorSchedule;
use HotReloadStudios\Conductor\ScheduledFunction;
use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use Illuminate\Console\Scheduling\Schedule;
use Throwable;

final class ScheduleRegistrar
{
    /**
     * Named frequency methods supported by Laravel's scheduler.
     *
     * @var array<string>
     */
    private const NAMED_FREQUENCIES = [
        'daily',
        'hourly',
        'weekly',
        'monthly',
        'yearly',
        'everyMinute',
        'everyFiveMinutes',
        'everyTenMinutes',
        'everyFifteenMinutes',
        'everyThirtyMinutes',
    ];

    public function register(Schedule $schedule): void
    {
        /** @var array<string> $functions */
        $functions = config('conductor.functions', []);

        foreach ($functions as $functionClass) {
            if (! is_subclass_of($functionClass, ScheduledFunction::class)) {
                continue;
            }

            /** @var ScheduledFunction $function */
            $function = new $functionClass();

            $frequencyString = $function->schedule();
            /** @var ConductorSchedule $conductorSchedule */
            $conductorSchedule = ConductorSchedule::firstOrNew(['function_class' => $functionClass]);
            $conductorSchedule->display_name = $function->displayName();
            $conductorSchedule->cron_expression = $frequencyString;

            if (! $conductorSchedule->exists) {
                $conductorSchedule->is_active = true;
            }

            $conductorSchedule->save();

            /** @var ScheduledEvent|null $scheduledEvent */
            $scheduledEvent = null;

            $scheduledEvent = $schedule->call(function () use ($functionClass, &$scheduledEvent): void {
                /** @var ConductorSchedule|null $conductorSchedule */
                $conductorSchedule = ConductorSchedule::where('function_class', $functionClass)->first();

                if ($conductorSchedule === null) {
                    return;
                }

                if (! $conductorSchedule->is_active) {
                    return;
                }

                $startedAt = now();

                $conductorSchedule->update(['last_run_at' => $startedAt]);

                /** @var ScheduledFunction $fn */
                $fn = new $functionClass();

                try {
                    $fn->handle();

                    $conductorSchedule->update([
                        'last_run_status' => ScheduleRunStatus::Completed,
                        'next_run_at' => $scheduledEvent?->nextRunDate($startedAt),
                    ]);
                } catch (Throwable) {
                    $conductorSchedule->update([
                        'last_run_status' => ScheduleRunStatus::Failed,
                    ]);
                }
            });

            if ($this->isCronExpression($frequencyString)) {
                $scheduledEvent->cron($frequencyString);
            } else {
                $scheduledEvent->{$frequencyString}();
            }
        }
    }

    private function isCronExpression(string $expression): bool
    {
        return ! in_array($expression, self::NAMED_FREQUENCIES, true);
    }
}
