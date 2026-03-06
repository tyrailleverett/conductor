# Conductor — Product Requirements Document

> **Version:** 1.0  
> **Date:** March 5, 2026  
> **Status:** Draft

---

## 1. Product Overview

Conductor is a Laravel-native background job orchestration platform distributed as a Composer package. It gives Laravel developers a self-hosted, code-first alternative to cloud services like trigger.dev and Inngest — bringing durable workflows, event-driven functions, and realtime visibility into background execution directly into their existing Laravel application.

Existing solutions require developers to route their workloads to external SaaS platforms, introducing network latency, data egress concerns, vendor lock-in, and additional monthly costs. Laravel's built-in queue system is powerful but lacks first-class support for multi-step durable workflows, event chaining, and a unified observability dashboard. Conductor closes this gap by building on top of Laravel's existing infrastructure without replacing it.

Conductor ships with a pre-compiled, self-contained dashboard built on React and shadcn/ui, accessible via a gated route in the host application (modelled on Laravel Horizon). The package serves a Blade shell that boots a standalone React SPA against Conductor-owned JSON and SSE endpoints. No host-side frontend tooling, Inertia installation, or Node.js setup is required in the host application.

### Product Principles

1. **Laravel-native by default.** Works with any existing queue driver, respects existing configuration, and introduces no new infrastructure requirements.
2. **Code-first.** Functions, workflows, events, and schedules are defined as PHP classes — not YAML, not config files, not GUI builders.
3. **Durable over best-effort.** Multi-step workflows persist step results to the database so that failures are resumable, not restartable.
4. **Zero host app coupling.** The dashboard is fully self-contained. Installing the package should not require any changes to the host application's frontend stack.
5. **Operationally lightweight.** Conductor should add minimal overhead when idle. Monitoring and visibility without performance cost.

---

## 2. Target Users

### Primary Persona: Laravel Application Developer

- Builds production Laravel applications and uses queued jobs regularly
- Frustrated by the lack of visibility into job failures, retries, and execution history
- Wants multi-step workflows with automatic retry/resume without building custom state machines
- Values staying within the Laravel ecosystem and not introducing SaaS dependencies for core background processing

### Secondary Persona: Engineering Team Lead / DevOps Lead

- Responsible for reliability and observability of production systems
- Needs a dashboard for monitoring queue health, worker status, and failure rates
- Wants scheduled jobs visible and controllable in one place without SSHing into servers
- Values access control — the dashboard should be gated and auditable

---

## 3. User Stories

### Laravel Application Developer

- As a developer, I want to define a multi-step workflow as a PHP class so that I can describe a complex async process in one place.
- As a developer, I want each step in a workflow to be automatically retried on failure so that transient errors don't require manual intervention.
- As a developer, I want step results persisted to the database so that a workflow can resume from its last completed step after a server restart.
- As a developer, I want to fire named events with a payload and have registered functions automatically execute so that I can decouple producers from consumers.
- As a developer, I want to define cron-based scheduled functions in PHP so that my schedules are version-controlled and not hidden in a cron tab.
- As a developer, I want to trigger functions from incoming webhooks so that external services can drive internal workflows.
- As a developer, I want to stream real-time logs from a running job in the dashboard so that I can observe long-running tasks without SSH.

### Engineering Team Lead

- As a team lead, I want to restrict dashboard access to authenticated admin users so that sensitive job data is not publicly accessible.
- As a team lead, I want to see aggregate metrics — throughput, failure rate, queue depth — over time so that I can detect degradation trends.
- As a team lead, I want to retry failed jobs and cancel pending or cooperatively cancellable running jobs from the dashboard without a deployment or database query.
- As a team lead, I want to see all scheduled functions and their next/last execution time in one view.
- As a team lead, I want to see queue worker health status so I know immediately if workers have stopped.

---

## 4. System Architecture

Conductor is a self-contained Laravel package. It registers its own routes, database migrations, service provider, and pre-compiled frontend assets. It does not modify the host application's routing, middleware stack, or frontend assets, except by appending its own route group.

**Deployment model**: Self-hosted, running within the host Laravel application's process. No separate server or agent is required.

**Key components:**

