<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Database\Factories;

use HotReloadStudios\Conductor\Enums\WebhookLogStatus;
use HotReloadStudios\Conductor\Models\ConductorWebhookLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConductorWebhookLog>
 */
final class ConductorWebhookLogFactory extends Factory
{
    protected $model = ConductorWebhookLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source' => 'stripe',
            'payload' => ['event' => 'charge.succeeded'],
            'masked_signature' => 'sha256=****',
            'status' => WebhookLogStatus::Received,
            'received_at' => now(),
        ];
    }

    public function verified(): static
    {
        return $this->state([
            'status' => WebhookLogStatus::Verified,
        ]);
    }

    public function processed(): static
    {
        return $this->state([
            'status' => WebhookLogStatus::Processed,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => WebhookLogStatus::Failed,
        ]);
    }
}
