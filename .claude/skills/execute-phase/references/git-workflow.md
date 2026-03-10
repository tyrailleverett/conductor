# Git Workflow Reference

Detailed git operations for the execute-phase skill.

## Git Initialization

Check and set up git infrastructure before starting work.

### Scenario A: No git repository exists

1. `git init`
2. `git add -A`
3. `git commit -m "Initial commit"`
4. `git branch -M main`
5. `git checkout -b develop`

### Scenario B: Git exists but no `develop` branch

1. `git checkout -b develop` (from current HEAD)

### Scenario C: Git and `develop` both exist

1. `git checkout develop`
2. If a remote named `origin` exists → `git pull origin develop`

### Pre-flight check

After any scenario above, verify the working tree is clean:

```
git status --porcelain
```

If output is non-empty → **abort** with this message:
> "Working tree has uncommitted changes. Please commit or stash them before running execute-phase."

### Cross-phase dependency check

Before creating a feature branch for Phase N (where N > 1), verify the prior phase was completed on develop:

```
git log develop --oneline | grep "Phase {N-1}\."
```

If nothing is found, warn the user that Phase {N-1} has not been completed on develop and ask for confirmation before proceeding.

## Branch Naming

Derive the feature branch name from the plan filename.

**Input:** `Plan_v1___Phase_1__Environments_and_API_Keys.md`

**Transformation:**
1. Strip the `Plan_v{N}___` prefix
2. Strip the `.md` extension
3. Replace `__` with `-`
4. Replace `_` with `-`
5. Lowercase everything
6. Prepend `feature/`

**Output:** `feature/phase-1-environments-and-api-keys`

## Branch Setup

1. From the `develop` branch, run: `git checkout -b <branch-name>`
2. Verify you are on the correct branch: `git branch --show-current`

## Commit Convention

### Message format

```
Phase {N}.{S}: {Section Title}
```

Where:
- `{N}` = phase number (from filename)
- `{S}` = section number (from the `## - [ ] S.` heading)
- `{Section Title}` = the heading text after the number

### Examples

| Section Heading | Commit Message |
|---|---|
| `## - [ ] 1. Configuration & Environment Variables` | `Phase 1.1: Configuration & Environment Variables` |
| `## - [ ] 4. Models` | `Phase 1.4: Models` |
| `## - [ ] 10. Tests` | `Phase 1.10: Tests` |

### Staging rules

- Stage files by specific path — never `git add .` or `git add -A`
- Stage only files created or modified by the current section
- If `vendor/bin/pint` made formatting changes, include those in the same commit
- Use `git status` to review what will be staged before committing

## Finalization (used by verify-phase)

After verify-phase confirms all checks pass, commit and push to develop:

### 1. Stage all changed files

Stage files by specific path — do not use `git add .` or `git add -A`.
Use `git status --porcelain` to enumerate all modified/new files, then stage each by path:

```bash
git add <path1> <path2> ...
```

### 2. Commit

```bash
git commit -m "Phase {N}: {Phase Title}"
```

Example: `Phase 1: Environments and API Keys`

### 3. Checkout develop

```bash
git checkout develop
```

### 4. Merge the feature branch

```bash
git merge <branch-name> --no-ff
```

### 5. Push to remote

```bash
git push origin develop
```

### 6. Delete the local feature branch

```bash
git branch -D <branch-name>
```

### 7. Delete the remote feature branch (if it was pushed)

```bash
git push origin --delete <branch-name>
```

Skip silently if the remote branch does not exist.

## Sections Implemented
- [x] 1. {Section Title}
- [x] 2. {Section Title}
- ...all sections...

## Test Results
{paste the output of the final `php artisan test --compact` run}

🤖 Generated with [Claude Code](https://claude.com/claude-code)
```

### 3. Report the PR URL

After the PR is created, report the URL to the user.
