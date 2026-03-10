---
description: Verify a phase implementation by reviewing uncommitted changes, auto-fixing issues, then committing and pushing to develop. Run after execute-phase.
subtask: true
---

Verify the uncommitted implementation of a phase plan. Compares working directory files against the plan specification, auto-fixes any deviations, runs tests, and pushes the result to develop once all checks pass.

## Before Starting

- Read @.claude/skills/verify-phase/references/section-verification.md for how to verify each section type
- Read @.claude/skills/verify-phase/references/report-format.md for the report template

## Workflow

Follow these steps exactly in order. Do not skip steps.

### Step 1: Validate Inputs

1. Parse `$ARGUMENTS` — expect a single value: the plan file path
2. Read the plan file, extract the phase number and title from the filename (e.g., `Plan_v1___Phase_2__API_Keys.md` → Phase 2, "API Keys")
3. Parse all sections by scanning for `## - [ ]` headings — each heading through the next `---` or next heading is one section
4. Derive the expected branch name from the plan filename (see @.claude/skills/execute-phase/references/git-workflow.md for branch naming rules)
5. Confirm the current branch is the expected feature branch: `git branch --show-current`
6. Run `git status --porcelain` to confirm there are uncommitted changes to verify
7. Abort with a clear error if:
   - `$ARGUMENTS` is missing
   - The plan file does not exist
   - The plan file contains no parseable `## - [ ]` sections
   - The current branch is not the expected feature branch
   - There are no uncommitted changes (`git status --porcelain` returns empty)

### Step 2: Section-by-Section Verification

For each `## - [ ]` section in the plan, in order:

#### 2a. Parse

1. Extract the section number, title, and body from the plan
2. Determine the section type from its content (migration, model, controller, etc.)

#### 2b. Verify

1. Run all structural checks for the identified section type by reading the relevant files directly
2. Run all semantic checks for the identified section type
3. See @.claude/skills/verify-phase/references/section-verification.md for the detailed checklists per section type

#### 2c. Record Findings

1. Assign each check a status: **PASS**, **DEVIATION**, **MISSING**, or **EXTRA**
2. For non-passing items, record the expected state (from the plan) and the actual state (from the codebase)

After all individual sections are verified, run the **Cross-Section Checks** described in @.claude/skills/verify-phase/references/section-verification.md to confirm sections integrate correctly.

### Step 3: Run Test Suite

1. Run the full test suite: `composer test`
2. Record the output, pass/fail result, and any failure details

### Step 4: Auto-Fix Issues

1. Run Pint to auto-fix any formatting violations: `vendor/bin/pint --dirty --format agent`
2. For each DEVIATION or MISSING finding from Step 2, apply the required fix to the relevant file
3. After all fixes are applied, re-run the test suite: `composer test`
4. If tests still fail after fixes, enter the fix-and-retry loop (max 3 attempts):
   - Invoke the **systematic-debugging** skill with the failure output
   - Apply the fix
   - Re-run: `composer test`
5. If tests are still failing after 3 attempts, stop and report to the user — **do not push**

### Step 5: Code Review

Perform a thorough code review of all staged and changed files using the guidelines in @.opencode/commands/code-review.md. This includes:

- All PHP and code standards checks
- Security, performance, and architecture reviews
- Testing coverage verification
- Laravel package conventions

Record the verdict:
- ✅ **Approved** — proceed to Step 6
- ⚠️ **Approved with suggestions** — proceed to Step 6 (suggestions can be addressed in future iterations)
- 🚫 **Changes required** — stop and report to the user with specific issues; do not push

### Step 6: Finalize

Follow the **Finalization** procedure in @.claude/skills/execute-phase/references/git-workflow.md:

1. Stage all changed files by specific path — never use `git add .` or `git add -A`
2. Commit: `git commit -m "Phase {N}: {Phase Title}"`
3. Checkout develop: `git checkout develop`
4. Merge the feature branch: `git merge <branch-name> --no-ff`
5. Push to remote: `git push origin develop`
6. Delete the local feature branch: `git branch -D <branch-name>`
7. Delete the remote feature branch if it exists: `git push origin --delete <branch-name>`

### Step 7: Report to User

Follow @.claude/skills/verify-phase/references/report-format.md for the report template.

Print the verification summary to the terminal:
- Overall status (PASS or FIXED — indicating auto-fixes were applied)
- Section results table (section number, title, initial status, any fixes applied)
- Test suite result
- Formatting result
- Confirmation that changes were pushed to `develop` and the feature branch was deleted
