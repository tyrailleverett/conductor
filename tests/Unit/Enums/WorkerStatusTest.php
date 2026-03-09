<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Enums\WorkerStatus;

it('has the expected cases', function (): void {
    expect(WorkerStatus::cases())->toHaveCount(3)
        ->and(WorkerStatus::Idle->value)->toBe('idle')
        ->and(WorkerStatus::Busy->value)->toBe('busy')
        ->and(WorkerStatus::Offline->value)->toBe('offline');
});
