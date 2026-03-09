<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Database\Factories;

use HotReloadStudios\Conductor\Models\ConductorWebhookSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConductorWebhookSource>
 */
final class ConductorWebhookSourceFactory extends Factory
{
    protected $model = ConductorWebhookSource::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source' => fake()->unique()->word(),
            'function_class' => 'App\\Functions\\ExampleWebhookHandler',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }
}
