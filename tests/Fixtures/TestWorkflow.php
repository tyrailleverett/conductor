<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Tests\Fixtures;

use Closure;
use HotReloadStudios\Conductor\Workflow;
use HotReloadStudios\Conductor\WorkflowContext;

/**
 * A concrete Workflow subclass for use in tests.
 * Accepts an array of step definitions or closures via constructor.
 *
 * @param  array<int, array{name: string, callback: Closure}|Closure>  $steps
 */
final class TestWorkflow extends Workflow
{
    /**
     * @param  array<int, array{name: string, callback: Closure}|Closure>  $steps
     */
    public function __construct(private readonly array $steps = []) {}

    public function handle(WorkflowContext $ctx): void
    {
        foreach ($this->steps as $index => $step) {
            if ($step instanceof Closure) {
                $ctx->step('step_'.$index, $step);
            } else {
                $ctx->step($step['name'], $step['callback']);
            }
        }
    }
}
