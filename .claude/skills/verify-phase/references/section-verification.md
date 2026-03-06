# Section Verification Reference

How to audit a single section from a phase plan and confirm it was implemented correctly.

## Parsing Sections

Plan files use a consistent heading format for sections:

```
## - [ ] N. Section Title

<instructions as prose, tables, bullet lists, Mermaid diagrams>

---
```

Scan for `## - [ ]` headings. Everything between one heading and the next `---` horizontal rule (or next `## - [ ]` heading) is that section's specification.

Extract:
- **Section number** — the integer `N` after `## - [ ]`
- **Section title** — the text after `N.`
- **Section body** — all content until the next `---` or `## - [ ]`

## Finding Statuses

Every individual check within a section produces one of four statuses:

| Status | Meaning |
|---|---|
| **PASS** | The implementation matches the plan specification exactly. |
| **DEVIATION** | The item exists but differs from what the plan specified (wrong type, missing parameter, different logic, renamed, etc.). |
| **MISSING** | The plan specifies this item but it does not exist in the codebase. |
| **EXTRA** | The codebase contains something in this area that the plan did not specify. Not always a problem — flag it for review rather than treating it as a failure. |

When reporting, always include the specific expectation from the plan and what was actually found (or not found).

## Verification Strategy by Section Type

Read the section body and determine its type from its content. Use the appropriate checks:

### Config / Environment

**Structural checks:**
- Config file exists at the specified path
- Each described key is present with the correct default value
- Corresponding `.env` variables exist in `.env.example`
- `env()` calls are used only inside config files, never in application code

**Semantic checks:**
- Config values are accessed via `config()` helper throughout the codebase, not `env()` directly
- Default values match the plan specification

### Migrations

**Structural checks:**
- A migration file exists for each described table (match by table name in the filename)
- Each column from the plan's Column/Type/Notes table exists in the `up()` method with the correct type and modifiers (nullable, default, etc.)
- Composite indexes match the plan (columns and uniqueness)
- Foreign key constraints use the exact cascade behavior described (e.g., `cascadeOnDelete()`)
- Migration order is correct — tables referenced by foreign keys are created before the tables that reference them

**Semantic checks:**
- `down()` method reverses the `up()` method correctly (drops table or reverses column changes)
- Column modifications include all previously defined attributes (Laravel 12 requirement — omitted attributes are dropped)
- Run `php artisan migrate:status` to confirm migrations have been applied

### Enums

**Structural checks:**
- File exists at the specified path (e.g., `app/Enums/FooStatus.php`)
- Enum is backed by the correct type (string or int)
- Every case from the plan exists with the exact name and value

**Semantic checks:**
- No extra cases exist beyond what the plan specifies (flag as EXTRA)
- Case names follow TitleCase convention
- The backed type matches how the enum is used in model casts and migration column types

### Models

**Structural checks:**
- File exists at the specified path
- All listed traits are present (e.g., `HasFactory`, `HasUlids`)
- `$fillable` contains exactly the listed fields
- `$hidden` contains exactly the listed fields (if specified)
- `casts()` method (or `$casts` property, matching codebase convention) maps each field to the correct cast type
- `getRouteKeyName()` returns the specified value (if specified)
- Each relationship method exists with the correct return type and target model (e.g., `environment(): BelongsTo` returning `$this->belongsTo(Environment::class)`)
- Each scope method exists with the correct parameter types and return type
- Each static helper and computed helper exists with the correct signature
- When the plan says to update an existing model, verify the new relationships/methods were added without removing existing ones

**Semantic checks:**
- Relationship methods return the correct Eloquent type (BelongsTo, HasMany, BelongsToMany, etc.)
- Pivot configuration on BelongsToMany relationships matches the plan (pivot table, extra columns, timestamps)
- Scope logic filters correctly per the plan description
- Computed helpers implement the described logic

### Factories

**Structural checks:**
- File exists at the expected path (e.g., `database/factories/FooFactory.php`)
- `definition()` method sets each attribute listed in the plan with the correct faker method
- Each named state method exists with the correct name and return type (e.g., `revoked(): static`)

**Semantic checks:**
- State methods override the correct attributes with the values described in the plan
- Factory follows existing codebase patterns (check sibling factories for style)
- Factory produces valid model instances — relationships reference existing factories

### Seeders

**Structural checks:**
- File exists at the specified path
- `run()` method creates the data described in the plan

**Semantic checks:**
- Seeder uses factories where appropriate rather than raw DB inserts
- Data created matches the plan description (counts, specific values, relationships)

### Form Requests

**Structural checks:**
- File exists at the specified path
- `rules()` method returns exactly the validation rules listed in the plan
- Rule format matches codebase convention (array-style vs string-style)
- `authorize()` method implements the described authorization logic

**Semantic checks:**
- Custom error messages are defined if the plan specifies them
- Authorization logic correctly gates access per the plan description
- Rules cover all fields mentioned in the corresponding controller method

### Controllers

