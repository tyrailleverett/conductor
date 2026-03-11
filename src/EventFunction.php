<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor;

abstract class EventFunction
{
    /**
     * Returns the dot-notation event name this function listens to.
     */
    abstract public function listenTo(): string;

    /**
     * Executes the function logic with the event payload.
     *
     * @param  array<mixed>  $payload
     */
    abstract public function handle(array $payload): void;

    /**
     * Returns a human-readable display name for this function.
     */
    final public function displayName(): string
    {
        return class_basename(static::class);
    }
}
