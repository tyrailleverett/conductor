<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor;

abstract class ScheduledFunction
{
    /**
     * Returns a cron expression or named frequency string for this function.
     *
     * Accepted cron formats: '0 9 * * *'
     * Accepted named frequencies: 'daily', 'hourly', 'weekly', 'monthly', 'yearly',
     * 'everyMinute', 'everyFiveMinutes', 'everyTenMinutes', 'everyFifteenMinutes', 'everyThirtyMinutes'
     */
    abstract public function schedule(): string;

    /**
     * Executes the scheduled logic.
     */
    abstract public function handle(): void;

    /**
     * Returns a human-readable display name for this function.
     */
    final public function displayName(): string
    {
        return class_basename(static::class);
    }
}
