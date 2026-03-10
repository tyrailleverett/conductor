<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Services;

use Closure;
use HotReloadStudios\Conductor\Enums\StepStatus;
use HotReloadStudios\Conductor\Enums\WorkflowStatus;
use HotReloadStudios\Conductor\Jobs\WorkflowStepJob;
use HotReloadStudios\Conductor\Models\ConductorWorkflow;
use HotReloadStudios\Conductor\Models\ConductorWorkflowStep;
use HotReloadStudios\Conductor\WorkflowContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

            /** @var class-string<\HotReloadStudios\Conductor\Workflow> $class */
            $class = $locked->class;

            /** @var array<mixed> $input */
            $input = $locked->input ?? [];

            $instance = new $class(...$input);
            $instance->conductorWorkflowId = $locked->id;

            try {
                $instance->handle($ctx);

                if ($ctx->isPaused()) {
                    WorkflowStepJob::dispatch($locked->id)
                        ->delay($locked->next_run_at)
                        ->onQueue((string) config('conductor.queue.queue'))
                        ->onConnection((string) config('conductor.queue.connection'));
                } else {
                    $locked->update([
                        'status' => WorkflowStatus::Completed,
                        'completed_at' => now(),
                    ]);
                }
            } catch (Throwable $e) {
                $currentStep = ConductorWorkflowStep::where('workflow_id', $locked->id)
                    ->where('step_index', $locked->current_step_index ?? 0)
                    ->first();

                $attempts = $currentStep?->attempts ?? 1;
                $maxAttempts = $instance->stepMaxAttempts;

                if ($attempts >= $maxAttempts) {
                    $locked->update(['status' => WorkflowStatus::Failed]);
                } else {
                    $backoffSeconds = (int) pow(2, $attempts);

                    WorkflowStepJob::dispatch($locked->id)
                        ->delay(now()->addSeconds($backoffSeconds))
                        ->onQueue((string) config('conductor.queue.queue'))
                        ->onConnection((string) config('conductor.queue.connection'));
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
        $step = ConductorWorkflowStep::where('workflow_id', $workflow->id)
            ->where('step_index', $stepIndex)
            ->lockForUpdate()
            ->first();

        if ($step === null) {
            $step = ConductorWorkflowStep::create([
                'workflow_id' => $workflow->id,
                'step_index' => $stepIndex,
                'name' => $name,
                'status' => StepStatus::Pending,
                'attempts' => 0,
            ]);
        }

        if ($step->status === StepStatus::Completed || $step->status === StepStatus::Skipped) {
            return $step->output;
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
                'output' => is_array($result) ? $result : (is_null($result) ? null : ['value' => $result]),
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
}
