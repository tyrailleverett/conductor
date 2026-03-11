<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConductorScheduleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'function_class' => $this->function_class,
            'display_name' => $this->display_name,
            'cron_expression' => $this->cron_expression,
            'is_active' => $this->is_active,
            'last_run_at' => $this->last_run_at?->toIso8601String(),
            'next_run_at' => $this->next_run_at?->toIso8601String(),
            'last_run_status' => $this->last_run_status?->value,
        ];
    }
}
