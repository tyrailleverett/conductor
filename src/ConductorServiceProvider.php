<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor;

use HotReloadStudios\Conductor\Concerns\Trackable;
use HotReloadStudios\Conductor\Enums\JobStatus;
use HotReloadStudios\Conductor\Exceptions\JobCancelledException;
use HotReloadStudios\Conductor\Http\Middleware\Authorize;
use HotReloadStudios\Conductor\Logging\ConductorLogHandler;
use HotReloadStudios\Conductor\Models\ConductorJob;
use HotReloadStudios\Conductor\Services\JobCancellationService;
use HotReloadStudios\Conductor\Services\JobRetryService;
use HotReloadStudios\Conductor\Services\PayloadRedactor;
use HotReloadStudios\Conductor\Services\WorkflowCancellationService;
use HotReloadStudios\Conductor\Services\WorkflowEngine;
use HotReloadStudios\Conductor\Support\ConductorContext;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use ReflectionProperty;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class ConductorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('conductor')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations([
                'create_conductor_jobs_table',
                'create_conductor_job_logs_table',
                'create_conductor_workflows_table',
                'create_conductor_workflow_steps_table',
                'create_conductor_events_table',
                'create_conductor_event_runs_table',
                'create_conductor_schedules_table',
                'create_conductor_workers_table',
                'create_conductor_webhook_sources_table',
                'create_conductor_webhook_logs_table',
                'create_conductor_metric_snapshots_table',
            ])
            ->hasRoute('web');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(Conductor::class);
        $this->app->singleton(PayloadRedactor::class);
        $this->app->singleton(JobRetryService::class);
        $this->app->singleton(JobCancellationService::class);
        $this->app->singleton(WorkflowEngine::class);
        $this->app->singleton(WorkflowCancellationService::class);
    }

    public function packageBooted(): void
    {
        Route::prefix((string) config('conductor.path').'/api')
            ->middleware(array_merge((array) config('conductor.middleware', ['web']), [Authorize::class]))
            ->group(__DIR__.'/../routes/api.php');

        Route::prefix((string) config('conductor.path').'/webhook')
            ->middleware((array) config('conductor.middleware', ['web']))
            ->group(__DIR__.'/../routes/webhook.php');

        if (! $this->app->environment('local') && ! $this->hasAuthCallbackConfigured()) {
            Log::warning('Conductor dashboard and API routes will return 403 until Conductor::auth() is configured.');
        }

        $this->registerQueueListeners();

        // Register the log handler once — it self-guards via ConductorContext::isActive().
        $this->registerLogHandler();
    }

    private function registerLogHandler(): void
    {
        $logger = Log::getLogger();

        if ($logger instanceof \Monolog\Logger) {
            $logger->pushHandler(new ConductorLogHandler());
        }
    }

    private function registerQueueListeners(): void
    {
        Queue::createPayloadUsing(function (string $connection, ?string $queue, array $payload): array {
            /** @var array{displayName?: string, data?: array{command?: string|object}} $payload */
            $queue ??= 'default';
            $commandData = $payload['data'] ?? [];
            $commandRaw = $commandData['command'] ?? null;

            // The command is an object when createPayloadUsing fires (before serialization).
            // It may be a string if already serialized (e.g., from another hook).
            if (is_object($commandRaw)) {
                $command = $commandRaw;
            } elseif (is_string($commandRaw)) {
                $command = unserialize($commandRaw);
                if (! is_object($command)) {
                    return $payload;
                }
            } else {
                return $payload;
            }

            if (! $this->usesTrackableTrait($command)) {
                return $payload;
            }

            /** @var mixed $command */

            // If conductorJobId is already set, this is a retry — reuse the existing record.
            if ($command->conductorJobId !== null) {
                return $payload;
            }

            $redactor = $this->app->make(PayloadRedactor::class);

            // Create the record first so we can set conductorJobId and embed it in
            // the serialized command (which feeds the encrypted retry payload).
            $conductorJob = ConductorJob::create([
                'uuid' => Str::uuid()->toString(),
                'class' => $command::class,
                'display_name' => $command->displayName(),
                'status' => JobStatus::Pending,
                'queue' => $queue,
                'connection' => $connection,
                'tags' => $redactor->redact($command->conductorTags()),
                'max_attempts' => isset($command->tries) ? (int) $command->tries : null,
            ]);

            // Mutate the job object in-place — since $command references the same PHP object
            // as $payload['data']['command'], Laravel's subsequent serialize(clone $job) will
            // produce a payload that includes conductorJobId.
            $command->conductorJobId = $conductorJob->id;

            $serialized = serialize($command);
            $encryptedRetry = Crypt::encryptString($serialized);
            $displayPayload = $redactor->redact((array) $command);

            $conductorJob->update([
                'payload' => [
                    'display' => $displayPayload,
                    'retry' => $encryptedRetry,
                ],
            ]);

            return $payload;
        });

        Queue::before(function (JobProcessing $event): void {
            /** @var array{conductorJobId?: int} $payload */
            $payload = $event->job->payload();
            $conductorJobId = $this->extractConductorJobId($payload);

            if ($conductorJobId === null) {
                return;
            }

            ConductorJob::where('id', $conductorJobId)->update([
                'status' => JobStatus::Running,
                'started_at' => now(),
                'attempts' => DB::raw('attempts + 1'),
            ]);

            ConductorContext::set($conductorJobId);
        });

        Queue::after(function (JobProcessed $event): void {
            /** @var array{conductorJobId?: int} $payload */
            $payload = $event->job->payload();
            $conductorJobId = $this->extractConductorJobId($payload);

            if ($conductorJobId === null) {
                return;
            }

            $conductorJob = ConductorJob::find($conductorJobId);

            if ($conductorJob !== null) {
                $finalStatus = $conductorJob->status === JobStatus::CancellationRequested
                    ? JobStatus::Cancelled
                    : JobStatus::Completed;

                $conductorJob->update([
                    'status' => $finalStatus,
                    'completed_at' => now(),
                    'cancelled_at' => $finalStatus === JobStatus::Cancelled ? now() : null,
                    'duration_ms' => $conductorJob->started_at !== null
                        ? (int) $conductorJob->started_at->diffInMilliseconds(now())
                        : null,
                ]);
            }

            ConductorContext::clear();
        });

        Queue::failing(function (JobFailed $event): void {
            /** @var array{conductorJobId?: int} $payload */
            $payload = $event->job->payload();
            $conductorJobId = $this->extractConductorJobId($payload);

            if ($conductorJobId === null) {
                return;
            }

            $conductorJob = ConductorJob::find($conductorJobId);

            if ($conductorJob !== null) {
                $isCancellation = $event->exception instanceof JobCancelledException;

                $conductorJob->update([
                    'status' => $isCancellation ? JobStatus::Cancelled : JobStatus::Failed,
                    'failed_at' => $isCancellation ? null : now(),
                    'cancelled_at' => $isCancellation ? now() : null,
                    'error_message' => $isCancellation ? null : $event->exception->getMessage(),
                    'stack_trace' => $isCancellation ? null : $event->exception->getTraceAsString(),
                    'duration_ms' => $conductorJob->started_at !== null
                        ? (int) $conductorJob->started_at->diffInMilliseconds(now())
                        : null,
                ]);
            }

            ConductorContext::clear();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractConductorJobId(array $payload): ?int
    {
        $data = $payload['data'] ?? null;

        if (! is_array($data)) {
            return null;
        }

        $commandRaw = $data['command'] ?? null;

        if (! is_string($commandRaw)) {
            return null;
        }

        $command = unserialize($commandRaw);

        if (! is_object($command) || ! $this->usesTrackableTrait($command)) {
            return null;
        }

        /** @var mixed $command */
        return $command->conductorJobId;
    }

    private function usesTrackableTrait(mixed $object): bool
    {
        if (! is_object($object)) {
            return false;
        }

        return in_array(Trackable::class, class_uses_recursive($object), true);
    }

    private function hasAuthCallbackConfigured(): bool
    {
        $property = new ReflectionProperty(Conductor::class, 'authUsing');

        return $property->getValue() !== null;
    }
}
