## Conductor

Conductor is a Laravel-native background job orchestration package. It provides durable workflows, event-driven
functions, scheduled functions, webhook triggers, tracked jobs, and a pre-compiled dashboard at `/conductor`. It is a
self-hosted alternative to services like Trigger.dev and Inngest.

## Installation

```bash
composer require hotreloadstudios/conductor
php artisan vendor:publish --tag="conductor-migrations"
php artisan migrate
php artisan conductor:publish
```

Configure dashboard access in `AppServiceProvider::boot()`:

```php
use HotReloadStudios\Conductor\Facades\Conductor;
use Illuminate\Http\Request;

Conductor::auth(function (Request $request): bool {
return $request->user()?->hasRole('admin') ?? false;
});
```

## Configuration

The config file is `config/conductor.php`. Key options:

- `path` — Dashboard URL prefix (default: `conductor`)
- `queue.queue` — Queue name for Conductor jobs (default: `conductor`)
- `prune_after_days` — How long to retain job records (default: `7`)
- `heartbeat_interval` — Worker heartbeat frequency in seconds (default: `15`)
- `functions` — Array of `EventFunction` and `ScheduledFunction` class names
- `webhooks` — Keyed array of webhook sources with `secret` and `function`

## Tracked Jobs

Add the `Trackable` trait to any `ShouldQueue` job to enable status tracking, duration recording, log capture, and
retry/cancel from the dashboard.

@verbatim
    <code-snippet name="Tracked Job" lang="php">
        use HotReloadStudios\Conductor\Concerns\Trackable;

        final class SendInvoiceJob implements ShouldQueue
        {
        use Trackable;

        public function __construct(private readonly int $invoiceId) {}

        public function handle(): void
        {
        logger()->info('Processing invoice', ['id' => $this->invoiceId]);
        // ...
        }
        }
    </code-snippet>
@endverbatim

## Durable Workflows

Extend `HotReloadStudios\Conductor\Workflow` and implement `handle(WorkflowContext $ctx)`. Each `$ctx->step()` persists
its result to the database; on failure the workflow resumes from the last completed step. Dispatch like a standard job.

@verbatim
    <code-snippet name="Durable Workflow" lang="php">
        use HotReloadStudios\Conductor\Workflow;
        use HotReloadStudios\Conductor\WorkflowContext;

        final class WelcomeWorkflow extends Workflow
        {
        public function __construct(private readonly User $user) {}

        public function handle(WorkflowContext $ctx): void
        {
        $ctx->step('send-welcome-email', function (): void {
        Mail::to($this->user)->send(new WelcomeEmail($this->user));
        });

        $ctx->sleep('3 days'); // pause; resumes after the duration

        $ctx->step('send-checkin-email', function (): void {
        Mail::to($this->user)->send(new CheckinEmail($this->user));
        });
        }
        }

        // Dispatch
        WelcomeWorkflow::dispatch($user);
    </code-snippet>
@endverbatim

`WorkflowContext` methods:
- `step(string $name, Closure $callback): mixed` — Runs a named, idempotent step
- `sleep(string|int $duration): void` — Pauses the workflow (e.g. `'3 days'`, `86400`)
- `skip(string $name): void` — Marks a step as skipped without executing it

`$stepMaxAttempts` (default `3`) controls per-step retry attempts.

## Event Functions

Extend `HotReloadStudios\Conductor\EventFunction`, implement `listenTo(): string` and `handle(array $payload): void`.
Register in `config/conductor.php` under `functions`.

@verbatim
    <code-snippet name="Event Function" lang="php">
        use HotReloadStudios\Conductor\EventFunction;

        final class OnUserCreated extends EventFunction
        {
        public function listenTo(): string
        {
        return 'user.created';
        }

        public function handle(array $payload): void
        {
        // executed as a queued job
        }
        }
    </code-snippet>
@endverbatim

Dispatch an event:

@verbatim
    <code-snippet name="Dispatch Event" lang="php">
        use HotReloadStudios\Conductor\Models\ConductorEvent;

        ConductorEvent::dispatch('user.created', ['user_id' => $user->id]);
    </code-snippet>
@endverbatim

## Scheduled Functions

Extend `HotReloadStudios\Conductor\ScheduledFunction`, implement `schedule(): string` and `handle(): void`. Register in
`config/conductor.php` under `functions`.

`schedule()` accepts cron expressions (`'0 9 * * *'`) or named frequencies: `daily`, `hourly`, `weekly`, `monthly`,
`yearly`, `everyMinute`, `everyFiveMinutes`, `everyTenMinutes`, `everyFifteenMinutes`, `everyThirtyMinutes`.

@verbatim
    <code-snippet name="Scheduled Function" lang="php">
        use HotReloadStudios\Conductor\ScheduledFunction;

        final class DailyReportFunction extends ScheduledFunction
        {
        public function schedule(): string
        {
        return '0 9 * * *';
        }

        public function handle(): void
        {
        // generate report
        }
        }
    </code-snippet>
@endverbatim

## Webhook Functions

Configure webhooks in `config/conductor.php` under `webhooks`. Each entry requires a `secret` for HMAC verification and
a `function` class name. The endpoint is `POST /conductor/webhook/{source}`. The handler class must extend
`HotReloadStudios\Conductor\WebhookFunction`.

@verbatim
    <code-snippet name="Webhook Config" lang="php">
        // config/conductor.php
        'webhooks' => [
        'stripe' => [
        'secret' => env('STRIPE_WEBHOOK_SECRET'),
        'function' => HandleStripeWebhook::class,
        ],
        ],
    </code-snippet>
@endverbatim

@verbatim
    <code-snippet name="Webhook Function" lang="php">
        use HotReloadStudios\Conductor\WebhookFunction;

        final class HandleStripeWebhook extends WebhookFunction
        {
        public function handle(array $payload, string $source): void
        {
        // $source is the webhook key e.g. 'stripe'
        }
        }
    </code-snippet>
@endverbatim

## Artisan Commands

- `php artisan conductor:publish` — Publish pre-compiled dashboard assets
- `php artisan conductor:status` — Show worker and queue status
- `php artisan conductor:prune` — Prune old job records (respects `prune_after_days`)

## Queue Setup

Run a dedicated queue worker for Conductor jobs:

```bash
php artisan queue:work --queue=conductor,default
```

MySQL or PostgreSQL is required for durable workflow locking. SQLite is supported for development only.