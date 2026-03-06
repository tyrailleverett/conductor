---
description: Generate a comprehensive Product Requirements Document (PRD) for a SaaS application. Use when the user asks to "create a PRD", "write a PRD", "generate product requirements", "draft a product spec", or wants to document requirements for a new SaaS product or feature. Expects the user to provide the product context upfront. Produces a complete PRD saved to specs/prd.md.
---

Generate a complete Product Requirements Document for a SaaS application from user-provided context.

## Workflow

### Step 1: Generate the PRD

Take the product context the user has provided and generate the full PRD. Write it to `specs/prd.md`. Use the structure below, adapting sections to fit the product. Omit sections that don't apply — not every SaaS needs an SDK, event system, or communication protocol.

### Step 2: Review

After writing the file, summarize the key sections and ask if the user wants to adjust anything before finalizing.

## PRD Structure

Use this as the structural guide. Adapt section depth and content to the product's complexity.

```markdown
# {Product Name} — Product Requirements Document

> **Version:** 1.0
> **Date:** {date}
> **Status:** Draft

---

## 1. Product Overview

{2-3 paragraphs: what the product is, what problem it solves, why existing solutions fall short, and how this product addresses the gap.}

### Product Principles

{3-5 guiding principles that inform all design decisions. These are values, not features.}

---

## 2. Target Users

### Primary Persona: {Role}

- {Context: what they do day-to-day}
- {Pain point: what frustrates them about current solutions}
- {Need: what they want from this product}
- {Values: what matters to them in a tool}

### Secondary Persona: {Role}

- {Same structure as above}

---

## 3. User Stories

{Group user stories by persona. Use the standard format. Focus on stories that clarify MVP scope — not an exhaustive list of every possible interaction.}

### {Primary Persona}

- As a {persona}, I want to {action} so that {benefit}.
- As a {persona}, I want to {action} so that {benefit}.

### {Secondary Persona}

- As a {persona}, I want to {action} so that {benefit}.

---

## 4. System Architecture

{High-level description of how the system is structured. Include:}

- Deployment model (hosted, self-hosted, hybrid)
- Key components and their responsibilities
- How components communicate
- Where user data lives and who controls it

{Include a communication protocol table if the product involves multiple systems or services:}

| Direction | Method | Details |
|---|---|---|
| ... | ... | ... |

{Include architectural diagrams or data flow descriptions where helpful.}

---

## 5. Core Entities

{Table of the main domain objects and their relationships.}

| Entity | Description |
|---|---|
| **{Entity}** | {What it represents and its role in the system} |

---

## 6. MVP Feature Set

{Break features into subsections. Each subsection should describe WHAT the feature does and WHY it matters. Include code examples or configuration samples where they clarify the user experience.}

### 6.1 {Feature Category}

- **{Feature}** — {Description of what it does and how the user interacts with it}

### 6.2 {Feature Category}

- ...

{Continue for all MVP features. Common categories: core workflow, integrations, dashboard/UI, developer experience, scheduling, error handling, access control.}

---

## 7. Platform Features

{Features of the SaaS platform itself, distinct from the core product features.}

### 7.1 Authentication & Onboarding

- {Registration, login, onboarding flow}

### 7.2 API

{If the product exposes an API, document the key endpoints:}

| Endpoint | Method | Description |
|---|---|---|
| ... | ... | ... |

### 7.3 Security

- {Key security considerations: data encryption, auth mechanisms, access control, compliance}

{Add subsections as needed: team management, webhooks, notifications, etc.}

---

## 8. Billing & Subscription Tiers *(optional)*

{Include this section if the product has paid plans, usage-based pricing, or freemium tiers. Omit if billing is not part of the MVP.}

### Pricing Model

{Describe the overall pricing strategy: flat-rate, per-seat, usage-based, freemium, or hybrid.}

### Subscription Tiers

| | Free | Pro | Enterprise |
|---|---|---|---|
| **Price** | $0/mo | {$/mo} | {Custom} |
| **{Limit 1}** | {value} | {value} | {value} |
| **{Limit 2}** | {value} | {value} | {value} |
| **{Limit 3}** | {value} | {value} | {value} |
| **{Feature 1}** | — | ✓ | ✓ |
| **{Feature 2}** | — | — | ✓ |

{Adapt the tier names, count, and rows to fit the product. Include all meaningful limits (API calls, storage, seats, records, etc.) and gated features. Use concrete numbers, not vague labels like "limited" or "unlimited" — if truly unlimited, say so explicitly.}

### Billing Behavior

- {Trial period: duration and what happens at expiration}
- {Upgrade/downgrade: how mid-cycle changes are handled (proration, immediate, next cycle)}
- {Overage handling: hard limit, soft limit with overage charges, or grace period}
- {Payment methods and billing frequency (monthly, annual, both)}

---

## 9. Data Model

{High-level entity relationship overview. Use indented tree notation or a table — whatever communicates the relationships most clearly.}

```
{entity}
├── {child entity}
│   ├── {grandchild}
│   └── {grandchild}
└── {child entity}
```

---

## 10. Non-Functional Requirements

### Performance
- {Latency, throughput, and responsiveness targets}

### Reliability
- {Uptime, data durability, failure handling}

### Scalability
- {Growth expectations and scaling strategy}

### Observability
- {Monitoring, logging, alerting for the platform itself}

---

## 11. Out of Scope (MVP)

{Bulleted list of features explicitly NOT in the MVP, with brief rationale where helpful. This sets expectations and documents future direction.}

---

## 12. Success Criteria

{Numbered list of measurable outcomes that define MVP success. Focus on user outcomes, not implementation milestones.}

---

## 13. Open Questions

{Numbered list of unresolved decisions. Each should frame the question and list the options being considered.}
```

## Writing Guidelines

- Write in plain, direct language. Avoid jargon unless the target audience expects it.
- Be specific over generic. "< 500ms webhook delivery" over "fast performance."
- Include code examples, API shapes, or configuration samples when they clarify the user experience.
- Use tables for structured comparisons (endpoints, entities, protocols).
- Every section should earn its place — if a section would be empty or generic, omit it.
- Product Principles should be values that guide tradeoffs, not restatements of features.
- Out of Scope is as important as in-scope — it prevents scope creep and sets expectations.
- Open Questions should be genuine unknowns, not things to decide later out of laziness.
