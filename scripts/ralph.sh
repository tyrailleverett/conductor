#!/usr/bin/env bash
# ralph.sh — Autonomous phase loop orchestrator
# Runs implement → verify → finalize → merge for each incomplete phase.
#
# Usage:
#   ./scripts/ralph.sh
#
# Environment variables:
#   DRY_RUN=true          Preview actions without invoking Claude
#   START_FROM_PHASE=05   Skip to a specific phase number
#   NO_MERGE=true         Create PRs but don't auto-merge
#   MAX_PHASES=N          Override the safety cap (default: 10)
#   MAX_BUDGET=N          Per-invocation spend cap in USD (optional)
#   CLAUDE_MODEL=model    Model to use (default: opus)

set -euo pipefail

# ─── Config & Constants ───────────────────────────────────────────────────────

MAX_RETRIES="${MAX_RETRIES:-2}"
MAX_PHASES="${MAX_PHASES:-10}"
DRY_RUN="${DRY_RUN:-false}"
NO_MERGE="${NO_MERGE:-false}"
START_FROM_PHASE="${START_FROM_PHASE:-}"
MAX_BUDGET="${MAX_BUDGET:-}"
CLAUDE_MODEL="${CLAUDE_MODEL:-opus}"

TURN_LIMIT_IMPLEMENT=80
TURN_LIMIT_VERIFY=40
TURN_LIMIT_FINALIZE=30

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PLAN_DIR="$PROJECT_ROOT/specs/plan"
SKILL_DIR="$PROJECT_ROOT/.claude/skills"
LOG_DIR="$PROJECT_ROOT/logs/ralph"

# Running totals
TOTAL_INPUT_TOKENS=0
TOTAL_OUTPUT_TOKENS=0
PHASES_COMPLETED=0

# ─── Helper Functions ─────────────────────────────────────────────────────────

log() {
    local msg="[$(date '+%Y-%m-%d %H:%M:%S')] $*"
    echo "$msg"
    echo "$msg" >> "$LOG_DIR/ralph.log"
}

log_separator() {
    local line="════════════════════════════════════════════════════════════════"
    log "$line"
}

