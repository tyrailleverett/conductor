<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConductorJobResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'class' => $this->class,
            'display_name' => $this->display_name,
            'status' => $this->status->value,
            'queue' => $this->queue,
            'connection' => $this->connection,
            'tags' => $this->tags,
            'attempts' => $this->attempts,
            'max_attempts' => $this->max_attempts,
            'is_cancellable' => $this->isCancellable(),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'failed_at' => $this->failed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'duration_ms' => $this->duration_ms,
            'error_message' => $this->error_message,
            'stack_trace' => $this->whenLoaded('logs', fn (): ?string => $this->stack_trace),
            'logs' => ConductorJobLogResource::collection($this->whenLoaded('logs')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
