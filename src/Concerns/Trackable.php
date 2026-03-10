<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Concerns;

use HotReloadStudios\Conductor\Enums\JobStatus;
use HotReloadStudios\Conductor\Models\ConductorJob;

trait Trackable
{
    public ?int $conductorJobId = null;

    /** @var array<string> */
    public array $conductorTags = [];

    public bool $conductorCancellable = false;

    /**
     * @return array<string>
     */
    public function conductorTags(): array
    {
        return $this->conductorTags;
    }

    public function displayName(): string
    {
        return class_basename(static::class);
    }

    public function shouldCancelConductorJob(): bool
    {
        if ($this->conductorJobId === null) {
            return false;
        }

        $job = ConductorJob::find($this->conductorJobId);

        return $job !== null && $job->status === JobStatus::CancellationRequested;
    }

    public function markAsCancellable(): void
    {
        $this->conductorCancellable = true;

        if ($this->conductorJobId !== null) {
            ConductorJob::where('id', $this->conductorJobId)->update([
                'cancellable_at' => now(),
            ]);
        }
    }
}
