---
name: execute-phase
description: Execute a phased implementation plan from specs/. Implements the entire phase using gitflow workflow, one commit per section, and auto-creates a PR to develop.
argument-hint: [path-to-phase-plan]
disable-model-invocation: true
---

# Execute Phase

Implement an entire phase plan end-to-end using a gitflow workflow. The plan file path is passed as `$ARGUMENTS`.

## Before Starting

- Read [references/git-workflow.md](references/git-workflow.md) for all git operations
- Read [references/section-execution.md](references/section-execution.md) for how to implement each section

## Workflow

Follow these steps exactly in order. Do not skip steps.

### Step 1: Validate Inputs

1. Verify `$ARGUMENTS` is provided and points to an existing file
2. Read the plan file
3. Extract the phase number and title from the filename (e.g., `Plan_v1___Phase_1__Environments_and_API_Keys.md` → Phase 1, "Environments and API Keys")
4. Parse all numbered sections by scanning for `## - [ ]` headings — each heading through the next `---` or next heading is one section
5. Abort with a clear error if the file doesn't exist or contains no parseable sections
6. **Cross-phase dependency check** — If the phase number is greater than 1, search for a merged PR for the prior phase: `gh pr list --state merged --search "Phase {N-1}:" --json number,title --limit 1`. If no merged PR is found, warn the user: "No merged PR found for Phase {N-1}. Phase {N} may depend on work from Phase {N-1}." Ask the user for confirmation before proceeding. If the user declines, abort.
7. **Resume detection** — Derive the branch name from the plan filename (see [references/git-workflow.md](references/git-workflow.md)). Check if the branch already exists (`git branch --list <branch-name>`). If it exists, this is a resume — skip to Step 3a.

### Step 2: Git Setup

Follow the **Git Initialization** procedure in [references/git-workflow.md](references/git-workflow.md):

1. If no `.git` directory exists → initialize git, create initial commit, create `develop` branch
2. If `develop` branch doesn't exist → create it from current HEAD
3. If working tree is dirty (uncommitted changes) → abort and tell the user to commit or stash
4. Checkout `develop` and pull if a remote exists

### Step 3: Create Branch

Follow the **Branch Setup** procedure in [references/git-workflow.md](references/git-workflow.md):

1. Derive the branch name from the plan filename (e.g., `feature/phase-1-environments-and-api-keys`)
2. Create and checkout the branch: `git checkout -b <branch-name>`
3. Confirm you are on the correct branch: `git branch --show-current`

#### Step 3a: Resume Into Existing Branch

If resume was detected in Step 1.6:

1. Checkout the existing branch: `git checkout <branch-name>`
2. Read the git log to find the last committed section: parse commit messages for `Phase {N}.{S}:` pattern, take the highest `{S}`
3. Report to the user: "Resuming Phase {N} from section {S+1}. Sections 1-{S} already committed."
4. Proceed to Step 4, starting from section S+1

### Step 4: Build Todo List

Before executing any sections, create a todo list using the `manage_todo_list` tool containing one item per section parsed in Step 1.4. Use the section title as the todo label and set all items to `not-started`. This list must be created once and maintained throughout execution.

### Step 5: Execute Sections

For each numbered section in the plan, in order:

1. Implement, verify, and commit the section (steps 5a–5d below).
2. Mark the section's todo item as `completed` immediately after its commit succeeds.

#### 5a. Implement

1. Read the section's instructions from the plan
2. Follow [references/section-execution.md](references/section-execution.md) to determine the implementation approach based on section type
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
4. If still failing after 3 attempts → commit what works, report the failure to the user, and **stop execution entirely** (do not proceed to the next section)

#### 5d. Commit

1. Stage relevant files by specific path — never use `git add .` or `git add -A`
2. Commit with message format: `Phase {N}.{S}: {Section Title}`
   - Example: `Phase 1.4: Models`

### Step 6: Final Verification

After all sections are implemented and committed:

1. Run the full test suite: `php artisan test --compact`
2. Run full formatting: `vendor/bin/pint --dirty --format agent`
3. If any failures, enter the fix-and-retry loop from Step 5c
4. Commit any final fixes

### Step 7: Create PR

Follow the **PR Creation** procedure in [references/git-workflow.md](references/git-workflow.md):

1. Push the feature branch to remote
2. Create a PR to `develop` using `gh pr create` with the structured summary format
3. Report the PR URL to the user

## Resuming and Rollback

### Resuming a failed phase

If execution stopped due to repeated test failures, re-invoke execute-phase with the same plan file. It will detect the existing branch, find the last committed section, and resume from the next one. Fix the failing code manually before re-invoking.

### Starting fresh

To abandon a partial execution and start over:

1. Delete the branch: `git branch -D <branch-name>`
2. Re-invoke execute-phase with the same plan file