| Component | Responsibility |
|---|---|
| **Service Provider** | Registers routes, publishes assets, configures the auth gate, binds services |
| **Job Runner** | Wraps standard Laravel jobs with Conductor tracking (status, duration, logs) |
| **Workflow Engine** | Executes multi-step workflows using database-backed step state |
| **Event Bus** | Dispatches named events, resolves and dispatches registered listener functions |
| **Schedule Registry** | Registers Conductor-defined schedules into Laravel's scheduler |
| **Webhook Handler** | Receives, verifies, and routes inbound webhook payloads to registered functions |
| **Dashboard API** | JSON endpoints consumed by the SPA dashboard |
| **Dashboard SPA** | Pre-compiled React + shadcn/ui SPA served from package assets |

**Communication (Dashboard ↔ Backend):**

| Direction | Method | Details |
|---|---|---|
| SPA → Backend | HTTP JSON API | REST-style JSON endpoints under `/conductor/api/*` |
| Backend → SPA | Server-Sent Events (SSE) | Realtime log streaming via `/conductor/api/jobs/{id}/stream` |
| External → Conductor | HTTP POST | Webhook ingestion at `/conductor/webhook/{source}` |

**Frontend asset delivery**: The package ships pre-compiled JS and CSS in `resources/dist/`. The service provider publishes these to `public/vendor/conductor/`. A single Blade view (`conductor.blade.php`) serves as the HTML shell that bootstraps the React SPA. All SPA navigation is client-side via React Router.

> **Why not Inertia**: Conductor is a package dashboard, not an application shell. Using Inertia would couple the package to the host application's page lifecycle, middleware expectations, and frontend conventions. Conductor instead serves a plain Blade shell plus compiled static assets, and the SPA talks to package-owned JSON and SSE endpoints over standard same-origin HTTP.

> **Frontend implementation**: The development environment uses Vite + React, but the distributed package ships compiled static assets. The host application does not need Inertia, a frontend build step, or any changes to its existing rendering stack.

**SPA deep-link routing**: A wildcard route `GET /conductor/{any?}` (excluding `/conductor/api/*` and `/conductor/webhook/*`) returns the Blade shell on all matched paths. This ensures navigating directly to a URL such as `/conductor/jobs/abc-123` returns the SPA shell rather than a 404, allowing React Router to handle client-side routing.

---

## 5. Core Entities

| Entity | Description |
|---|---|
| **ConductorJob** | A tracked background job. Wraps any Laravel Queueable with status, timing, log, and retry metadata. |
| **ConductorWorkflow** | A named, multi-step durable workflow instance. Tracks overall status and input/output. |
| **ConductorWorkflowStep** | A single step within a workflow. Persists per-step result, status, duration, and error. |
| **ConductorEvent** | A named event dispatched through Conductor's event bus, with payload and dispatch timestamp. |
| **ConductorEventRun** | A single function execution triggered by a specific event dispatch. |
| **ConductorSchedule** | A registered scheduled function with its cron expression, last/next run metadata, and enabled state. |
| **ConductorWebhookSource** | A registered webhook source with its verification secret and bound function. |
| **ConductorWorker** | A heartbeat record for a queue worker process, including queue assignment, current job, and last-seen timestamp. |
| **ConductorMetricSnapshot** | Periodic aggregate metric snapshots (job count, failure count, throughput) for chart rendering. |

---

## 6. MVP Feature Set

### 6.1 Background Jobs & Tasks

- **Tracked Job Dispatch** — Any standard Laravel `Queueable` can be tracked by dispatching through Conductor's dispatcher or by using the `Trackable` trait. Conductor records status, queue, connection, attempt count, duration, and any thrown exceptions.
- **Job Tagging** — Jobs can be tagged with arbitrary strings for filtering in the dashboard and API. Tags are assigned per-job by setting the `$conductorTags` property or overriding `conductorTags(): array` in the job class to return a dynamic list. Tags are stored as a JSON array in `conductor_jobs.tags` at dispatch time. Both the dashboard Jobs page and the `GET /conductor/api/jobs` endpoint support filtering by a single tag via the `?tag=` query parameter. Tag values are arbitrary strings — Conductor does not validate or deduplicate tag names. Example usage:

