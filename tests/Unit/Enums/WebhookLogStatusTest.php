<?php

declare(strict_types=1);

use HotReloadStudios\Conductor\Enums\WebhookLogStatus;

it('has the expected cases', function (): void {
    expect(WebhookLogStatus::cases())->toHaveCount(4)
        ->and(WebhookLogStatus::Received->value)->toBe('received')
        ->and(WebhookLogStatus::Verified->value)->toBe('verified')
        ->and(WebhookLogStatus::Processed->value)->toBe('processed')
        ->and(WebhookLogStatus::Failed->value)->toBe('failed');
});
