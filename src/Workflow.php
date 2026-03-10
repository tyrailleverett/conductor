<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor;

abstract class Workflow
{
    public int $stepMaxAttempts = 3;

    public ?int $conductorWorkflowId = null;

    abstract public function handle(WorkflowContext $ctx): void;

    final public static function dispatch(mixed ...$args): PendingWorkflowDispatch
    {
        return new PendingWorkflowDispatch(static::class, $args);
    }

    final public function displayName(): string
    {
        return class_basename(static::class);
    }
}