**Structural checks:**
- File exists at the specified path
- Controller type matches the plan (invokable with `__invoke()` or resource with named methods)
- Each method exists with the correct parameter types and return type (e.g., `store(StoreApiKeyRequest $request): RedirectResponse`)
- Type-hinted Form Requests match what the plan specifies for each method

**Semantic checks:**
- Controllers are thin — they handle request/response orchestration only
- Business logic is delegated to Services/Actions as the plan describes
- Each method's logic steps match the plan's numbered steps:
  1. Accepts validated input from the correct Form Request
  2. Delegates to the correct Service or Action
  3. Returns the correct response type (Inertia render, redirect, JSON resource, etc.)
- Inertia renders pass the correct props and point to the correct page component

### Eloquent API Resources

**Structural checks:**
- File exists at the specified path
- `toArray()` method exposes exactly the fields listed in the plan
- Fields that the plan says should never be exposed are absent

**Semantic checks:**
- Field values are correctly resolved (e.g., relationship data loaded, computed values returned)
- Nested resources use the correct resource class for related models
- Collection resources wrap individual resources correctly

### Middleware

**Structural checks:**
- File exists at the specified path
- `handle()` method signature matches `handle(Request $request, Closure $next): Response`
- Middleware is registered in `bootstrap/app.php` with the correct alias or group

**Semantic checks:**
- Logic flow matches the plan's description or Mermaid sequence diagram
- Container bindings are set correctly (e.g., `app()->instance('current.environment', $env)`)
- Error responses match the plan (status code, message, format)
- Early return / abort conditions are implemented as described

### Routes

**Structural checks:**
- Routes exist in the correct route file
- Each route uses the correct HTTP method (GET, POST, PUT, DELETE, etc.)
- URI patterns match the plan exactly, including parameter placeholders
- Routes point to the correct controller and method
- Route names match the plan specification
- Middleware groups and individual middleware are applied as described
- Prefix and name prefix match the plan

**Semantic checks:**
- Route model binding uses the correct key (e.g., `{post:slug}`)
- Route groups are nested correctly with the described middleware, prefix, and name prefix
- New route files are registered in `bootstrap/app.php` if the plan requires it

### Services / Actions

**Structural checks:**
- File exists at the specified path
- Each public method exists with the correct parameter types, return type, and PHPDoc array shapes
- Constructor uses dependency injection with the correct type-hinted parameters

**Semantic checks:**
- Logic flow matches the plan's description, numbered steps, or Mermaid diagram
- Actions are single-purpose (one `execute()` or `handle()` method)
- Services group related methods for a domain as described
- Business logic that the plan says should live here is not leaked into controllers

### Jobs

**Structural checks:**
- File exists at the specified path
- Class implements `ShouldQueue`
- Constructor has the correct typed parameters (e.g., `public readonly TaskRun $taskRun`)
- `$tries` and `$backoff` properties match the plan values
- `handle()` method exists with the correct parameter types and return type
- `failed()` method exists if the plan specifies it

**Semantic checks:**
- `handle()` logic matches the plan description
- `failed()` logic matches the plan description (cleanup, notifications, logging)
- Job is dispatched from the correct location in the codebase (controller, listener, or service as described)

### Events

**Structural checks:**
- File exists at the specified path (e.g., `app/Events/FooCreated.php`)
- Constructor has the correct typed properties matching the plan
- Implements `ShouldBroadcast` or `ShouldBroadcastNow` if the plan specifies broadcasting

**Semantic checks:**
- Event carries the correct data payload (constructor properties match what listeners and broadcast channels need)
- Broadcasting channel and event name match the plan if specified

### Listeners

**Structural checks:**
- File exists at the specified path
- `handle()` method accepts the correct event class as its parameter
- Listener is discoverable (Laravel 12 uses automatic event discovery by default; verify the listener is in `app/Listeners/` or registered in `bootstrap/app.php` if custom registration is used)

**Semantic checks:**
- `handle()` logic matches the plan description
- Side effects (dispatching jobs, sending mail, logging) match what the plan describes

### Mailables

**Structural checks:**
- File exists at the specified path
- Constructor has the correct typed parameters
- `envelope()` returns an `Envelope` with the correct subject
- `content()` returns a `Content` with the correct view/markdown reference
- `attachments()` returns the correct attachments if specified

**Semantic checks:**
- Mailable view/template exists and renders the expected content
- Dynamic data from the constructor is correctly passed to the view
- Mailable is sent from the correct location in the codebase

### Notifications

**Structural checks:**
- File exists at the specified path (e.g., `app/Notifications/FooNotification.php`)
- `via()` method returns the correct channels (e.g., `['mail', 'database']`)
- Channel-specific methods exist (`toMail()`, `toDatabase()`, `toArray()`, etc.) as described in the plan

**Semantic checks:**
- `toMail()` builds the correct `MailMessage` with subject, lines, and action
- `toDatabase()` / `toArray()` returns the correct data structure for storage
- Notification is sent from the correct location using `$user->notify()` or `Notification::send()`