```php
final class ProcessPaymentJob implements ShouldQueue
{
    use Trackable;

    public function conductorTags(): array
    {
        return ['billing', "invoice:{$this->invoiceId}"];
    }
}
```
- **Automatic Log Capture** — Log output from within a tracked job is captured and stored per-run, streamable in realtime.
- **Status Lifecycle** — `pending → running → completed | failed | cancellation_requested | cancelled`. Retries increment attempt count and record per-attempt error details.
- **Cancellation semantics** — Pending jobs may be cancelled immediately before execution. Running jobs are cancellable only when they opt into Conductor's cooperative cancellation checks via the `Trackable` integration; Conductor marks them as `cancellation_requested`, and the job exits cleanly on its next checkpoint. Failed jobs cannot be cancelled and may only be retried or dismissed in the UI.
- **Retry semantics** — Manual retry from the dashboard re-dispatches the original job payload and increments `attempts` on the existing `conductor_jobs` record. No new record is created on retry, preserving the full failure history. Manual retry bypasses `max_attempts` and is always accepted. Automatic retries driven by Laravel's queue system also increment the same `attempts` counter and do respect `max_attempts`.
- **Display name** — Each tracked job and workflow stores a `display_name` derived from the short class name by default. Jobs and workflows may override `displayName(): string` to provide a custom label shown in the dashboard.

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

### 6.2 Durable Multi-step Workflows

- **Workflow Definition** — Workflows extend `Conductor\Workflow` and implement `handle(WorkflowContext $ctx)`. Steps are defined as named closures passed to `$ctx->step()`.
- **Step Persistence** — Each step's output is stored in `conductor_workflow_steps` immediately on completion. On resume, already-completed steps return their persisted result without re-executing. Each workflow step is uniquely identified by `(workflow_id, step_index)` and persisted before any subsequent step is scheduled.
- **Step Retry** — Failed steps are retried independently up to a configurable `$stepMaxAttempts`.
- **Sleep / Delay** — `$ctx->sleep()` schedules the next step dispatch after a given interval without holding a queue worker. The workflow row stores `sleep_until` / `next_run_at` so delayed continuations survive process restarts and can be queried efficiently.
- **Workflow Dispatch** — Workflows are dispatched like jobs: `WelcomeWorkflow::dispatch($user)`.
- **Concurrent execution safety** — Before executing any step, the workflow engine acquires a pessimistic lock (`SELECT ... FOR UPDATE`) on the `conductor_workflows` row and then the `conductor_workflow_steps` row for the current step inside the same transaction. If the step row does not yet exist, it is created first under the workflow lock. If the lock cannot be acquired because another worker is already progressing the workflow, the job is released back to the queue. This prevents double-execution when multiple queue workers process the same workflow continuation simultaneously.
- **Forward-compatible waiting state** — The workflow data model reserves `waiting` status and a nullable `waiting_for_event` field for future wait-for-event support, even though event waiting is out of scope for MVP.
- **Workflow cancellation** — A workflow in `pending` or `running` status may be cancelled via `DELETE /conductor/api/workflows/{id}`. Cancellation sets the workflow status to `cancelled` and marks any not-yet-started steps as `skipped`. If the currently executing step is backed by a `Trackable` job, cooperative cancellation is requested; otherwise the active step runs to completion and the workflow halts before advancing to the next step.
- **Locking database requirement** — The pessimistic locking strategy (`SELECT ... FOR UPDATE`) requires `conductor_workflows` to reside in a relational database (MySQL or PostgreSQL). When using Redis or SQS as the queue driver, the lock is still acquired on the application's default database connection, not the queue connection. SQLite is supported for development but has limited lock semantics under concurrent writes.

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

### 6.3 Event-driven Functions

- **Event Dispatch** — `ConductorEvent::dispatch('user.created', ['user_id' => $user->id])` fires a named event, records it to `conductor_events`, and dispatches all registered listener functions as queued jobs.
- **Explicit Registration** — Event functions must be registered in `config/conductor.php` under the `functions` key. There is no automatic class discovery. This keeps registration explicit, version-controlled, and free of filesystem scanning.
- **Multiple Listeners** — Multiple functions can listen to the same event and execute in parallel.
- **Event Log** — All events and their triggered function runs are queryable, with payload and status stored.

```php
// In config/conductor.php
'functions' => [
    OnUserCreated::class,
    SendWelcomeEmail::class,
    ProvisionUserResources::class,
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
        // provision user resources
    }
}
```

### 6.4 Scheduled / Cron Functions

- **Schedule Definition** — Functions extend `Conductor\ScheduledFunction` and define `public function schedule(): string` returning a cron expression or named frequency (`'daily'`, `'hourly'`, etc.).
- **Explicit Registration** — Scheduled functions must be registered in `config/conductor.php` under the `functions` key alongside event functions. The service provider iterates the registered list, identifies `ScheduledFunction` subclasses, and registers each into Laravel's scheduler via `$schedule->call()`. No filesystem scanning is performed.
- **Dashboard Visibility** — All registered schedules appear in the dashboard with cron expression, next run time, last run time, and last run status.
- **Enable/Disable** — Schedules can be toggled on/off from the dashboard without code changes.

