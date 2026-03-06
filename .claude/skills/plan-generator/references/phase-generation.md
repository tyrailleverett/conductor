# Phase Generation Guide

## What Makes a Good Phase

A phase is a self-contained unit of work that can be implemented and verified independently. Each phase should:

1. **Have clear boundaries** — Define what's in scope and what's deferred to later phases
2. **Build on prior phases** — Reference what earlier phases established, never duplicate
3. **Be implementable in isolation** — A developer can complete it without needing future phases
4. **Have a testable outcome** — After implementation, something new and verifiable works

## Phase Ordering Principles

Phases must be ordered by dependency — foundational infrastructure first, features that depend on it later:

1. **Data layer first** — Models, migrations, relationships before anything that uses them
2. **Auth/security early** — Authentication and authorization before protected features
3. **Core domain before UI** — Backend logic before the frontend that displays it
4. **SDK/package last** — External-facing packages after the internal system works

## Typical Phase Pattern for a Laravel SaaS

A common ordering (adapt to the specific project):

1. **Multi-tenancy / data foundation** — Org/team/workspace models, membership, ownership chains
2. **Authentication & API keys** — How external systems authenticate (API keys, OAuth, tokens)
3. **Core domain models** — The primary entities the product manages
4. **Core engine/logic** — The business logic that operates on those entities (jobs, services, state machines)
5. **Event/webhook system** — Ingestion, routing, dispatching
6. **Dashboard UI** — Inertia pages displaying the data
7. **SDK/package** — The Composer/npm package external developers install

## Phase Summary Format

When presenting phases as a high-level overview (before detailed plans), use this format:

```
## Phase {N}: {Title}

{1-2 sentence description of what this phase builds}

Key deliverables:
- {Model/Migration}: {brief description with key columns}
- {Controller/Service}: {what it does}
- {Route/Middleware}: {what it enables}
```

## Scoping Decisions

When deciding what goes in which phase:

- **Group by domain** — All "API key" stuff in one phase, not spread across three
- **Nullable forward references** — If Phase 4 needs a column that references a Phase 5 table, make it nullable without a FK constraint and note that Phase 5 will add the constraint
- **Stub endpoints** — A phase can register route groups that later phases populate (e.g. Phase 2 creates `routes/api.php` with a health check, Phase 3 adds task endpoints to it)
- **No premature UI** — Don't build UI pages until the data layer and API they depend on exist
