<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Enums\EventRunStatus;

it('has the expected cases', function (): void {
    expect(EventRunStatus::cases())->toHaveCount(4)
        ->and(EventRunStatus::Pending->value)->toBe('pending')
        ->and(EventRunStatus::Running->value)->toBe('running')
        ->and(EventRunStatus::Completed->value)->toBe('completed')
        ->and(EventRunStatus::Failed->value)->toBe('failed');
});
