<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor;

abstract class WebhookFunction
{
    /**
     * Processes the verified webhook payload.
     *
     * @param  array<mixed>  $payload
     */
    abstract public function handle(array $payload, string $source): void;

    /**
     * Returns a human-readable display name for this function.
     */
    final public function displayName(): string
    {
        return class_basename(static::class);
    }
}
