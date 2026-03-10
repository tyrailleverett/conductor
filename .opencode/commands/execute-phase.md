---
description: Execute a phased implementation plan from specs/. Implements the entire phase using gitflow workflow, leaving changes uncommitted for verify-phase to review and push.
subtask: true
---

Implement an entire phase plan end-to-end using a gitflow workflow. The plan file path is passed as `$ARGUMENTS`. All changes are left uncommitted — run verify-phase afterwards to verify, auto-fix, and push.

## Before Starting

- Read @.claude/skills/execute-phase/references/git-workflow.md for all git operations
- Read @.claude/skills/execute-phase/references/section-execution.md for how to implement each section

## Workflow

Follow these steps exactly in order. Do not skip steps.

### Step 1: Validate Inputs

1. Verify `$ARGUMENTS` is provided and points to an existing file
2. Read the plan file
3. Extract the phase number and title from the filename (e.g., `Plan_v1___Phase_1__Environments_and_API_Keys.md` → Phase 1, "Environments and API Keys")
4. Parse all numbered sections by scanning for `## - [ ]` headings — each heading through the next `---` or next heading is one section
5. Abort with a clear error if the file doesn't exist or contains no parseable sections
6. **Cross-phase dependency check** — If the phase number is greater than 1, verify Phase {N-1} was completed on develop: `git log develop --oneline | grep "Phase {N-1}\."`). If nothing is found, warn the user: "Phase {N-1} has not been completed on develop. Phase {N} may depend on work from Phase {N-1}." Ask the user for confirmation before proceeding. If the user declines, abort.
7. **Resume detection** — Derive the branch name from the plan filename (see @.claude/skills/execute-phase/references/git-workflow.md). Check if the branch already exists (`git branch --list <branch-name>`). If it exists and has uncommitted changes (`git status --porcelain`), warn: "Branch <branch-name> already exists with uncommitted changes. Re-running will re-implement all sections, overwriting existing changes." Ask for confirmation. If confirmed, checkout the branch and continue from Step 4. If the user declines, abort.

### Step 2: Git Setup

Follow the **Git Initialization** procedure in @.claude/skills/execute-phase/references/git-workflow.md:

1. If no `.git` directory exists → initialize git, create initial commit, create `develop` branch
2. If `develop` branch doesn't exist → create it from current HEAD
3. If working tree is dirty (uncommitted changes) → abort and tell the user to commit or stash
4. Checkout `develop` and pull if a remote exists

### Step 3: Create Branch

Follow the **Branch Setup** procedure in @.claude/skills/execute-phase/references/git-workflow.md:

1. Derive the branch name from the plan filename (e.g., `feature/phase-1-environments-and-api-keys`)
2. Run `git checkout -b <branch-name>` from the `develop` branch
3. Confirm you are on the correct branch: `git branch --show-current`

### Step 4: Build Todo List

Before executing any sections, create a todo list using the `manage_todo_list` tool containing one item per section parsed in Step 1.4. Use the section title as the todo label and set all items to `not-started`. This list must be created once and maintained throughout execution.

### Step 5: Execute Sections

For each numbered section in the plan, in order:

1. Implement and verify the section (steps 5a–5c below).
2. Mark the section's todo item as `completed` immediately after implementation succeeds.
3. Update the plan file: change `## - [ ] {S}.` to `## - [x] {S}.` for the completed section.

#### 5a. Implement

1. Read the section's instructions from the plan
2. Follow @.claude/skills/execute-phase/references/section-execution.md to determine the implementation approach based on section type
3. Follow the plan verbatim — do not add features, refactor, or improve beyond what is specified
4. Use `php artisan make:*` commands where the plan specifies
5. When implementing test sections, invoke the **pest-testing** skill

#### 5b. Verify

1. Run `php artisan test --compact` (filter to relevant test files if they exist for this section)
2. Run `bun run lint`
3. Run `composer lint`
4. If linters made formatting changes, include them in this section's commit

#### 5c. On Failure → Fix & Retry

If tests fail, enter the fix-and-retry loop (max 3 attempts):

1. Invoke the **systematic-debugging** skill with the failure output
2. Apply the fix
3. Re-run verification (5b)
4. If still failing after 3 attempts → report the failure to the user and **stop execution entirely** (do not proceed to the next section). All implemented files remain in the working directory as-is for inspection.

### Step 6: Final Verification

After all sections are implemented:

1. Run the full test suite: `php artisan test --compact`
2. Run full formatting: `vendor/bin/pint --dirty --format agent`
3. If any failures, enter the fix-and-retry loop from Step 5c and apply fixes

**Do not commit any changes.** All modifications must remain uncommitted.

### Step 7: Handoff

Report to the user:
- Which sections were implemented
- Current test suite result
- That all changes are uncommitted on branch `<branch-name>`
- That they should now run `verify-phase` with the plan file path to verify, auto-fix, and push the changes

## Resuming and Rollback

### Resuming a failed phase

If execution stopped due to repeated test failures, fix the failing code manually and re-invoke execute-phase with the same plan file. It will detect the existing branch and uncommitted changes, ask for confirmation, and re-implement all sections when you confirm.

### Starting fresh

To abandon a partial implementation and start over:

1. Checkout develop: `git checkout develop`
2. Delete the branch: `git branch -D <branch-name>`
3. Re-invoke execute-phase with the same plan file
