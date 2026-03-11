<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Jobs;

use HotReloadStudios\Conductor\Enums\WebhookLogStatus;
use HotReloadStudios\Conductor\Models\ConductorWebhookLog;
use HotReloadStudios\Conductor\WebhookFunction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class WebhookFunctionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<mixed>  $payload
     */
    public function __construct(
        public readonly int $webhookLogId,
        public readonly string $source,
        public readonly string $functionClass,
        public readonly array $payload,
    ) {
        $this->queue = (string) config('conductor.queue.queue');

        $connection = config('conductor.queue.connection');

        if (is_string($connection) && $connection !== '') {
            $this->connection = $connection;
        }
    }

    public function handle(): void
    {
        /** @var ConductorWebhookLog|null $webhookLog */
        $webhookLog = ConductorWebhookLog::find($this->webhookLogId);

        if ($webhookLog === null) {
            return;
        }

        /** @var WebhookFunction $function */
        $function = new $this->functionClass();

        try {
            $function->handle($this->payload, $this->source);
        } catch (Throwable $e) {
            $webhookLog->update(['status' => WebhookLogStatus::Failed]);

            throw $e;
        }
    }
}
