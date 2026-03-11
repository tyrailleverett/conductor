<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Services;

use Closure;
use HotReloadStudios\Conductor\Enums\StepStatus;
use HotReloadStudios\Conductor\Enums\WorkflowStatus;
use HotReloadStudios\Conductor\Jobs\WorkflowStepJob;
use HotReloadStudios\Conductor\Models\ConductorWorkflow;
use HotReloadStudios\Conductor\Models\ConductorWorkflowStep;
use HotReloadStudios\Conductor\Workflow;
use HotReloadStudios\Conductor\WorkflowContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use Throwable;

final class WorkflowEngine
{
    public function run(ConductorWorkflow $workflow): void
    {
        DB::transaction(function () use ($workflow): void {
            /** @var ConductorWorkflow|null $locked */
            $locked = ConductorWorkflow::where('id', $workflow->id)->lockForUpdate()->first();

            if ($locked === null) {
                return;
            }

            if ($locked->isTerminal()) {
                return;
            }

            if ($locked->status === WorkflowStatus::Pending) {
                $locked->update(['status' => WorkflowStatus::Running]);
                $locked->refresh();
            }

            $ctx = new WorkflowContext($locked, $this);

            $instance = $this->instantiateWorkflow($locked);
            $instance->conductorWorkflowId = $locked->id;

            try {
                $instance->handle($ctx);

                if ($ctx->isPaused()) {
                    $this->dispatchWorkflowStepJob($locked->id, $locked->next_run_at);
                } else {
                    $locked->update([
                        'status' => WorkflowStatus::Completed,
                        'completed_at' => now(),
                        'output' => $this->normalizeOutput($ctx->getLastResult()),
                        'next_run_at' => null,
                        'sleep_until' => null,
                    ]);
                }
            } catch (Throwable $e) {
                $currentStep = $this->findStep($locked, $locked->current_step_index ?? 0);

                $attempts = $currentStep?->attempts ?? 1;
                $maxAttempts = $instance->stepMaxAttempts;

                if ($attempts >= $maxAttempts) {
                    $locked->update([
                        'status' => WorkflowStatus::Failed,
                        'next_run_at' => null,
                    ]);
                } else {
                    $backoffSeconds = (int) pow(2, $attempts);

                    $this->dispatchWorkflowStepJob($locked->id, now()->addSeconds($backoffSeconds));
                }

                Log::error('Workflow step failed: '.$e->getMessage(), [
                    'workflow_id' => $locked->id,
                    'exception' => $e,
                ]);
            }
        });
    }

    public function executeStep(ConductorWorkflow $workflow, int $stepIndex, string $name, Closure $callback): mixed
    {
        $step = $this->findOrCreateStep($workflow, $stepIndex, $name);

        if ($step->status === StepStatus::Completed || $step->status === StepStatus::Skipped) {
            return $this->restoreOutput($step->output);
        }

        $step->update([
            'status' => StepStatus::Running,
            'started_at' => now(),
            'attempts' => $step->attempts + 1,
        ]);

        $workflow->update(['current_step_index' => $stepIndex]);

        try {
            $result = $callback();

            $step->update([
                'status' => StepStatus::Completed,
                'output' => $this->normalizeOutput($result),
                'completed_at' => now(),
                'duration_ms' => $step->started_at !== null
                    ? (int) $step->started_at->diffInMilliseconds(now())
                    : null,
            ]);

            return $result;
        } catch (Throwable $e) {
            $step->update([
                'status' => StepStatus::Failed,
                'error_message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function dispatchWorkflowStepJob(int $workflowId, mixed $delay = null): void
    {
        $pendingDispatch = WorkflowStepJob::dispatch($workflowId)
            ->onQueue((string) config('conductor.queue.queue', 'conductor'));

        $connection = config('conductor.queue.connection');

        if (is_string($connection) && $connection !== '') {
            $pendingDispatch->onConnection($connection);
        }

        if ($delay !== null) {
            $pendingDispatch->delay($delay);
        }
    }

    private function instantiateWorkflow(ConductorWorkflow $workflow): Workflow
    {
        /** @var class-string<Workflow> $class */
        $class = $workflow->class;

        /** @var array<mixed> $input */
        $input = $workflow->input ?? [];

        /** @var Workflow $instance */
        $instance = (new ReflectionClass($class))->newInstanceArgs($input);

        return $instance;
    }

    private function findOrCreateStep(ConductorWorkflow $workflow, int $stepIndex, string $name): ConductorWorkflowStep
    {
        $step = $this->findStep($workflow, $stepIndex, true);

        if ($step instanceof ConductorWorkflowStep) {
            return $step;
        }

        return ConductorWorkflowStep::create([
            'workflow_id' => $workflow->id,
            'step_index' => $stepIndex,
            'name' => $name,
            'status' => StepStatus::Pending,
            'attempts' => 0,
        ]);
    }

    private function findStep(ConductorWorkflow $workflow, int $stepIndex, bool $forUpdate = false): ?ConductorWorkflowStep
    {
        $query = ConductorWorkflowStep::query()
            ->where('workflow_id', $workflow->id)
            ->where('step_index', $stepIndex);

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        $step = $query->first();

        return $step instanceof ConductorWorkflowStep ? $step : null;
    }

    private function normalizeOutput(mixed $result): ?array
    {
        if ($result === null) {
            return null;
        }

        if (is_array($result)) {
            return $result;
        }

        return ['value' => $result];
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
