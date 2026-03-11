<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConductorWorkflowStepResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'step_index' => $this->step_index,
            'status' => $this->status->value,
            'attempts' => $this->attempts,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'duration_ms' => $this->duration_ms,
            'error_message' => $this->error_message,
            'output' => $this->output,
        ];
    }
}
