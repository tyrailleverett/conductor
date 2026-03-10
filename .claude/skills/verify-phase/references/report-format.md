# Report Format Reference

Template for printing the verification summary to the terminal after verify-phase completes.

## Overall Status Determination

Derive the overall status from the collected findings before and after auto-fixes:

| Overall Status | Condition |
|---|---|
| **PASS** | Every item was PASS or EXTRA — no fixes were needed. |
| **FIXED** | Deviations or missing items were found and auto-fixed successfully. |
| **FAILED** | Tests still failing after 3 fix attempts — changes not pushed. |

## Terminal Report Template

Print this summary after Step 5 (Finalize) completes:

```
Phase {phase_number} Verification — {overall_status}

Plan:    {plan_filename}
Branch:  {branch_name} (deleted)

Section Results
───────────────────────────────────────────────────────
 #   Section                          Initial    Fixed
───────────────────────────────────────────────────────
 1   {section_title}                  PASS
 2   {section_title}                  DEVIATION  ✓
 3   {section_title}                  MISSING    ✓
───────────────────────────────────────────────────────

Tests:     {test_result}   ({test_count} tests, {assertion_count} assertions)
Formatting: {formatting_result}

{pushed_confirmation}
```

### Field Reference

| Field | Description |
|---|---|
| `{overall_status}` | `PASS`, `FIXED`, or `FAILED` |
| `{plan_filename}` | The plan file name (e.g., `Plan_v1___Phase_1__Environments.md`) |
| `{branch_name}` | The feature branch that was created and then deleted |
| `{section_title}` | The section title from the plan heading |
| Initial status | `PASS`, `DEVIATION`, `MISSING`, or `EXTRA` for each section |
| Fixed column | `✓` if auto-fixed, blank if already passing |
| `{test_result}` | `PASS` or `FAIL` |
| `{formatting_result}` | `PASS` or `Fixed {N} files` |
| `{pushed_confirmation}` | `✓ Pushed to origin/develop` or `✗ Not pushed — tests still failing` |

### Detailed Findings Block

When DEVIATION or MISSING items were found (before fixes), append a detailed block after the summary:

```
Detailed Findings (before fixes)
───────────────────────────────────────────────────────

Section {N}: {section_title}

  DEVIATION  {item_description}
    Expected: {expected_state}
    Actual:   {actual_state}

  MISSING    {item_description}
    Expected: {expected_state}
    Actual:   Not found
```

Omit this block when all sections were PASS or EXTRA.

### FAILED Status

When tests are still failing after 3 attempts, end the report with:

```
Phase {phase_number} Verification — FAILED

Tests are still failing after 3 fix attempts. Changes have NOT been pushed.
The working directory remains on branch {branch_name} with all files in place.

Next steps:
  1. Review the test failures above
  2. Fix the issues manually
  3. Re-run verify-phase with the same plan file
```

