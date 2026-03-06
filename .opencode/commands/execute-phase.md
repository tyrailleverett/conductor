---
description: Execute a phased implementation plan from specs/. Implements the entire phase using gitflow workflow with isolated worktree, one commit per section, and auto-creates a PR to develop.
subtask: true
---

Implement an entire phase plan end-to-end using a gitflow workflow. The plan file path is passed as `$ARGUMENTS`.

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
6. **Cross-phase dependency check** — If the phase number is greater than 1, search for a merged PR for the prior phase: `gh pr list --state merged --search "Phase {N-1}:" --json number,title --limit 1`. If no merged PR is found, warn the user: "No merged PR found for Phase {N-1}. Phase {N} may depend on work from Phase {N-1}." Ask the user for confirmation before proceeding. If the user declines, abort.
7. **Resume detection** — Derive the branch name from the plan filename (see @.claude/skills/execute-phase/references/git-workflow.md). Check if the branch already exists (`git branch --list <branch-name>`). If it exists and a worktree exists at `.claude/worktrees/<branch-name>`, this is a resume — skip to Step 3a.

### Step 2: Git Setup

Follow the **Git Initialization** procedure in @.claude/skills/execute-phase/references/git-workflow.md:

1. If no `.git` directory exists → initialize git, create initial commit, create `develop` branch
2. If `develop` branch doesn't exist → create it from current HEAD
3. If working tree is dirty (uncommitted changes) → abort and tell the user to commit or stash
4. Checkout `develop` and pull if a remote exists

### Step 3: Create Worktree & Branch

Follow the **Worktree & Branch** procedure in @.claude/skills/execute-phase/references/git-workflow.md:

1. Derive the branch name from the plan filename (e.g., `feature/phase-1-environments-and-api-keys`)
2. Use the `EnterWorktree` tool with the branch name as the worktree name
3. Confirm the worktree is on the correct branch

#### Step 3a: Resume Into Existing Worktree

If resume was detected in Step 1.6:

1. Enter the existing worktree at `.claude/worktrees/<branch-name>`
2. Read the git log to find the last committed section: parse commit messages for `Phase {N}.{S}:` pattern, take the highest `{S}`
3. Report to the user: "Resuming Phase {N} from section {S+1}. Sections 1-{S} already committed."
4. Proceed to Step 4, starting from section S+1

### Step 4: Execute Sections

For each numbered section in the plan, in order:

#### 4a. Implement

1. Read the section's instructions from the plan
2. Follow @.claude/skills/execute-phase/references/section-execution.md to determine the implementation approach based on section type
3. Follow the plan verbatim — do not add features, refactor, or improve beyond what is specified
4. Use `php artisan make:*` commands where the plan specifies
5. When implementing test sections, invoke the **pest-testing** skill

#### 4b. Verify

1. Run `php artisan test --compact` (filter to relevant test files if they exist for this section)
2. Run `bun run lint`
3. Run `composer lint`
4. If linters made formatting changes, include them in this section's commit

#### 4c. On Failure → Fix & Retry

If tests fail, enter the fix-and-retry loop (max 3 attempts):

1. Invoke the **systematic-debugging** skill with the failure output
2. Apply the fix
3. Re-run verification (4b)
4. If still failing after 3 attempts → commit what works, report the failure to the user, and **stop execution entirely** (do not proceed to the next section)

#### 4d. Commit

1. Stage relevant files by specific path — never use `git add .` or `git add -A`
2. Commit with message format: `Phase {N}.{S}: {Section Title}`
   - Example: `Phase 1.4: Models`

### Step 5: Final Verification

After all sections are implemented and committed:

1. Run the full test suite: `php artisan test --compact`
2. Run full formatting: `vendor/bin/pint --dirty --format agent`
3. If any failures, enter the fix-and-retry loop from Step 4c
4. Commit any final fixes

### Step 6: Create PR

Follow the **PR Creation** procedure in @.claude/skills/execute-phase/references/git-workflow.md:

1. Push the feature branch to remote
2. Create a PR to `develop` using `gh pr create` with the structured summary format
3. Report the PR URL to the user

## Resuming and Rollback

### Resuming a failed phase

If execution stopped due to repeated test failures, re-invoke execute-phase with the same plan file. It will detect the existing branch and worktree, find the last committed section, and resume from the next one. Fix the failing code manually before re-invoking.

### Starting fresh

To abandon a partial execution and start over:

1. Remove the worktree: `git worktree remove .claude/worktrees/<branch-name>`
2. Delete the branch: `git branch -D <branch-name>`
3. Re-invoke execute-phase with the same plan file
