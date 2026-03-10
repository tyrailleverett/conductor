<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Support;

final class ConductorContext
{
    private static ?int $currentJobId = null;

    public static function set(int $jobId): void
    {
        self::$currentJobId = $jobId;
    }

    public static function get(): ?int
    {
        return self::$currentJobId;
    }

    public static function clear(): void
    {
        self::$currentJobId = null;
    }

    public static function isActive(): bool
    {
        return self::$currentJobId !== null;
    }
}
