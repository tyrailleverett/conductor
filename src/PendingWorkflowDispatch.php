<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor;

use DateTimeInterface;
use HotReloadStudios\Conductor\Enums\WorkflowStatus;
use HotReloadStudios\Conductor\Jobs\WorkflowStepJob;
use HotReloadStudios\Conductor\Models\ConductorWorkflow;
use Illuminate\Support\Str;

final class PendingWorkflowDispatch
{
    private ?string $queue = null;

    private ?string $connection = null;

    private DateTimeInterface|int|null $delay = null;

    private bool $dispatched = false;

    /**
     * @param  array<mixed>  $arguments
     */
    public function __construct(
        private readonly string $workflowClass,
        private readonly array $arguments,
    ) {}

    public function __destruct()
    {
        if (! $this->dispatched) {
            $this->dispatch();
        }
    }

    public function onQueue(string $queue): static
    {
        $this->queue = $queue;

        return $this;
    }

    public function onConnection(string $connection): static
    {
        $this->connection = $connection;

        return $this;
    }

    public function delay(DateTimeInterface|int $delay): static
    {
        $this->delay = $delay;

        return $this;
    }

    public function dispatch(): ConductorWorkflow
    {
        $this->dispatched = true;

        /** @var Workflow $tempInstance */
        $tempInstance = new ($this->workflowClass)(...$this->arguments);

        $workflow = ConductorWorkflow::create([
            'uuid' => Str::uuid()->toString(),
            'class' => $this->workflowClass,
            'display_name' => $tempInstance->displayName(),
            'status' => WorkflowStatus::Pending,
            'input' => $this->arguments,
        ]);

        $job = WorkflowStepJob::dispatch($workflow->id);

        if ($this->queue !== null) {
            $job->onQueue($this->queue);
        }

        if ($this->connection !== null) {
            $job->onConnection($this->connection);
        }

        if ($this->delay !== null) {
            $job->delay($this->delay);
        }

        return $workflow;
    }
}
