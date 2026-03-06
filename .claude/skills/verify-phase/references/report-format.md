# Report Format Reference

Exact templates and `gh` CLI commands for posting verification results to GitHub.

## Overall Status Determination

Derive the overall status from the collected findings across all sections:

| Overall Status | Condition |
|---|---|
| **PASS** | Every item is PASS or EXTRA — no DEVIATION or MISSING findings. |
| **DEVIATIONS FOUND** | At least one DEVIATION finding, but zero MISSING findings. |
| **MISSING ITEMS** | At least one MISSING finding (regardless of DEVIATION count). |

Evaluation order: check for MISSING first (highest severity), then DEVIATION, then default to PASS.

## PR Summary Comment

Post the main verification report as a PR comment.

### Command

```bash
gh pr comment {pr_number} --body "$(cat <<'EOF'
{comment_body}
EOF
)"
```

### Template

```markdown
## Phase {phase_number} Verification — {overall_status}

**Plan:** `{plan_filename}`
**Branch:** `{branch_name}`
**Verified at:** {commit_sha_short}

### Section Results

| # | Section | Status | Notes |
|---|---------|--------|-------|
| {section_number} | {section_title} | {section_status} | {section_notes} |

### Test Suite

```
{test_output}
```

**Result:** {test_pass_or_fail}

### Formatting

```
{pint_output}
```

**Result:** {formatting_pass_or_fail}

### Detailed Findings

{detailed_findings_block}

---

🤖 Verified with [Claude Code](https://claude.com/claude-code)
```

### Section Status Values

Use these in the Section Results table:

- `PASS` — all items passed
- `DEVIATION ({count})` — number of deviations in this section
- `MISSING ({count})` — number of missing items in this section
- `MIXED ({dev_count}D, {miss_count}M)` — section has both deviations and missing items

### Detailed Findings Block

Only include sections that have non-passing items. For each such section:

```markdown
#### Section {section_number}: {section_title}

| Item | Expected | Actual | Status |
|------|----------|--------|--------|
| {item_description} | {expected_state} | {actual_state} | {status} |
```

Omit sections where every item is PASS or EXTRA. If all sections pass, replace the detailed findings block with:

```markdown
All sections passed verification. No deviations or missing items found.
```

### Full Command Example

```bash
gh pr comment 42 --body "$(cat <<'EOF'
## Phase 2 Verification — ❌ MISSING ITEMS

**Plan:** `phase-2-api-keys.md`
**Branch:** `feature/api-keys`
**Verified at:** a1b2c3d

### Section Results

| # | Section | Status | Notes |
|---|---------|--------|-------|
| 1 | Config & Environment | PASS | |
| 2 | Migrations | DEVIATION (1) | Column type mismatch |
| 3 | Models | MISSING (2) | Missing scope and relationship |
| 4 | Controllers | PASS | |

### Test Suite

\```
Tests:    42 passed (168 assertions)
Duration: 3.21s
\```

**Result:** PASS

### Formatting

\```
Fixed 0 files
\```

**Result:** PASS

### Detailed Findings

#### Section 2: Migrations

| Item | Expected | Actual | Status |
|------|----------|--------|--------|
| `api_keys.expires_at` column type | `timestamp` | `date` | DEVIATION |

#### Section 3: Models

| Item | Expected | Actual | Status |
|------|----------|--------|--------|
| `ApiKey::scopeActive()` | Query scope filtering by `expires_at > now()` | Not found | MISSING |
| `ApiKey::environment()` | `BelongsTo` relationship to `Environment` | Not found | MISSING |

---

🤖 Verified with [Claude Code](https://claude.com/claude-code)
EOF
)"
```

## PR Review Comments

Post file-specific comments directly on the PR diff using the GitHub API.

### When to Use Review Comments

- **Use review comments** for findings tied to a specific file and line — deviations in a migration column, a missing method in a model, a wrong return type in a controller.
- **Use summary-only** for cross-cutting findings that span multiple files or have no single file anchor — route-controller alignment issues, missing test coverage across several areas, general architecture concerns.

### Getting the Commit SHA

```bash
HEAD_SHA=$(gh pr view {pr_number} --json headRefOid -q .headRefOid)
```

### Command

```bash
gh api repos/{owner}/{repo}/pulls/{pr_number}/comments \
  --method POST \
  --field body="{comment_body}" \
  --field path="{file_path}" \
  --field commit_id="{head_sha}" \
  --field position={diff_position}
```

### Field Reference

| Field | Description |
|---|---|
| `body` | The comment text. Include the status, expected state, and actual state. |
| `path` | Relative file path from the repo root (e.g., `app/Models/ApiKey.php`). |
| `commit_id` | The HEAD SHA of the PR branch. Must match the latest push. |
| `position` | Line position in the diff hunk (1-indexed from the start of the hunk, not the file). Use `null` or omit to comment on the file generally. |

### Comment Body Format

```markdown
**{status}** — {item_description}

**Expected:** {expected_state}
**Actual:** {actual_state}
```

### Full Command Example

```bash
HEAD_SHA=$(gh pr view 42 --json headRefOid -q .headRefOid)

gh api repos/myorg/myrepo/pulls/42/comments \
  --method POST \
  --field body="**DEVIATION** — \`expires_at\` column type

**Expected:** \`timestamp\` as specified in plan section 2
**Actual:** \`date\` — found in migration \`2026_02_15_create_api_keys_table.php\`" \
  --field path="database/migrations/2026_02_15_000000_create_api_keys_table.php" \
  --field commit_id="$HEAD_SHA" \
  --field position=12
```

