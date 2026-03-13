# Conductor

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hotreloadstudios/conductor.svg?style=flat-square)](https://packagist.org/packages/hotreloadstudios/conductor)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/hotreloadstudios/conductor/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/hotreloadstudios/conductor/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/hotreloadstudios/conductor/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/hotreloadstudios/conductor/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/hotreloadstudios/conductor.svg?style=flat-square)](https://packagist.org/packages/hotreloadstudios/conductor)

Conductor is a Laravel-native background job orchestration platform. It gives Laravel developers a self-hosted, code-first alternative to cloud services like trigger.dev and Inngest — bringing durable workflows, event-driven functions, and realtime visibility into background execution directly into their existing Laravel application.

It ships with a pre-compiled dashboard (React + shadcn/ui) accessible at `/conductor`, modelled on Laravel Horizon. No host-side frontend tooling, Inertia integration, or Node.js setup is required.

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

## Getting Started

### 1. Install

```bash
composer require hotreloadstudios/conductor
```

### 2. Publish & Run Migrations

```bash
php artisan vendor:publish --tag="conductor-migrations"
php artisan migrate
```

### 3. Publish Dashboard Assets

```bash
php artisan conductor:publish
```

### 4. Configure Dashboard Access

In `AppServiceProvider::boot()`, configure who can access the dashboard. In `local`, all users are allowed by default. In all other environments, the gate must be explicitly set:

```php
use Conductor\Facades\Conductor;
use Illuminate\Http\Request;

Conductor::auth(function (Request $request): bool {
    return $request->user()?->hasRole('admin') ?? false;
});
```

### 5. Start a Queue Worker

```bash
php artisan queue:work --queue=conductor,default
```

The dashboard is now accessible at `/conductor`.

---

## Quick Examples

### Track a Job

Add `Trackable` to any `ShouldQueue` job. Conductor records status, duration, logs, and enables retry/cancel from the dashboard:

```php
use Conductor\Concerns\Trackable;

final class SendInvoiceJob implements ShouldQueue
{
    use Trackable;

    public function __construct(private readonly int $invoiceId) {}

    public function handle(): void
    {
        logger()->info('Sending invoice', ['id' => $this->invoiceId]);
        // ...
    }
}
```

### Define a Durable Workflow

Each step's result is persisted. On failure, the workflow resumes from the last completed step rather than restarting:

```php
use Conductor\Workflow;
use Conductor\WorkflowContext;

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

// Dispatch like a standard job
WelcomeWorkflow::dispatch($user);
```

### Dispatch an Event

```php
use HotReloadStudios\Conductor\Models\ConductorEvent;

ConductorEvent::dispatch('user.created', ['user_id' => $user->id]);
```

Register listener functions in `config/conductor.php`:

```php
'functions' => [
    OnUserCreated::class,
],
```

```php
use Conductor\EventFunction;

final class OnUserCreated extends EventFunction
{
    public function listenTo(): string { return 'user.created'; }

    public function handle(array $payload): void
    {
        // executes as a queued job
    }
}
```

### Register a Scheduled Function

```php
use Conductor\ScheduledFunction;

final class DailyReportFunction extends ScheduledFunction
{
    public function schedule(): string { return '0 9 * * *'; }

    public function handle(): void { /* generate report */ }
}
```

```php
// config/conductor.php
'functions' => [
    DailyReportFunction::class,
],
```

### Handle a Webhook

```php
// config/conductor.php
'webhooks' => [
    'stripe' => [
        'secret'   => env('STRIPE_WEBHOOK_SECRET'),
        'function' => HandleStripeWebhook::class,
    ],
],
```

Conductor verifies the HMAC signature and dispatches `HandleStripeWebhook` as a tracked job. The endpoint is `POST /conductor/webhook/stripe`.

---

## Documentation

Full documentation is in the [`docs/`](docs/) directory:

| Guide | Description |
|---|---|
| [Installation & Setup](docs/installation.md) | Requirements, install steps, migrations, and queue setup |
| [Configuration](docs/configuration.md) | Full `config/conductor.php` reference and environment variables |
| [Tracked Jobs](docs/tracked-jobs.md) | `Trackable` trait, status lifecycle, tagging, cooperative cancellation |
| [Durable Workflows](docs/workflows.md) | Multi-step workflows with step persistence, sleep, retry, and cancellation |
| [Event Functions](docs/event-functions.md) | Named event dispatch and registered listener functions |
| [Scheduled Functions](docs/scheduled-functions.md) | Cron-based PHP classes with dashboard visibility and toggle |
| [Webhooks](docs/webhooks.md) | Inbound webhook ingestion with HMAC verification |
| [Dashboard](docs/dashboard.md) | Dashboard pages, access control, and realtime log streaming |
| [Artisan Commands](docs/commands.md) | `conductor:publish`, `conductor:prune`, `conductor:status` |
| [API Reference](docs/api.md) | All JSON API endpoints |
| [Advanced Topics](docs/advanced.md) | PHP-FPM/Octane caveats, payload redaction, upgrading |

## Screenshots

### Overview

![Conductor overview](docs/screenshots/overview.png)

### Jobs

![Conductor jobs](docs/screenshots/jobs.png)

### Job Detail

![Conductor job detail](docs/screenshots/job-detail.png)

### Workflows

![Conductor workflows](docs/screenshots/workflows.png)

### Workflow Detail

![Conductor workflow detail](docs/screenshots/workflow-detail.png)

### Events

![Conductor events](docs/screenshots/events.png)

### Event Detail

![Conductor event detail](docs/screenshots/event-detail.png)

### Webhooks

![Conductor webhooks](docs/screenshots/webhooks.png)

### Schedules

![Conductor schedules](docs/screenshots/schedules.png)

### Metrics

![Conductor metrics](docs/screenshots/metrics.png)

### Queues

![Conductor queues](docs/screenshots/queues.png)

---

## Dashboard Development

Conductor ships with a pre-compiled React SPA in `resources/dist/`. This directory is committed to git so host applications do not need any frontend tooling. If you are contributing to or customising the dashboard source in `resources/js/`, follow these steps:

**Install frontend dependencies:**
```bash
npm install
```

**Start development server with HMR:**
```bash
npm run dev
```

**Build for production:**
```bash
npm run build
```

After building, commit the updated `resources/dist/` directory alongside your source changes.

**Publish assets to the host application:**
```bash
php artisan conductor:publish
```

> **Release enforcement:** CI verifies that `resources/dist/` matches the source before releases are tagged. Run `npm run build` and commit the output before opening a release PR.

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
