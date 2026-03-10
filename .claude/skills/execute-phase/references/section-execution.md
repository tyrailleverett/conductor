# Section Execution Reference

How to parse and implement a single section from a phase plan.

## Parsing Sections

Plan files use a consistent heading format for sections:

```
## - [ ] N. Section Title

<instructions as prose, tables, bullet lists, Mermaid diagrams>

---
```

Scan for `## - [ ]` headings. Everything between one heading and the next `---` horizontal rule (or next `## - [ ]` heading) is that section's instructions.

Extract:
- **Section number** — the integer `N` after `## - [ ]`
- **Section title** — the text after `N.`
- **Section body** — all content until the next `---` or `## - [ ]`

## Implementation Strategy by Section Type

Read the section body and determine its type from its content. Use the appropriate approach:

### Migrations

- Section contains column tables with Column/Type/Notes headers
- Run `php artisan make:migration <name> --no-interaction` for each migration described
- Edit the generated file to add columns, indexes, and foreign keys per the table spec
- Run migrations: `php artisan migrate`

### Enums

- Section describes string-backed or int-backed enum cases
- Create the file at the specified path (e.g., `app/Enums/FooStatus.php`)
- Define all cases exactly as described

### Models

- Section lists traits, `$fillable`, `$hidden`, `casts()`, relationships, scopes, helpers
- For new models: run `php artisan make:model <Name> --no-interaction`
- For existing models: edit the file directly
- Add all described properties, methods, and relationships

### Factories

- Section lists definition attributes and named states
- Run `php artisan make:factory <Name>Factory --no-interaction`
- Edit to match described attributes and states

### Middleware

- Section describes request handling logic, container bindings, error responses
- Create the file at the specified path
- Implement the `handle()` method per the described logic flow
- Register in `bootstrap/app.php` as described

### Routes

- Section maps HTTP methods + URIs to controllers
- Create or edit the route file
- If a new route file, register it in `bootstrap/app.php`

### Controllers

- Section describes methods with parameter types, return types, and logic steps
- For invokable controllers: `php artisan make:controller <Name> --invokable --no-interaction`
- For resource controllers: `php artisan make:controller <Name> --no-interaction`
- Implement each method per the described logic

### Eloquent API Resources

- Section lists fields to expose in `toArray()`
- Run `php artisan make:resource <Name> --no-interaction`
- Implement `toArray()` with exactly the described fields

### Form Requests

- Section lists validation rules and authorization logic
- Run `php artisan make:request <Name> --no-interaction`
- Implement `rules()` and `authorize()` as described

### Services / Actions

- Section describes method signatures and logic flows
- Create the file at the specified path using `php artisan make:class <Name> --no-interaction`
- Implement methods per the described logic

### Jobs

- Section describes constructor, handle logic, retries, backoff
- Run `php artisan make:job <Name> --no-interaction`
- Implement as described

### Config Files

- Section describes config keys, env variables, and defaults
- Create the file at the specified path
- Use `env()` helper for each key with the described default

### Tests

- Section lists test file paths and test case descriptions
- **Invoke the pest-testing skill** before implementing tests
- Run `php artisan make:test <Name> --pest --no-interaction`
- Implement each described test case
- Use factories for all model creation
- Use `RefreshDatabase` trait in all test files

## Key Rules

1. **Follow the plan verbatim** — the plan is the spec. Do not add features, refactor surrounding code, or make improvements beyond what is specified.
2. **Use artisan generators** — when the plan says to create a migration, model, controller, etc., use `php artisan make:*`. Don't hand-create files that artisan can scaffold.
3. **Respect existing conventions** — the plan's Observations section describes them (e.g., `casts()` method vs `$casts` property, `fake()` vs `$this->faker`). Follow those.
4. **Prose → code faithfully** — the plan describes logic as prose and tables, not code. Translate it to code faithfully without adding or removing behavior.
5. **Pass `--no-interaction`** — to all artisan commands to ensure they work without prompts.

## Fix & Retry Loop

When tests fail after implementing a section:

```
attempts = 0

WHILE tests fail AND attempts < 3:
    1. Invoke the systematic-debugging skill with the failure output
    2. Apply the fix
    3. Re-run: php artisan test --compact (filtered to relevant files)
    4. Re-run: vendor/bin/pint --dirty --format agent
    5. attempts++

IF attempts == 3 AND still failing:
    1. Report the failure details to the user
    2. STOP — do not proceed to the next section
    (All implemented files remain in the working directory as-is for the user to inspect)
```

The user can fix the issue manually and re-invoke execute-phase with the same plan file to re-implement all sections when ready.

## Verification After Each Section

1. Run `php artisan test --compact` — filter to relevant test files if they exist for this section
   - If no tests exist yet for this section (e.g., Config, Enums), skip the test run
2. Run `vendor/bin/pint --dirty --format agent`
3. If Pint made changes, stage them into the same commit as the section's code
