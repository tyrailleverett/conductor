# Plan Format Specification

Each phase plan is a standalone Markdown file saved to the project's `specs/` directory. The filename convention is:

```
Plan_v{version}___Phase_{N}__{Phase_Title_With_Underscores}.md
```

Example: `Plan_v1___Phase_3__Task_Registry_&_Event_Definitions.md`

## Document Structure

Every plan follows this exact top-level structure in order:

### 1. Preamble (verbatim)

```
I have created the following plan after thorough exploration and analysis of the codebase. Follow the below plan verbatim. Trust the files and references. Do not re-verify what's written in the plan. Explore only when absolutely necessary. First implement all the proposed file changes and then I'll review all the changes together at the end.
```

### 2. Observations

A short paragraph describing:
- What prior phases have established (conventions, patterns, existing models)
- Relevant codebase conventions discovered (e.g. `casts()` method vs `$casts` property, `fake()` vs `$this->faker`, middleware registration style)
- Any notable constraints or absence of packages

### 3. Approach

A concise paragraph explaining the high-level strategy for this phase:
- The core architectural decision and why
- How it fits into the broader system
- Any key trade-offs or design choices

### 4. Numbered Sections

The bulk of the plan. Each section is a numbered heading (`## 1. Section Title`) with a checkbox for progress tracking, covering one logical unit of work. Sections should appear in dependency order (things that must exist first come first).

Every numbered section heading MUST include a checkbox so the developer can track completion:

```
## - [ ] 1. Section Title
```

#### Example: Migration Section

```markdown
## - [ ] 2. Migrations

Create two migrations in this order (so foreign keys resolve correctly):

**`create_tasks_table`**
| Column | Type | Notes |
|---|---|---|
| `id` | ULID primary | |
| `environment_id` | `foreignUlid` | `constrained()->cascadeOnDelete()` |
| `slug` | `string` | |
| `name` | `string` | |
| `description` | `text` nullable | |
| `trigger_type` | `string` | stores `TriggerType` enum value |
| `trigger_config` | `json` nullable | e.g. `{"event": "user.created"}` |
| `timestamps` | | |

Add a `unique(['environment_id', 'slug'])` composite index — a slug must be unique within an environment, not globally.

**`create_event_types_table`**
| Column | Type | Notes |
|---|---|---|
| `id` | ULID primary | |
| `environment_id` | `foreignUlid` | `constrained()->cascadeOnDelete()` |
| `name` | `string` | dot-notation e.g. `user.created` |
| `schema` | `json` nullable | optional JSON Schema for validation |
| `timestamps` | | |

Add a `unique(['environment_id', 'name'])` composite index.
```

#### Example: Models Section

```markdown
## - [ ] 3. Models

**`app/Models/Task.php`**
- Traits: `HasFactory`, `HasUlids`
- `$fillable`: `environment_id`, `slug`, `name`, `description`, `trigger_type`, `trigger_config`
- `casts()`: `trigger_type` → `TriggerType::class`, `trigger_config` → `'array'`
- `getRouteKeyName(): string` returns `'slug'`
- Relationships:
  - `environment(): BelongsTo` → `Environment`
- Scopes:
  - `scopeActive(Builder $query): void` — filters to non-archived tasks

**`app/Models/EventType.php`**
- Traits: `HasFactory`, `HasUlids`
- `$fillable`: `environment_id`, `name`, `schema`
- `casts()`: `schema` → `'array'`
- Relationships:
  - `environment(): BelongsTo` → `Environment`

**Update `app/Models/Environment.php`** — add two `HasMany` relationships:
- `tasks(): HasMany` → `Task::class`
- `eventTypes(): HasMany` → `EventType::class`
```

#### Section Types and What to Include

**Migrations** — table name, columns as a Markdown table with Column/Type/Notes headers, composite indexes, foreign key constraints with cascade behavior.

**Enums** — file path, backed type (string/int), all cases with their values in a table.

**Models** — file path, traits, `$fillable`, `$hidden`, `casts()` entries, `getRouteKeyName(): string`. All relationships with full signatures including return types (e.g. `environment(): BelongsTo` → `Environment`). Scopes with parameter types and return type (e.g. `scopeActive(Builder $query): void`). Static helpers with input parameter types and return type (e.g. `static hashToken(string $rawToken): string`). Computed helpers with return type (e.g. `duration(): ?float`). Each relationship specifies its Eloquent type (BelongsTo, HasMany, BelongsToMany, etc.) and any pivot configuration.

**Factories** — follow existing factory patterns, list each attribute and its faker method, list named states with what they set. State methods include return type (e.g. `revoked(): static`).

**Form Requests** — file path, validation rules (array-style or string-style matching existing convention), `authorize(): bool` behavior, custom error messages if any.

**Controllers** — file path, whether invokable or resource. Controllers must be thin — they handle request/response orchestration only. Each method with full signature including parameter types and return type (e.g. `store(StoreApiKeyRequest $request): RedirectResponse`). Logic described as numbered steps (not code) limited to: 1) accept validated input from Form Request, 2) delegate to a Service or Action, 3) return a response (Inertia render, redirect, JSON resource collection, etc.). Any business logic beyond simple CRUD must be extracted to a corresponding Service or Action class.

**Eloquent API Resources** — file path, `toArray(Request $request): array` with list of exposed fields, fields that are explicitly never exposed.

**Middleware** — file path, `handle(Request $request, Closure $next): Response` logic flow (preferably as a Mermaid sequence diagram), what it binds into the container, error responses.

**Routes** — which route file, middleware group, prefix, name prefix, HTTP method + URI + controller mapping.

