<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConductorWebhookSourceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->source,
            'source' => $this->source,
            'function_class' => $this->function_class,
            'is_active' => $this->is_active,
            'logs_count' => $this->when(isset($this->logs_count), $this->logs_count),
        ];
    }
}