# Detect the next incomplete phase by scanning plan files for unchecked tasks.
# Returns: "PHASE_NUM|PHASE_NAME" or empty string if all complete.
detect_next_phase() {
    local start_from="${1:-}"

    while IFS= read -r plan_file; do
        local filename
        filename=$(basename "$plan_file")
        local phase_num
        phase_num=$(echo "$filename" | sed -E 's/phase-([0-9]+)-.*/\1/')

        # Skip phases before START_FROM_PHASE
        if [[ -n "$start_from" ]] && (( 10#$phase_num < 10#$start_from )); then
            continue
        fi

        # Check if this phase has any unchecked tasks
        if grep -q '^\- \[ \]' "$plan_file"; then
            local phase_name
            phase_name=$(echo "$filename" | sed -E 's/phase-[0-9]+-(.*)\.md/\1/' | tr '-' ' ')
            echo "${phase_num}|${phase_name}"
            return 0
        fi
    done < <(find "$PLAN_DIR" -name "phase-*.md" -type f | sort)

    echo ""
    return 0
}

# Map a phase number to its plan file path using glob.
phase_to_plan_file() {
    local phase_num="$1"
    local padded
    padded=$(printf "%02d" "$((10#$phase_num))")

    # Glob for the plan file
    local matches
    matches=$(find "$PLAN_DIR" -name "phase-${padded}-*.md" -type f 2>/dev/null | head -1)

    if [[ -z "$matches" ]]; then
        log "ERROR: No plan file found for phase $padded in $PLAN_DIR"
        return 1
    fi

    echo "$matches"
}

# Strip YAML frontmatter from a skill file (everything between --- delimiters).
strip_frontmatter() {
    local file="$1"
    sed '/^---$/,/^---$/d' "$file"
}

# Build common claude flags.
build_claude_flags() {
    local max_turns="$1"
    local flags=(
        "-p"
        "--output-format" "json"
        "--max-turns" "$max_turns"
        "--permission-mode" "bypassPermissions"
        "--model" "$CLAUDE_MODEL"
    )

    if [[ -n "$MAX_BUDGET" ]]; then
        flags+=("--max-budget-usd" "$MAX_BUDGET")
    fi

    echo "${flags[@]}"
}

# Invoke claude -p with retry logic and log capture.
# Arguments: step_name prompt max_turns phase_log_file
run_claude_step() {
    local step_name="$1"
    local prompt="$2"
    local max_turns="$3"
    local phase_log="$4"

    local attempt=0
    local max_attempts=$(( MAX_RETRIES + 1 ))

    while (( attempt < max_attempts )); do
        attempt=$(( attempt + 1 ))
        log "  [$step_name] Attempt $attempt/$max_attempts (max_turns=$max_turns)"

        if [[ "$DRY_RUN" == "true" ]]; then
            log "  [$step_name] DRY_RUN — would invoke claude -p with ${#prompt} chars"
            log "  [$step_name] Prompt preview (first 200 chars):"
            log "    ${prompt:0:200}..."
            return 0
        fi

        local step_log="$phase_log.${step_name}.attempt${attempt}.json"
        local flags
        flags=$(build_claude_flags "$max_turns")

        # shellcheck disable=SC2086
        local result
        if result=$(echo "$prompt" | claude $flags 2>>"$phase_log.stderr"); then
            echo "$result" > "$step_log"

            # Extract token usage from JSON output
            local input_tokens output_tokens
            input_tokens=$(echo "$result" | jq -r '.usage.input_tokens // 0' 2>/dev/null || echo "0")
            output_tokens=$(echo "$result" | jq -r '.usage.output_tokens // 0' 2>/dev/null || echo "0")
            TOTAL_INPUT_TOKENS=$(( TOTAL_INPUT_TOKENS + input_tokens ))
            TOTAL_OUTPUT_TOKENS=$(( TOTAL_OUTPUT_TOKENS + output_tokens ))

            log "  [$step_name] Completed (tokens: in=$input_tokens out=$output_tokens)"

            # Check for error in result
            local is_error
            is_error=$(echo "$result" | jq -r '.is_error // false' 2>/dev/null || echo "false")
            if [[ "$is_error" == "true" ]]; then
                log "  [$step_name] Claude reported an error, retrying..."
                continue
            fi

            return 0
        else
            local exit_code=$?
            log "  [$step_name] Failed with exit code $exit_code"
            if [[ -f "$phase_log.stderr" ]]; then
                log "  [$step_name] stderr: $(tail -5 "$phase_log.stderr")"
            fi

            if (( attempt < max_attempts )); then
                log "  [$step_name] Retrying in 10s..."
                sleep 10
            fi
        fi
    done

    log "  [$step_name] FAILED after $max_attempts attempts"
    return 1
}

# Squash-merge the phase branch into main and return to main.
merge_and_return_to_main() {
    local branch
    branch=$(git -C "$PROJECT_ROOT" branch --show-current)

    if [[ "$branch" == "main" || "$branch" == "master" ]]; then
        log "  Already on main, skipping merge step"
        return 0
    fi

    if [[ "$NO_MERGE" == "true" ]]; then
        log "  NO_MERGE=true — skipping merge of branch $branch"
        git -C "$PROJECT_ROOT" checkout main
        git -C "$PROJECT_ROOT" pull origin main
        return 0
    fi

    log "  Squash-merging $branch into main"
    git -C "$PROJECT_ROOT" checkout main
    git -C "$PROJECT_ROOT" pull origin main

    if git -C "$PROJECT_ROOT" merge --squash "$branch"; then
        git -C "$PROJECT_ROOT" commit -m "feat: merge phase branch $branch"
        git -C "$PROJECT_ROOT" push origin main
        git -C "$PROJECT_ROOT" branch -d "$branch"
        git -C "$PROJECT_ROOT" push origin --delete "$branch" || true
        log "  Branch $branch squash-merged into main and deleted"
    else
        log "  WARNING: Squash merge failed — attempting to continue"
        git -C "$PROJECT_ROOT" merge --abort 2>/dev/null || true
    fi
}

# ─── Prompt Builders ──────────────────────────────────────────────────────────

build_implement_prompt() {
    local plan_file="$1"
    local skill_content
    skill_content=$(strip_frontmatter "$SKILL_DIR/implement-phase/SKILL.md")

    # Replace $ARGUMENTS placeholder with actual plan file path
    skill_content="${skill_content//\$ARGUMENTS/$plan_file}"

    cat <<EOF
$skill_content

## Additional Instructions for Autonomous Mode

- When committing, use \`git commit --no-verify\` to skip pre-commit hooks (linting/tests are handled in the finalize step).
- Do NOT wait for user input. Complete the entire implementation and stop when ready for finalize.
- Begin now.
EOF
}

build_verify_prompt() {
    local plan_file="$1"
    local skill_content
    skill_content=$(strip_frontmatter "$SKILL_DIR/verify-phase/SKILL.md")

    # Replace $ARGUMENTS placeholder with actual plan file path
    skill_content="${skill_content//\$ARGUMENTS/$plan_file}"

    cat <<EOF
$skill_content

## Additional Instructions for Autonomous Mode

- Do NOT wait for user input. Complete all verification and fixes autonomously.
- When committing fixes, use \`git commit --no-verify\` to skip pre-commit hooks.
- Begin now.
EOF
}

build_finalize_prompt() {
    local skill_content
    skill_content=$(strip_frontmatter "$SKILL_DIR/finalize-phase/SKILL.md")

    cat <<EOF
$skill_content

## Additional Instructions for Autonomous Mode

- Do NOT wait for user input. Run all verification steps, fix issues, and push to remote autonomously.
- Do NOT skip pushing — it is mandatory.
- Begin now.
EOF
}

# ─── Main Loop ────────────────────────────────────────────────────────────────

main() {
    mkdir -p "$LOG_DIR"

    log_separator
    log "Ralph Wiggum Phase Loop starting"
    log "  Project root: $PROJECT_ROOT"
    log "  DRY_RUN: $DRY_RUN"
    log "  NO_MERGE: $NO_MERGE"
    log "  START_FROM_PHASE: ${START_FROM_PHASE:-auto}"
    log "  MAX_PHASES: $MAX_PHASES"
    log "  MAX_BUDGET: ${MAX_BUDGET:-unlimited}"
    log "  CLAUDE_MODEL: $CLAUDE_MODEL"
    log_separator

    # Ensure we start from main with latest code
    if [[ "$DRY_RUN" != "true" ]]; then
        local current_branch
        current_branch=$(git -C "$PROJECT_ROOT" branch --show-current)
        if [[ "$current_branch" != "main" ]]; then
            log "Not on main branch (on $current_branch). Switching to main..."
            git -C "$PROJECT_ROOT" checkout main
            git -C "$PROJECT_ROOT" pull origin main
        else
            git -C "$PROJECT_ROOT" pull origin main
        fi
    fi

    local phase_count=0

    while (( phase_count < MAX_PHASES )); do
        # Detect next incomplete phase
        local next_phase
        next_phase=$(detect_next_phase "$START_FROM_PHASE")

        if [[ -z "$next_phase" ]]; then
            log "All phases complete! 🎉"
            break
        fi

        local phase_num phase_name
        phase_num=$(echo "$next_phase" | cut -d'|' -f1)
        phase_name=$(echo "$next_phase" | cut -d'|' -f2)

        log_separator
        log "PHASE $phase_num: $phase_name"
        log_separator

        # Resolve plan file
        local plan_file
        if ! plan_file=$(phase_to_plan_file "$phase_num"); then
            log "ERROR: Could not find plan file for phase $phase_num — aborting"
            exit 1
        fi
        log "  Plan file: $plan_file"

        local phase_log="$LOG_DIR/phase-${phase_num}"

        # Build prompts
        local implement_prompt verify_prompt finalize_prompt
        implement_prompt=$(build_implement_prompt "$plan_file")
        verify_prompt=$(build_verify_prompt "$plan_file")
        finalize_prompt=$(build_finalize_prompt)

        # Step 1: Implement
        log "  Step 1/3: IMPLEMENT"
        if ! run_claude_step "implement" "$implement_prompt" "$TURN_LIMIT_IMPLEMENT" "$phase_log"; then
            log "FATAL: Implement step failed for phase $phase_num — aborting"
            exit 1
        fi

        # Step 2: Verify
        log "  Step 2/3: VERIFY"
        if ! run_claude_step "verify" "$verify_prompt" "$TURN_LIMIT_VERIFY" "$phase_log"; then
            log "FATAL: Verify step failed for phase $phase_num — aborting"
            exit 1
        fi

        # Step 3: Finalize
        log "  Step 3/3: FINALIZE"
        if ! run_claude_step "finalize" "$finalize_prompt" "$TURN_LIMIT_FINALIZE" "$phase_log"; then
            log "FATAL: Finalize step failed for phase $phase_num — aborting"
            exit 1
        fi

        # Merge PR and return to main
        if [[ "$DRY_RUN" != "true" ]]; then
            log "  Merging and returning to main..."
            merge_and_return_to_main
        else
            log "  DRY_RUN — skipping merge"
        fi

        PHASES_COMPLETED=$(( PHASES_COMPLETED + 1 ))
        phase_count=$(( phase_count + 1 ))

        log "  Phase $phase_num complete. Running totals:"
        log "    Phases completed: $PHASES_COMPLETED"
        log "    Total input tokens: $TOTAL_INPUT_TOKENS"
        log "    Total output tokens: $TOTAL_OUTPUT_TOKENS"

        # Clear START_FROM_PHASE after first iteration so we detect normally
        START_FROM_PHASE=""
    done

    log_separator
    log "Ralph Wiggum Phase Loop finished"
    log "  Phases completed this run: $PHASES_COMPLETED"
    log "  Total input tokens: $TOTAL_INPUT_TOKENS"
    log "  Total output tokens: $TOTAL_OUTPUT_TOKENS"
    log_separator
}

main "$@"