### Posting Multiple Review Comments as a Single Review

To batch multiple comments into one review (preferred when there are several file-level findings):

```bash
HEAD_SHA=$(gh pr view {pr_number} --json headRefOid -q .headRefOid)

gh api repos/{owner}/{repo}/pulls/{pr_number}/reviews \
  --method POST \
  --field commit_id="$HEAD_SHA" \
  --field event="COMMENT" \
  --field body="Phase {phase_number} verification found {count} file-level issues." \
  --input - <<'EOF'
{
  "comments": [
    {
      "path": "{file_path_1}",
      "position": {position_1},
      "body": "**{status}** — {item_description}\n\n**Expected:** {expected}\n**Actual:** {actual}"
    },
    {
      "path": "{file_path_2}",
      "position": {position_2},
      "body": "**{status}** — {item_description}\n\n**Expected:** {expected}\n**Actual:** {actual}"
    }
  ]
}
EOF
```

## GitHub Issue

Create a tracking issue only when DEVIATION or MISSING findings exist. Do not create an issue when the overall status is PASS.

### Check for Existing Labels

Before applying labels, verify they exist:

```bash
gh label list --search "verification"
```

If the label does not exist, create it:

```bash
gh label create "verification" --description "Phase verification findings" --color "D93F0B"
```

### Command

```bash
gh issue create \
  --title "Phase {phase_number} verification: {count} items need attention" \
  --label "verification" \
  --body "$(cat <<'EOF'
{issue_body}
EOF
)"
```

### Title Format

```
Phase {phase_number} verification: {count} items need attention
```

Where `{count}` is the total number of DEVIATION + MISSING findings.

### Body Template

```markdown
## Phase {phase_number} Verification Results

**PR:** #{pr_number}
**Plan:** `{plan_filename}`
**Branch:** `{branch_name}`
**Overall status:** {overall_status}

### Items Needing Attention

{items_checklist}

### Summary

| Status | Count |
|--------|-------|
| PASS | {pass_count} |
| DEVIATION | {deviation_count} |
| MISSING | {missing_count} |
| EXTRA | {extra_count} |

---

🤖 Generated with [Claude Code](https://claude.com/claude-code)
```

### Items Checklist Format

Each item becomes a checkbox entry grouped by section:

```markdown
**Section {section_number}: {section_title}**

- [ ] **DEVIATION** — {item_description}: expected {expected}, found {actual}
- [ ] **MISSING** — {item_description}: expected {expected}
```

Only include DEVIATION and MISSING items. Do not include PASS or EXTRA items in the checklist.

### Full Command Example

```bash
gh issue create \
  --title "Phase 2 verification: 3 items need attention" \
  --label "verification" \
  --body "$(cat <<'EOF'
## Phase 2 Verification Results

**PR:** #42
**Plan:** `phase-2-api-keys.md`
**Branch:** `feature/api-keys`
**Overall status:** ❌ MISSING ITEMS

### Items Needing Attention

**Section 2: Migrations**

- [ ] **DEVIATION** — `api_keys.expires_at` column type: expected `timestamp`, found `date`

**Section 3: Models**

- [ ] **MISSING** — `ApiKey::scopeActive()`: expected query scope filtering by `expires_at > now()`
- [ ] **MISSING** — `ApiKey::environment()`: expected `BelongsTo` relationship to `Environment`

### Summary

| Status | Count |
|--------|-------|
| PASS | 35 |
| DEVIATION | 1 |
| MISSING | 2 |
| EXTRA | 0 |

---

🤖 Generated with [Claude Code](https://claude.com/claude-code)
EOF
)"
```

## Command Reference

Quick-reference for all `gh` commands used during reporting.

### Post PR Summary Comment

```bash
gh pr comment {pr_number} --body "$(cat <<'EOF'
{comment_body}
EOF
)"
```

### Post PR Review Comment (Single)

```bash
gh api repos/{owner}/{repo}/pulls/{pr_number}/comments \
  --method POST \
  --field body="{body}" \
  --field path="{file_path}" \
  --field commit_id="{head_sha}" \
  --field position={diff_position}
```

### Post PR Review (Batched Comments)

```bash
gh api repos/{owner}/{repo}/pulls/{pr_number}/reviews \
  --method POST \
  --field commit_id="{head_sha}" \
  --field event="COMMENT" \
  --field body="{review_summary}" \
  --input - <<'EOF'
{"comments": [{...}, {...}]}
EOF
```

### Create GitHub Issue

```bash
gh issue create \
  --title "{title}" \
  --label "verification" \
  --body "$(cat <<'EOF'
{issue_body}
EOF
)"
```

### Get PR Metadata

```bash
gh pr view {pr_number} --json number,headRefName,headRefOid,url
```

Individual fields:

```bash
# HEAD commit SHA
gh pr view {pr_number} --json headRefOid -q .headRefOid

# Branch name
gh pr view {pr_number} --json headRefName -q .headRefName

# PR URL
gh pr view {pr_number} --json url -q .url
```

### Get Repository Owner and Name

```bash
gh repo view --json owner,name -q '"\(.owner.login)/\(.name)"'
```

Or parse from the git remote:

```bash
gh repo view --json nameWithOwner -q .nameWithOwner
```