```php
final class DailyReportFunction extends ScheduledFunction
{
    public function schedule(): string
    {
        return '0 9 * * *'; // or 'daily'
    }

    public function handle(): void
    {
        // generate and send report
    }
}
```

### 6.5 Webhook Triggers

- **Webhook Ingestion** — The package registers a `/conductor/webhook/{source}` POST route for each configured source.
- **Signature Verification** — Each webhook source is configured with a secret; Conductor verifies the HMAC signature before processing.
- **Function Binding** — Each webhook source is bound to a `WebhookFunction` that receives the verified payload.
- **Webhook Log** — All inbound webhook requests are logged with payload, source, and processing status.

```php
// In config/conductor.php
'webhooks' => [
    'stripe' => [
        'secret' => env('STRIPE_WEBHOOK_SECRET'),
        'function' => HandleStripeWebhook::class,
    ],
],
```

### 6.6 Realtime Log Streaming

- **SSE Stream** — Each running job/workflow exposes a Server-Sent Events endpoint at `/conductor/api/jobs/{id}/stream`. The SPA opens this stream when viewing a job detail page and appends log lines as they arrive.
- **SSE implementation** — The SSE handler queries `conductor_job_logs WHERE job_id = :id AND logged_at > :last_seen` on a 500 ms polling interval, flushing each new batch as `data:` events. When the job transitions to a terminal status (`completed`, `failed`, or `cancelled`), the handler emits a final `data: {"event":"done"}` and closes the connection.
- **Context-aware log capture** — Log output is captured using a custom Monolog handler (`ConductorLogHandler`) pushed onto the host application's logging stack for the duration of a tracked job. The handler uses a process-scoped context registry (`ConductorContext`) — a simple static holder set to the current job's ID at the start of `handle()` and cleared on completion. This is the same pattern Telescope uses for its `ExceptionContext`. Concurrent jobs in separate worker processes each maintain their own context, so logs are always attributed to the correct job.
- **Polling fallback** — For environments where SSE is not suitable (see FPM caveat below), the dashboard detects a closed or failed SSE connection and falls back to polling `/conductor/api/jobs/{id}` every 2 seconds to refresh log entries.

> **PHP-FPM caveat**: SSE streams hold an HTTP connection open for the duration of the job. Under PHP-FPM, each open stream ties up one FPM worker process. This is acceptable for infrequent operational use but should be factored into FPM pool sizing. Users on Laravel Octane (Swoole or RoadRunner) are unaffected as those servers handle concurrent connections without blocking worker processes. This limitation is documented in the package README.

> **Octane fiber caveat**: Under Laravel Octane with coroutines or fibers enabled, multiple concurrent jobs may share the same worker process. The static `ConductorContext` holder is not fiber-safe and would cause cross-job log contamination. Queue jobs must run as separate `artisan queue:work` processes (not as inline tasks within the Octane web server process) when using Conductor's log capture feature. This limitation is documented in the package README.

### 6.7 Dashboard UI

The dashboard is a pre-compiled React SPA served from `/conductor`. It requires no frontend tooling from the host application.

**Pages:**

| Page | Description |
|---|---|
| **Overview** | Summary cards (total jobs, failed, throughput, queue depth) + recent activity feed |
| **Jobs** | Paginated, filterable list of all job runs with status badges, queue, duration, and tags; supports filtering by status, queue, and tag via query parameters |
| **Job Detail** | Execution metadata, log output (live-streamed if in-progress), retry/cancel actions |
| **Workflows** | List of workflow runs with overall status and step count |
| **Workflow Detail** | Per-step timeline with status, duration, input/output, and error details |
| **Events** | Paginated event log with payload viewer and linked function runs |
| **Schedules** | All registered schedules, cron expression, next/last run, enable/disable toggle |
| **Metrics** | Time-series charts: job throughput, failure rate, queue depth over 1h/24h/7d windows |
| **Queues** | Worker status per queue — last heartbeat, current job, idle/busy state |

### 6.8 Worker Health & Heartbeats

