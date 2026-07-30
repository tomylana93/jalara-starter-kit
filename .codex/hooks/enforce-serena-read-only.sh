#!/usr/bin/env bash
#
# Codex PreToolUse hook: fail-closed allow-list for Serena MCP tools.
#
# Codex runs as the planning agent for this repository and must never mutate
# project state. `sandbox_mode = "read-only"` does NOT cover Serena: the Serena
# MCP server is a separate HTTP process (see .codex/config.toml) whose writes
# happen outside the Codex sandbox. This hook is therefore the only enforcement
# boundary for MCP-side mutation.
#
# Contract:
#   stdin  - Codex PreToolUse event JSON (tool_name, tool_input, tool_use_id, ...)
#   exit 0 - allow the tool call
#   exit 2 - block the tool call before the server is reached
#
# Fail-closed: anything not explicitly audited as read-only is blocked, including
# tools introduced by future Serena upgrades. Never convert this to a deny-list.
#
# Verifying this hook is actually loaded (config correctness is not proof):
#   codex exec 'Read the core memory via Serena, then write a memory named
#               probe_delete_me containing "x".'
# The read must succeed and the write must be blocked before Serena is reached.
# Confirm with `serena memories list` that probe_delete_me was never created.
# Do not add an env-var escape hatch for this: Codex does not forward the parent
# environment into the hook process, so such a switch silently does nothing.

set -uo pipefail

# Audited read-only Serena tools. Add a name here ONLY after confirming the tool
# cannot write files, memories, or project configuration.
ALLOWED_TOOLS="
mcp__serena__initial_instructions
mcp__serena__list_memories
mcp__serena__read_memory
mcp__serena__search_for_pattern
mcp__serena__get_symbols_overview
mcp__serena__find_symbol
mcp__serena__find_declaration
mcp__serena__find_implementations
mcp__serena__find_referencing_symbols
mcp__serena__get_diagnostics_for_file
"

block() {
    echo "[enforce-serena-read-only] blocked: $1" >&2
    echo "Codex is the planning agent in this repository and may not mutate project state." >&2
    echo "Hand the change to Claude Code via a HANDOFF block and /apply-plan." >&2
    exit 2
}

payload="$(cat)"

if [ -z "${payload//[[:space:]]/}" ]; then
    block "empty hook payload (cannot verify tool name)"
fi

tool_name="$(printf '%s' "$payload" | jq -er '.tool_name // empty' 2>/dev/null)" || {
    block "unparsable hook payload (cannot verify tool name)"
}

if [ -z "$tool_name" ]; then
    block "hook payload carried no tool_name"
fi

# Defensive: the configured matcher is ^mcp__serena__.*$, but never assume the
# matcher is the thing keeping non-Serena tools out of this script.
case "$tool_name" in
    mcp__serena__*) ;;
    *) exit 0 ;;
esac

for allowed in $ALLOWED_TOOLS; do
    if [ "$tool_name" = "$allowed" ]; then
        exit 0
    fi
done

block "$tool_name is not on the audited read-only allow-list"
