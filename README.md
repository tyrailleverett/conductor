# Conductor

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hotreloadstudios/conductor.svg?style=flat-square)](https://packagist.org/packages/hotreloadstudios/conductor)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/hotreloadstudios/conductor/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/hotreloadstudios/conductor/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/hotreloadstudios/conductor/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/hotreloadstudios/conductor/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/hotreloadstudios/conductor.svg?style=flat-square)](https://packagist.org/packages/hotreloadstudios/conductor)

Conductor is a Laravel-native background job orchestration platform. It gives Laravel developers a self-hosted, code-first alternative to cloud services like trigger.dev and Inngest — bringing durable workflows, event-driven functions, and realtime visibility into background execution directly into their existing Laravel application.

It ships with a pre-compiled dashboard (React + shadcn/ui) accessible at `/conductor`, modelled on Laravel Horizon. No additional frontend tooling, Inertia, or Node.js setup is required in the host application.

## Features

- **Tracked Jobs** — Wrap any `ShouldQueue` job with status, duration, log capture, and retry/cancel actions
- **Durable Workflows** — Multi-step workflows with database-backed step persistence; resume from the last completed step after any failure
- **Event-driven Functions** — Dispatch named events and automatically execute registered listener functions as queued jobs
- **Scheduled Functions** — Define cron schedules as PHP classes; version-controlled and visible in the dashboard
- **Webhook Triggers** — Receive and verify inbound webhooks with HMAC signature checking, bound to handler functions
- **Realtime Log Streaming** — Stream live log output from running jobs via Server-Sent Events
- **Dashboard** — Pre-compiled SPA with job list, workflow timelines, event log, schedules, metrics charts, and worker health

## Requirements

- PHP 8.4+
- Laravel 11 or 12
- MySQL or PostgreSQL (required for durable workflow locking; SQLite is supported for development only)

## Installation

Install via Composer:

```bash
composer require hotreloadstudios/conductor
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="conductor-migrations"
php artisan migrate
```

Publish the dashboard assets:

```bash
php artisan conductor:publish
```

Publish the config file:

```bash
php artisan vendor:publish --tag="conductor-config"
```

## Configuration

The published `config/conductor.php` file:

```php
return [
    // URL prefix for the dashboard and API routes
    'path' => 'conductor',

    // Middleware applied to all Conductor routes
    'middleware' => ['web'],

    // Queue connection and queue name for Conductor's internal jobs
    'queue' => [
        'connection' => env('CONDUCTOR_QUEUE_CONNECTION', null),
        'queue'      => env('CONDUCTOR_QUEUE', 'conductor'),
    ],

    // Number of days to retain historical records before pruning
    'prune_after_days' => 7,

    // Worker heartbeat interval in seconds
    'heartbeat_interval' => 15,

    // Seconds without a heartbeat before a worker is considered offline
    'worker_timeout' => 60,

    // Keys whose values are masked before persisting payloads and logs
    'redact_keys' => [
        'password', 'token', 'authorization', 'secret',
        'api_key', 'cookie', 'x-signature', 'x-hub-signature',
    ],

    // Registered event functions and scheduled functions
    'functions' => [],

    // Registered webhook sources
    'webhooks' => [],

    // Rate limit for webhook ingestion — requests per minute per IP (null to disable)
    'webhook_rate_limit' => 60,
];
```

## Dashboard Access

Dashboard access is gated by a closure registered in your service provider, identical to how Horizon works:

```php
use Conductor\Facades\Conductor;

// In AppServiceProvider::boot()
Conductor::auth(function (Request $request): bool {
    return $request->user()?->hasRole('admin') ?? false;
});
```

In the `local` environment all users are allowed by default. In all other environments the gate must be explicitly configured, or a `403` is returned.

## Usage

### Tracked Jobs

Add the `Trackable` trait to any job to enable status tracking, log capture, and cooperative cancellation:

```php
use Conductor\Concerns\Trackable;

final class SendInvoiceJob implements ShouldQueue
{
    use Trackable;

    public function __construct(private readonly int $invoiceId) {}

    public function handle(): void
    {
        // job logic
    }
}
```

### Durable Workflows

Extend `Conductor\Workflow` and define steps via `$ctx->step()`. Each step's result is persisted so the workflow can resume from the last completed step on failure:

```php
final class WelcomeWorkflow extends Workflow
{
    public function __construct(private readonly User $user) {}

    public function handle(WorkflowContext $ctx): void
    {
        $ctx->step('send-welcome-email', function (): void {
            Mail::to($this->user)->send(new WelcomeEmail($this->user));
        });

        $ctx->sleep('3 days');

        $ctx->step('send-checkin-email', function (): void {
            Mail::to($this->user)->send(new CheckinEmail($this->user));
        });
    }
}
```

Dispatch a workflow like a standard job:

```php
WelcomeWorkflow::dispatch($user);
```

### Event Functions

Register event functions in `config/conductor.php`, then dispatch named events from anywhere:

```php
// config/conductor.php
'functions' => [
    OnUserCreated::class,
],
```

```php
final class OnUserCreated extends EventFunction
{
    public function listenTo(): string
    {
        return 'user.created';
    }

    public function handle(array $payload): void
    {
        // handle the event
    }
}
```

```php
ConductorEvent::dispatch('user.created', ['user_id' => $user->id]);
```

### Scheduled Functions

Register scheduled functions alongside event functions in `config/conductor.php`:

```php
final class DailyReportFunction extends ScheduledFunction
{
    public function schedule(): string
    {
        return '0 9 * * *';
    }

    public function handle(): void
    {
        // generate and send report
    }
}
```

### Webhook Triggers

Configure webhook sources in `config/conductor.php`:

```php
'webhooks' => [
    'stripe' => [
        'secret'   => env('STRIPE_WEBHOOK_SECRET'),
        'function' => HandleStripeWebhook::class,
    ],
],
```

Conductor verifies the HMAC signature before dispatching the bound function. Each source is accessible at `/conductor/webhook/{source}`.

## Artisan Commands

| Command | Description |
|---|---|
| `conductor:publish` | Publishes compiled frontend assets to `public/vendor/conductor/` |
| `conductor:prune` | Prunes records older than `prune_after_days` from all Conductor tables |
| `conductor:status` | Outputs a console health summary; exits with code `1` if any workers are offline |

## PHP-FPM & Octane Notes

**PHP-FPM**: Realtime log streaming via SSE holds an HTTP connection open for the duration of a job. Each open stream ties up one FPM worker process. Factor this into your FPM pool sizing for environments with many concurrent streams. Users on Laravel Octane (Swoole or RoadRunner) are unaffected.

**Octane fibers**: Under Laravel Octane with coroutines or fibers enabled, queue jobs must run as separate `artisan queue:work` processes — not as inline tasks within the Octane web server process — when using Conductor's log capture feature. The static log context holder is not fiber-safe and would cause cross-job log contamination otherwise.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
