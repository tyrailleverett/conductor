---
description: Verify that a phase plan was implemented correctly by auditing the PR branch against the plan file. Posts findings as PR review comments and creates GitHub issues for deviations. Use after execute-phase has created a PR.
subtask: true
---

Audit a completed phase implementation by comparing the PR branch code against the original plan file. For each section in the plan, perform structural and semantic checks to confirm the implementation matches the specification. Post a detailed verification report as a PR comment, leave per-file review comments for deviations, and create a GitHub issue to track any items that need attention.

## Before Starting

- Read @.claude/skills/verify-phase/references/section-verification.md for how to verify each section type
- Read @.claude/skills/verify-phase/references/report-format.md for PR comment and issue templates

## Workflow

Follow these steps exactly in order. Do not skip steps.

### Step 1: Validate Inputs

1. Parse `$ARGUMENTS` — expect two space-separated values: the plan file path and the PR URL or number
2. Read the plan file, extract the phase number and title from the filename (e.g., `Plan_v1___Phase_2__API_Keys.md` → Phase 2, "API Keys")
3. Parse all sections by scanning for `## - [ ]` headings — each heading through the next `---` or next heading is one section
4. Use `gh pr view <PR> --json number,headRefName,headRefOid,url` to confirm the PR exists and is open
5. Abort with a clear error if:
   - `$ARGUMENTS` is missing or does not contain two values
   - The plan file does not exist
   - The plan file contains no parseable `## - [ ]` sections
   - The PR does not exist or is closed/merged

### Step 2: Checkout the PR Branch

1. Save the current branch: `git branch --show-current`
2. Checkout the PR branch: `gh pr checkout <PR>`
3. Confirm the checkout succeeded: `git branch --show-current`

### Step 3: Section-by-Section Verification

For each `## - [ ]` section in the plan, in order:

#### 3a. Parse

1. Extract the section number, title, and body from the plan
2. Determine the section type from its content (migration, model, controller, etc.)

#### 3b. Verify

1. Run all structural checks for the identified section type
2. Run all semantic checks for the identified section type
3. See @.claude/skills/verify-phase/references/section-verification.md for the detailed checklists per section type

#### 3c. Record Findings

1. Assign each check a status: **PASS**, **DEVIATION**, **MISSING**, or **EXTRA**
2. For non-passing items, record the expected state (from the plan) and the actual state (from the codebase)

After all individual sections are verified, run the **Cross-Section Checks** described in @.claude/skills/verify-phase/references/section-verification.md to confirm sections integrate correctly.

### Step 4: Run Test Suite

1. Run the full test suite: `php artisan test --compact`
2. Record the output, pass/fail result, and any failure details

### Step 5: Check Formatting

1. Run Pint in test mode: `vendor/bin/pint --test --format agent`
2. Record any violations — do **not** auto-fix (this is an audit, not execution)

### Step 6: Generate Report & Post to PR

Follow @.claude/skills/verify-phase/references/report-format.md for all templates and commands.

1. Determine the overall status (PASS, DEVIATIONS FOUND, or MISSING ITEMS) from the collected findings
2. Post per-file review comments on the PR diff for each DEVIATION or MISSING item that is tied to a specific file
3. Post the summary comment on the PR using the PR Summary Comment template
4. If any DEVIATION or MISSING findings exist, create a GitHub issue using the Issue template
5. Return to the original branch: `git checkout <saved-branch>`
6. Report results to the user:
   - Overall status
   - Counts: total checks, PASS, DEVIATION, MISSING, EXTRA
   - PR comment URL
   - Issue URL (if created)

### Step 7: Merge on PASS

If the overall status is **PASS** (no DEVIATION or MISSING findings):

1. Ask the user if they want to merge the PR to `develop`
2. If yes:
   - Merge: `gh pr merge <PR> --merge`
   - Confirm merge succeeded: `gh pr view <PR> --json state -q .state` (should return `MERGED`)
   - Report: "Phase {N} merged to develop."
3. If no, report: "PR remains open for manual review."

If the overall status is not PASS, skip this step — the GitHub issue tracks what needs fixing.