**Services/Actions** — file path, public method signatures with full parameter types, return types, and PHPDoc array shapes (e.g. `execute(TaskRun $taskRun, array $steps = []): void`). Logic flow as Mermaid diagram or numbered steps. Services/Actions are the primary home for business logic — controllers delegate to them. Every controller method that does more than simple CRUD should have a corresponding Service or Action. Use Actions for single-purpose operations (e.g. `CreateTask`, `RevokeApiKey`) and Services for grouped domain operations (e.g. `TaskService` with multiple related methods).

**Jobs** — file path, implements ShouldQueue, constructor with typed parameters (e.g. `__construct(public readonly TaskRun $taskRun)`), `$tries`, `$backoff`, `handle(RunOrchestrator $orchestrator): void` logic, `failed(Throwable $e): void` logic.

**Events** — file path (e.g., `app/Events/FooCreated.php`), constructor with typed properties describing the data payload. Whether it implements `ShouldBroadcast` or `ShouldBroadcastNow`. Broadcasting channel name and event name if applicable.

**Listeners** — file path, what event it listens to, `handle(EventClass $event): void` logic.

**Mailables** — file path, constructor with typed parameters, `envelope(): Envelope`, `content(): Content`, `attachments(): array`.

**Notifications** — file path (e.g., `app/Notifications/FooNotification.php`), `via()` method return value listing channels (e.g., `['mail', 'database']`). Channel-specific methods (`toMail()`, `toDatabase()`, `toArray()`) with their return structure described. Constructor with typed parameters.

**Policies** — file path, each method with full signature (e.g. `update(User $user, ApiKey $apiKey): bool`) and the authorization logic.

**Seeders** — file path, what data they create.

**Frontend Pages** — file path under `resources/js/pages/`, props interface (TypeScript types), key components used, layout, what data it displays.

**Frontend Components** — file path under `resources/js/components/`, props interface, behavior, what it renders.

**TypeScript Types** — file path, interface definitions with all fields typed.

**Config/Environment** — what config keys or env vars are added, where they're registered.

**Tests** — organized into three categories:

- *Unit Tests* — file path under `tests/Unit/`, what class/method is under test, test method names as a bullet list with a brief description of what each asserts. Cover isolated logic like model accessors, scopes, enum behavior, service methods (mocked dependencies).

- *Feature Tests* — file path under `tests/Feature/`, the endpoint or workflow under test, test method names as a bullet list describing the scenario (e.g. `it creates a task with valid data`, `it rejects unauthenticated requests`). Cover HTTP requests, form validation, authorization policies, database persistence, and job dispatching.

- *Browser Tests* — file path under `tests/Browser/`, the user flow under test, test method names as a bullet list describing the end-to-end scenario (e.g. `it allows a user to create and view a task`, `it shows validation errors on the form`). Cover full user journeys through the UI including navigation, form submission, flash messages, page transitions, and interactive components. Specify which pages are visited and key assertions (sees text, URL changes, element visibility).

### 5. Diagrams

Include where they add clarity:
- **Data Model Diagram** — Mermaid `classDiagram` showing models, their key fields, relationships. Include at the end of phases that introduce new models.
- **Sequence/Flow Diagrams** — Mermaid `sequenceDiagram` inline within the relevant section to illustrate request flows, middleware chains, job execution, etc.
- **URL Structure Tables** — Markdown table showing URL segments, their bound model, and route key.

## Formatting Rules

- Use `---` horizontal rules between numbered sections
- Use Markdown tables for migration columns, enum cases, and URL structures
- Use Mermaid code blocks for diagrams (```mermaid)
- Reference file paths as `app/Models/Foo.php` (relative to project root)
- Reference existing files with `file:` prefix when pointing to specific locations
- Never include actual PHP or TypeScript code snippets — describe logic as structured prose, numbered steps, or diagrams
- Exception: small config/route registration snippets are acceptable when the exact syntax matters (e.g. middleware alias registration, route group definitions)
- When a section references work from a prior phase, state what it established (e.g. "Phase 2's `AuthenticateWithApiKey` middleware binds `'current.environment'` into the container")
- Every model relationship must specify its Eloquent type and target model
- Every migration column must specify its type and constraints

## Completeness Checklist

Each phase plan must address ALL of the following that are relevant:

- [ ] Migrations (with indexes and foreign keys)
- [ ] Enums (if new status/type fields exist)
- [ ] Models (fillable, casts, relationships, scopes, helpers)
- [ ] Factories (with named states)
- [ ] Seeders (if sample data is needed)
- [ ] Form Requests (for every controller action that accepts input)
- [ ] Controllers (with full method descriptions)
- [ ] Eloquent API Resources (for every API response)
- [ ] Middleware (if request pipeline changes)
- [ ] Route registrations (with middleware, prefix, names)
- [ ] Services/Actions (for business logic)
- [ ] Jobs (for async work)
- [ ] Events (if event-driven behavior exists)
- [ ] Listeners (if responding to events)
- [ ] Policies (if authorization is needed)
- [ ] Mailables (if sending transactional email)
- [ ] Notifications (if users are notified via multiple channels)
- [ ] Config/Environment changes
- [ ] Frontend pages (with TypeScript prop types)
- [ ] Frontend components (with prop interfaces)
- [ ] TypeScript type definitions
- [ ] Unit tests (model accessors, scopes, services, isolated logic)
- [ ] Feature tests (HTTP endpoints, validation, authorization, jobs)
- [ ] Browser tests (end-to-end user flows through the UI)
- [ ] Data model diagrams
- [ ] Relationship updates to existing models