- **Heartbeat collection** — Conductor records worker heartbeats in a `conductor_workers` table. Each queue worker running Conductor instrumentation emits a heartbeat on a fixed interval (default: 15 seconds) with worker name, queue, connection, process identifier, hostname, current job UUID, and last heartbeat timestamp.
- **Instrumentation model** — Conductor does not require a separate daemon, but accurate worker health requires workers to boot through Conductor's service provider and event listeners so queue lifecycle hooks can update the heartbeat record. This keeps the deployment model self-hosted while making the source of worker status explicit.
- **Status derivation** — A worker is considered `busy` while a tracked job is running, `idle` when the heartbeat is fresh and no job is assigned, and `offline` when its heartbeat is stale beyond a configurable timeout.
- **Driver support** — Worker health is available for asynchronous drivers with long-lived workers (database, Redis, SQS, Beanstalkd). The `sync` driver does not expose worker health because there is no persistent worker process. When the application is using the `sync` driver, the Queues page and any worker-health indicators in the dashboard display a reduced-capability notice — making it explicit that live worker status, queue depth, and realtime operational views are unavailable, rather than appearing broken or empty.

---

## 7. Platform Features

### 7.1 Authentication & Access Control

Access to the Conductor dashboard is gated using a closure registered in the application's service provider, exactly like Laravel Horizon:

```php
use Conductor\Facades\Conductor;

// In AppServiceProvider::boot()
Conductor::auth(function (Request $request): bool {
    return $request->user()?->hasRole('admin') ?? false;
});
```

**Default behaviour**: In the `local` environment, all users are allowed. In all other environments, the gate must be explicitly configured (a `403` is returned otherwise). A console warning is emitted if no gate is configured in a non-local environment.

### 7.2 API

All dashboard API endpoints are prefixed with `/conductor/api` and protected by the same auth gate. Endpoints return JSON.

| Endpoint | Method | Description |
|---|---|---|
| `/conductor/api/jobs` | GET | Paginated job list. Filterable by status, queue, tag. |
| `/conductor/api/jobs/{id}` | GET | Job detail with log entries |
| `/conductor/api/jobs/{id}/retry` | POST | Re-dispatch the job |
| `/conductor/api/jobs/{id}` | DELETE | Cancel a pending job or request cooperative cancellation for a running cancellable job |
| `/conductor/api/jobs/{id}/stream` | GET | SSE stream for realtime logs |
| `/conductor/api/workflows` | GET | Paginated workflow list |
| `/conductor/api/workflows/{id}` | GET | Workflow detail with step list |
| `/conductor/api/events` | GET | Paginated event log |
| `/conductor/api/events/{id}` | GET | Event detail with triggered runs |
| `/conductor/api/schedules` | GET | All registered schedules |
| `/conductor/api/schedules/{id}/toggle` | POST | Enable/disable a schedule |
| `/conductor/api/workflows/{id}` | DELETE | Cancel a pending or running workflow |
| `/conductor/api/metrics` | GET | Aggregate metrics. `?window=1h\|24h\|7d` |
| `/conductor/api/workers` | GET | Queue worker health |

**CSRF protection**: The SPA reads the `XSRF-TOKEN` cookie automatically set by Laravel and includes it as the `X-XSRF-TOKEN` header on all state-mutating requests (POST, DELETE). No additional CSRF configuration is required in the host application. The SPA communicates exclusively with the same-origin API, so cross-origin requests are never issued.

### 7.3 Security

- Dashboard is protected by the auth gate on every request (both HTML shell and API)
- Webhook signature verification is required before any payload processing
- No raw SQL queries; all database access via Eloquent with parameterised queries
- No credentials stored in the package; all secrets via environment configuration
- Stored payloads and logs pass through configurable redaction before persistence. By default, Conductor masks common secret-bearing keys such as `password`, `token`, `authorization`, `secret`, `api_key`, `cookie`, and known signature headers.
- Webhook signatures may be stored only in masked or hashed form for auditability; raw secret values and authorization headers are never persisted.
- Payload encryption is not enabled by default. If the host application requires encrypted Conductor data at rest, it must opt in via configuration.
- Webhook ingestion endpoints (`/conductor/webhook/{source}`) are rate-limited per source IP by default (configurable via `webhook_rate_limit` in `config/conductor.php`). A failed signature verification returns `403` immediately, before any payload processing or function dispatch.

### 7.4 Asset Publishing

On `php artisan vendor:publish --tag=conductor-assets`, the compiled frontend assets are copied to `public/vendor/conductor/`. The Blade shell view references these published paths. A `php artisan conductor:publish` convenience command wraps this.

