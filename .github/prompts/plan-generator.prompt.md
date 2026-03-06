---
description: Generate comprehensive, phased implementation plans for Laravel projects from a PRD or project description. Use when the user asks to "generate a plan", "create phases", "plan out a feature/project", "break this into phases", or describes a project/feature they want built and needs an implementation roadmap. Produces detailed specs covering migrations, models, controllers, routes, services, jobs, frontend pages, TypeScript types, and everything a developer needs to implement each phase with no ambiguity.
---

Generate phased implementation plans for Laravel projects. Each plan is a detailed, self-contained spec that a developer can follow verbatim.

## Before Starting

- Read @.claude/skills/plan-generator/references/phase-generation.md for phase ordering and scoping guidance
- Read @.claude/skills/plan-generator/references/plan-format.md for the detailed plan format specification

## Workflow

### Step 1: Gather Context

Before generating anything, gather product and codebase context:

0. **Read the PRD** — If `$ARGUMENTS` is provided, read the file at that path. If `$ARGUMENTS` is not provided, check if `specs/prd.md` exists and read it. If a PRD is found, extract: Product Overview, Core Entities, MVP Feature Set, Data Model, and Non-Functional Requirements. Use these as the primary input for phase decomposition — the PRD defines *what* to build, the plan defines *how* to build it.

1. **Existing conventions** — Read 2-3 existing models, controllers, factories, and form requests to identify:
   - `casts()` method vs `$casts` property
   - `fake()` vs `$this->faker` in factories
   - Array-style vs string-style validation rules
   - Primary key type (auto-increment, ULID, UUID)
   - Route organization (separate files? naming conventions?)
   - Middleware registration in `bootstrap/app.php`
   - Frontend stack (Inertia + React/Vue, Blade, API-only)

2. **Existing models and relationships** — Understand the current data layer so plans extend it, not duplicate it.

3. **Package versions** — Check `composer.json` and `package.json` for framework versions to use version-appropriate features.

4. **Directory structure** — Confirm where things live (`app/Services/`, `app/Actions/`, component directories, etc.).

### Step 2: Generate Phase Overview

Present a numbered list of phases before writing detailed plans. Read @.claude/skills/plan-generator/references/phase-generation.md for phase ordering and scoping guidance.

For each phase provide:
- **Phase N: Title**
- 1-2 sentence description
- Bullet list of key deliverables

Proceed directly to generating detailed plans — do not wait for user approval.

### Step 3: Generate Detailed Plans

For each phase, read @.claude/skills/plan-generator/references/plan-format.md and generate a plan following that format exactly. Critical rules:

- **Explore first** — Read the specific files each phase touches before writing the plan. Reference actual paths, method names, and conventions.
- **Dependency order** — Migrations → Enums → Models → Factories → Form Requests → Services/Actions → Controllers → Resources → Routes → Jobs → Frontend → Tests (Unit, Feature, Browser).
- **Thin controllers** — Controllers must only handle request/response orchestration. All business logic belongs in Services or Actions. A controller method should validate input (via Form Request), call a service/action, and return a response. Never put domain logic directly in controllers.
- **No code snippets** — Describe logic as prose, numbered steps, tables, and Mermaid diagrams. Exception: small config/route registration where exact syntax matters.
- **Checkboxes** — Every numbered section heading must include a `- [ ]` checkbox for progress tracking (e.g. `## - [ ] 3. Models`).
- **Be exhaustive** — Every column, relationship, controller method, route, factory state, validation rule. No guessing.
- **Cross-reference** — State what prior phases established rather than repeating.
- **Include diagrams** — Mermaid classDiagram for data models, sequenceDiagram for flows.

Save plans to `specs/` as `Plan_v{version}___Phase_{N}__{Title_With_Underscores}.md`.

### Step 4: Review

After all plans are generated, summarize:
- Total phases and what each delivers
- Cross-phase dependencies
- Assumptions to validate
