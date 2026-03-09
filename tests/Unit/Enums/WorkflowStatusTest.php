<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Enums\WorkflowStatus;

it('has the expected cases', function (): void {
    expect(WorkflowStatus::cases())->toHaveCount(6)
        ->and(WorkflowStatus::Pending->value)->toBe('pending')
        ->and(WorkflowStatus::Running->value)->toBe('running')
        ->and(WorkflowStatus::Waiting->value)->toBe('waiting')
        ->and(WorkflowStatus::Completed->value)->toBe('completed')
        ->and(WorkflowStatus::Failed->value)->toBe('failed')
        ->and(WorkflowStatus::Cancelled->value)->toBe('cancelled');
});

it('identifies terminal statuses', function (): void {
    expect(WorkflowStatus::Completed->isTerminal())->toBeTrue()
        ->and(WorkflowStatus::Failed->isTerminal())->toBeTrue()
        ->and(WorkflowStatus::Cancelled->isTerminal())->toBeTrue()
        ->and(WorkflowStatus::Pending->isTerminal())->toBeFalse()
        ->and(WorkflowStatus::Running->isTerminal())->toBeFalse()
        ->and(WorkflowStatus::Waiting->isTerminal())->toBeFalse();
});
