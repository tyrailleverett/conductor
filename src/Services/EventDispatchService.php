<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Services;

use HotReloadStudios\Conductor\Enums\EventRunStatus;
use HotReloadStudios\Conductor\EventFunction;
use HotReloadStudios\Conductor\Jobs\EventFunctionJob;
use HotReloadStudios\Conductor\Models\ConductorEvent;
use HotReloadStudios\Conductor\Models\ConductorEventRun;
use Illuminate\Support\Str;

final class EventDispatchService
{
    public function __construct(private readonly PayloadRedactor $payloadRedactor) {}

    public function dispatch(string $eventName, array $payload = []): ConductorEvent
    {
        $redactedPayload = $this->payloadRedactor->redact($payload);

        $event = ConductorEvent::create([
            'uuid' => Str::uuid()->toString(),
            'name' => $eventName,
            'payload' => $redactedPayload,
            'dispatched_at' => now(),
        ]);

        /** @var array<string> $functions */
        $functions = config('conductor.functions', []);

        foreach ($functions as $functionClass) {
            if (! is_subclass_of($functionClass, EventFunction::class)) {
                continue;
            }

            /** @var EventFunction $function */
            $function = new $functionClass();

            if ($function->listenTo() !== $eventName) {
                continue;
            }

            $eventRun = ConductorEventRun::create([
                'event_id' => $event->id,
                'function_class' => $functionClass,
                'status' => EventRunStatus::Pending,
            ]);

            EventFunctionJob::dispatch($eventRun->id, $functionClass, $payload);
        }

        return $event;
    }
}
