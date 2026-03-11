<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor;

use Closure;
use HotReloadStudios\Conductor\Enums\StepStatus;
use HotReloadStudios\Conductor\Models\ConductorWorkflow;
use HotReloadStudios\Conductor\Models\ConductorWorkflowStep;
use HotReloadStudios\Conductor\Services\WorkflowEngine;
use Illuminate\Support\Carbon;

final class WorkflowContext
{
    private int $stepIndex = 0;

    private bool $shouldPause = false;

    private mixed $lastResult = null;

    public function __construct(
        private readonly ConductorWorkflow $workflow,
        private readonly WorkflowEngine $engine,
    ) {}

    public function step(string $name, Closure $callback): mixed
    {
        if ($this->shouldPause) {
            $this->stepIndex++;

            return null;
        }

        $step = ConductorWorkflowStep::where('workflow_id', $this->workflow->id)
            ->where('step_index', $this->stepIndex)
            ->first();

        if ($step !== null && $step->status === StepStatus::Completed) {
            $this->lastResult = $this->restoreOutput($step->output);
            $this->stepIndex++;

            return $this->lastResult;
        }

        if ($step !== null && $step->status === StepStatus::Skipped) {
            $this->stepIndex++;

            return null;
        }

        $result = $this->engine->executeStep($this->workflow, $this->stepIndex, $name, $callback);

        $this->lastResult = $result;

        $this->stepIndex++;

        return $result;
    }

    public function sleep(string|int $duration): void
    {
        if (is_int($duration)) {
            $wakeAt = now()->addSeconds($duration);
        } else {
            $wakeAt = Carbon::parse($duration);
        }

        if ($this->workflow->sleep_until !== null && $this->workflow->sleep_until->isPast()) {
            $this->workflow->update([
                'sleep_until' => null,
                'next_run_at' => null,
            ]);

            return;
        }

        $this->workflow->update([
            'sleep_until' => $wakeAt,
            'next_run_at' => $wakeAt,
        ]);

        $this->shouldPause = true;
    }

    public function isPaused(): bool
    {
        return $this->shouldPause;
    }

    public function getStepIndex(): int
    {
        return $this->stepIndex;
    }

    public function getLastResult(): mixed
    {
        return $this->lastResult;
    }

    private function restoreOutput(?array $output): mixed
    {
        if ($output === null) {
            return null;
        }

        if (array_key_exists('value', $output) && count($output) === 1) {
            return $output['value'];
        }

        return $output;
    }
}