**Frontend development**: The package includes its own `package.json` and `vite.config.ts` in the repository root. Compiled output is written to `resources/dist/` and **committed to git** — this is the same approach used by Horizon and Telescope. Users installing via Composer receive the pre-built assets with no Node.js requirement.

**Release process**: A GitHub Actions workflow (`.github/workflows/release.yml`) enforces that `resources/dist/` is always up-to-date before a release is tagged. The workflow:
1. Runs `npm ci && npm run build` on every push to `main`
2. Fails the release job if `resources/dist/` has uncommitted changes after the build step
3. Contributors are required to run `npm run build` locally and commit the updated dist files alongside any frontend changes — this is documented in `CONTRIBUTING.md`

**Asset cache-busting**: Vite is configured to use content-hash filenames (e.g. `app.a1b2c3d4.js`). The compiled `manifest.json` in `resources/dist/` is read by the Blade shell at runtime to resolve the correct hashed filenames. When assets are republished after a package upgrade (`php artisan conductor:publish`), the updated manifest ensures browsers receive invalidated asset URLs without manual intervention.

### 7.5 Artisan Commands

| Command | Description |
|---|---|
| `conductor:publish` | Publishes compiled frontend assets to `public/vendor/conductor/`. Convenience alias for `vendor:publish --tag=conductor-assets`. |
| `conductor:prune` | Prunes records older than `prune_after_days` (default: 7) from all Conductor tables. Child table rows are cascade-deleted. Registered automatically in Laravel's scheduler. |
| `conductor:status` | Outputs a console health summary: queue driver, registered function count, active/offline workers with last heartbeat timestamps, pending/running job counts, and stale worker warnings. Exits with code `1` if any workers are offline. Suitable for deployment health checks and CI pipelines. |

### 7.6 Configuration Schema

The full set of keys available in `config/conductor.php`:

```php
return [
    // URL prefix for the dashboard and API routes
    'path' => 'conductor',

    // Middleware applied to all Conductor routes
    'middleware' => ['web'],

    // Queue connection and queue name used for Conductor's own internal jobs
    // (event listener dispatch, workflow continuations).
    // Defaults to the application's default connection and a dedicated 'conductor' queue
    // to avoid contention with application jobs.
    'queue' => [
        'connection' => env('CONDUCTOR_QUEUE_CONNECTION', null), // null = app default
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
    'webhooks' => [
        // 'stripe' => [
        //     'secret'   => env('STRIPE_WEBHOOK_SECRET'),
        //     'function' => HandleStripeWebhook::class,
        // ],
    ],

    // Rate limit for webhook ingestion — requests per minute per source IP.
    // Set to null to disable.
    'webhook_rate_limit' => 60,
];
```

---

## 8. Data Model

```
conductor_jobs
├── id, uuid, class, display_name
├── status (pending|running|completed|failed|cancellation_requested|cancelled)
├── queue, connection, tags[]
├── payload (JSON)
├── attempts, max_attempts
├── cancellable_at, cancellation_requested_at, cancelled_at
├── started_at, completed_at, failed_at, duration_ms
└── error_message, stack_trace

conductor_job_logs
├── id, job_id (FK → conductor_jobs, cascade delete)
├── level (info|warning|error|debug)
├── message
└── logged_at

conductor_workflows
├── id, uuid, class, display_name
├── status (pending|running|waiting|completed|failed|cancelled)
├── input (JSON), output (JSON)
├── current_step_index, next_run_at, sleep_until, waiting_for_event
└── created_at, completed_at, cancelled_at

conductor_workflow_steps
├── id, workflow_id (FK → conductor_workflows, cascade delete)
├── conductor_job_id (nullable FK → conductor_jobs)
├── name, step_index
├── status (pending|running|completed|failed|skipped)
├── input (JSON), output (JSON)
├── lock_version, available_at
├── attempts, error_message, stack_trace
└── started_at, completed_at, duration_ms

conductor_workers
├── id, worker_uuid, worker_name
├── queue, connection, hostname, process_id
├── status (idle|busy|offline) — derived at query time from last_heartbeat_at; not written by a cleanup job
├── current_job_uuid
└── last_heartbeat_at

conductor_events
├── id, uuid, name
├── payload (JSON)
├── dispatched_at

conductor_event_runs
├── id, event_id (FK → conductor_events, cascade delete)
├── conductor_job_id (nullable FK → conductor_jobs)
├── function_class
├── status, error_message
├── attempts
└── started_at, completed_at, duration_ms

conductor_schedules
├── id, function_class (unique), display_name
├── cron_expression
├── is_active
└── last_run_at, next_run_at, last_run_status

conductor_webhook_sources
├── id, source (unique)
├── function_class
├── is_active
└── created_at, updated_at

conductor_webhook_logs
├── id, source (FK → conductor_webhook_sources.source)
├── payload (JSON), masked_signature
├── status (received|verified|processed|failed)
└── received_at

conductor_metric_snapshots
├── id
├── metric (throughput|failure_rate|queue_depth)
├── queue (nullable — populated for queue_depth metrics)
├── value, recorded_at
```

