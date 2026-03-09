<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Database\Factories;

use HotReloadStudios\Conductor\Models\ConductorEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConductorEvent>
 */
final class ConductorEventFactory extends Factory
{
    protected $model = ConductorEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'name' => fake()->word().'.'.fake()->word(),
            'payload' => ['key' => 'value'],
            'dispatched_at' => now(),
        ];
    }
}
