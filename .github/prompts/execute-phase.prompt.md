---
description: Execute a phased implementation plan from specs/. Implements the entire phase using gitflow workflow, leaving changes uncommitted for verify-phase to review and push.
---

Implement an entire phase plan end-to-end using a gitflow workflow. The plan file path is passed as `$ARGUMENTS`. All changes are left uncommitted — run verify-phase afterwards to verify, auto-fix, and push.

## Before Starting

- Read @.claude/skills/execute-phase/references/git-workflow.md for all git operations
- Read @.claude/skills/execute-phase/references/section-execution.md for how to implement each section

## Workflow

### Step 1: Validate Inputs & Auto-Detect Phase

#### 1a. Determine the Phase File

**IF** `$ARGUMENTS` is provided:
1. Verify it points to an existing file
2. Use that file as the plan file
3. Jump to Step 1c

**IF** `$ARGUMENTS` is NOT provided:
1. List all plan files in `specs/` matching the pattern `Plan_v1___Phase_*.md`
2. For each file, count:
   - Total sections: number of lines matching `## - [x]` or `## - [ ]`
   - Completed sections: number of lines matching `## - [x]`
3. Find the highest phase number where `completed sections == total sections` (100% complete)
4. If a fully completed phase exists:
   - The next phase = completed phase number + 1
   - Look for `Plan_v1___Phase_{N}.md` where N = next phase
   - If that file exists, use it (this is the phase to execute)
   - If that file does NOT exist, report: "All available phases (1–{highest}) are complete. No next phase found." Abort.
5. If NO fully completed phase exists:
   - Use Phase 1: `Plan_v1___Phase_1.md`
   - If Phase 1 file does not exist, abort with error: "No plan file provided and Phase 1 not found in specs/"

#### 1b. Read and Parse the Plan File

1. Read the selected plan file
2. Extract the phase number and title from the filename (e.g., `Plan_v1___Phase_1__Package_Foundation_and_Configuration.md` → Phase 1, "Package Foundation and Configuration")
3. Parse all numbered sections by scanning for `## - [ ]` or `## - [x]` headings — each heading through the next `---` or next heading is one section
4. Abort with a clear error if the file contains no parseable sections

#### 1c. Cross-Phase Dependency & Resume Detection

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
