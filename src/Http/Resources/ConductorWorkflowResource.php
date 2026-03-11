<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConductorWorkflowResource extends JsonResource
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
            'current_step_index' => $this->current_step_index,
            'step_count' => $this->when(
                $this->relationLoaded('steps'),
                fn () => $this->steps->count(),
                fn () => $this->steps_count ?? 0,
            ),
            'created_at' => $this->created_at->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'steps' => ConductorWorkflowStepResource::collection($this->whenLoaded('steps')),
        ];
    }
}
