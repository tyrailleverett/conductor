<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Enums\ScheduleRunStatus;

it('has the expected cases', function (): void {
    expect(ScheduleRunStatus::cases())->toHaveCount(2)
        ->and(ScheduleRunStatus::Completed->value)->toBe('completed')
        ->and(ScheduleRunStatus::Failed->value)->toBe('failed');
});