### Key Database Indexes

The following indexes are required to meet the < 200 ms API response NFR. Migrations must include them:

| Table | Index |
|---|---|
| `conductor_jobs` | `(uuid)` unique; `(status, queue)`; `(failed_at)` |
| `conductor_job_logs` | `(job_id, logged_at)` |
| `conductor_workflows` | `(uuid)` unique; `(status)` |
| `conductor_workflow_steps` | `(workflow_id, step_index)` unique |
| `conductor_workers` | `(worker_uuid)` unique; `(last_heartbeat_at)` |
| `conductor_events` | `(name, dispatched_at)` |
| `conductor_event_runs` | `(event_id)`; `(conductor_job_id)` |
| `conductor_schedules` | `(function_class)` unique |
| `conductor_metric_snapshots` | `(metric, recorded_at)` |

---

## 9. Non-Functional Requirements

### Performance
- No overhead on application requests when no Conductor jobs are running
- Dashboard API responses < 200ms for paginated lists (with appropriate DB indexes)
- SSE streams consume a single persistent connection per open log view
- Metric aggregation runs asynchronously via a scheduled pruning/snapshot command

### Reliability
- Workflow step state is committed to the database atomically before dispatching the next step
- A failed step does not advance the workflow — it retries the current step
- Failed tracked jobs are never silently discarded; all failures are recorded

### Scalability
- Core tracking is compatible with all Laravel queue drivers, including `sync`, but worker-health visibility and realtime operational views require asynchronous drivers with persistent workers such as database, Redis, SQS, or Beanstalkd.
- Multiple queue workers reading from the same queues is fully supported
- Database table rows are pruned on a configurable schedule (`prune_after_days`, default: 7)

### Observability
- All Conductor-internal errors are logged to the host application's default log channel
- A `conductor:status` Artisan command outputs a health summary to the console

---

## 10. Out of Scope (MVP)

- **Multi-tenant / team isolation** — all jobs visible to any gate-passing user
- **Fan-out / parallel steps** — workflow steps are sequential only in v1
- **Wait-for-event in workflows** — `$ctx->waitForEvent()` pausing a workflow until a named event fires (powerful but complex; deferred to v2)
- **Distributed tracing / OpenTelemetry** — no span correlation across steps in v1
- **Job dependencies** — defining that Job B only runs after Job A completes
- **Custom queue drivers** — no new queue driver is introduced; only existing Laravel drivers are used
- **API key authentication** — no external API keys; only the gate closure is supported
- **Mobile-responsive dashboard** — desktop-first for v1
- **Horizon integration** — Conductor runs independently and does not read Horizon's data
- **Step result hashing / deduplication** — content-addressed caching of step outputs based on input hash (simple name-based step lookup is sufficient for durability in v1)

---

## 11. Success Criteria

1. A developer can install the package, run migrations, and access the dashboard within 5 minutes on a fresh Laravel application.
2. A multi-step workflow with 3 steps survives a mid-execution process restart and resumes from the last completed step.
3. All tracked job failures are visible in the dashboard with stack trace and log output within 2 seconds of failure.
4. The dashboard is accessible at `/conductor` with no frontend dependencies installed in the host application.
5. An event dispatched via `ConductorEvent::dispatch()` triggers all registered listener functions and logs the triggered runs.
6. A webhook received at `/conductor/webhook/{source}` executes its bound function only after successful signature verification.
7. The auth gate correctly blocks unauthenticated access in non-local environments.
8. Worker heartbeats mark an asynchronous queue worker offline within the configured timeout when it stops reporting.
9. Sensitive keys in persisted payloads and logs are masked by default before they are visible in the dashboard.