### Policies

**Structural checks:**
- File exists at the specified path
- Each method exists with the correct signature (e.g., `update(User $user, ApiKey $apiKey): bool`)
- Policy is registered for the correct model (if explicit registration is used)

**Semantic checks:**
- Each method's authorization logic matches the plan description
- Gate checks and ability references in controllers/views align with the policy methods defined

### Frontend Pages

**Structural checks:**
- File exists at the specified path under `resources/js/pages/`
- Component receives the correct props matching the TypeScript interface described in the plan
- Layout component matches the plan specification

**Semantic checks:**
- Page displays the data described in the plan
- Key child components listed in the plan are used
- Inertia links and form submissions point to the correct routes
- Wayfinder imports (from `@/actions/` or `@/routes/`) are used correctly for route references

### Frontend Components

**Structural checks:**
- File exists at the specified path under `resources/js/components/`
- Props interface matches the TypeScript types described in the plan
- Component is exported correctly (default or named export matching usage)

**Semantic checks:**
- Component renders the elements described in the plan
- Behavior (event handlers, state management, conditional rendering) matches the plan description
- Component is used in the correct parent pages/components

### TypeScript Types

**Structural checks:**
- File exists at the specified path
- Each interface/type is defined with all fields and correct types as listed in the plan
- Exported correctly for use in other files

**Semantic checks:**
- Types align with the backend models/resources they represent
- Optional fields (`?`) match nullable columns or optional props from the plan

### Tests (Unit, Feature, Browser)

**Structural checks:**
- Test file exists at the specified path
- Each test method/case described in the plan exists (match by name or description)
- Test uses the correct base class or Pest syntax
- `RefreshDatabase` trait is used in tests that touch the database

**Semantic checks — Unit Tests:**
- Each test isolates the class/method under test
- Mocking is used for dependencies as appropriate
- Assertions verify the behavior described in the plan (model accessors, scopes, enum behavior, service methods)

**Semantic checks — Feature Tests:**
- HTTP requests use the correct method and URI
- Authentication and authorization scenarios are tested as described
- Validation rules are tested (valid data passes, invalid data fails with correct errors)
- Database assertions (`assertDatabaseHas`, `assertDatabaseMissing`) verify persistence
- Job dispatching assertions (`Queue::assertPushed`) verify async work

**Semantic checks — Browser Tests:**
- User flows cover the scenarios described in the plan
- Pages are visited in the correct order
- Form submissions, navigation, and flash messages are asserted
- Element visibility and URL changes are checked as described

## Cross-Section Checks

After verifying individual sections, perform these cross-cutting checks to ensure the sections integrate correctly:

### Foreign Key Integrity
- Every `foreignUlid` or `foreignId` in a migration has a corresponding `BelongsTo` relationship in the model
- Every `HasMany`, `HasOne`, or `BelongsToMany` relationship has a matching foreign key in the related table's migration
- Cascade behavior in migrations aligns with how the application handles deletions

### Route-Controller-Request Alignment
- Every route points to a controller method that exists with the correct signature
- Every controller method that accepts input uses the Form Request specified in the plan
- Every controller method returns the response type the plan describes (Inertia page, redirect, JSON resource)

### Model-Factory-Seeder Consistency
- Every model listed in the plan has a corresponding factory
- Factory `definition()` attributes cover all `$fillable` fields on the model
- Factory states match scenarios used in tests
- Seeders use the correct factories

### Controller-Service Delegation
- Business logic the plan assigns to Services/Actions is not implemented inline in controllers
- Controllers delegate to the exact Service/Action classes the plan specifies
- Service/Action method signatures match what controllers call

### Frontend-Backend Contract
- Props passed from controllers via Inertia match the TypeScript interfaces on the frontend page
- API Resources expose exactly the fields that frontend components consume
- Route names used in Wayfinder imports exist in the route files

### Test Coverage Alignment
- Every controller method has at least one feature test
- Every service/action public method has at least one unit test
- Every authorization rule in a policy has a test that asserts both allowed and denied access
- Browser tests cover the user flows the plan describes

## Verification Workflow

For each section in the plan:

```
1. Read the section specification from the plan
2. Identify the section type
3. Run all structural checks for that type
4. Run all semantic checks for that type
5. Record each check as PASS, DEVIATION, MISSING, or EXTRA
6. After all sections are verified, run cross-section checks
7. Compile the full report
```

When a check cannot be determined from static analysis alone, run the relevant command:
- `php artisan test --compact --filter=<relevant>` to verify tests pass
- `php artisan route:list` to verify route registration
- `php artisan migrate:status` to verify migrations are applied
- `vendor/bin/pint --test --format agent` to verify code style compliance

## Reporting

Group findings by section, then append cross-section findings. For each finding, include:
- The check that was performed
- The expected state (from the plan)
- The actual state (from the codebase)
- The status (PASS, DEVIATION, MISSING, or EXTRA)

Summarize with counts: total checks, PASS count, DEVIATION count, MISSING count, EXTRA count.
