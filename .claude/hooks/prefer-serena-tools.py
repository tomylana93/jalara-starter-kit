#!/usr/bin/env python3
"""PreToolUse gate: route code reads and edits through Serena's symbolic tools.

The shared Serena MCP server runs with ``--context ide`` because Codex connects
to the same process, so the stronger tool-routing prompt that ships
with Serena's ``claude-code`` context never reaches this client. Guideline text
alone loses to the built-in tool descriptions, which sit right at the point of
decision. This hook moves the rule to that same point.

Gating is deliberately narrower than Serena's generic override:

* ``Read`` is gated for PHP only. `.vue` orientation reads are prescribed by
  `.ai/guidelines/agent-workflow.md` because Serena's overview exposes template
  nodes there, and `.ts` symbol navigation rides on the Vue language server
  after `.serena/project.local.yml` dropped the second tsserver.
* ``Edit``/``Write`` are gated for PHP, TypeScript and Vue, where
  ``replace_symbol_body`` and ``replace_content`` are direct equivalents.

Escape hatches keep the gate from blocking legitimate work: a targeted
``Read`` with ``offset``/``limit``, a file already inspected symbolically this
session, and creation of a new file all pass. The gate also fails open after
``MAX_CONSECUTIVE_DENIALS`` refusals in a row, so it can never deadlock a
session; any Serena call resets that count and re-arms it.
"""

from __future__ import annotations

import json
import os
import sys
import tempfile
import time

# Serena calls that establish symbolic knowledge of a file. Once one of these
# has named a path, built-in reads of that path are no longer discovery.
SERENA_READ_TOOLS = frozenset(
    {
        "mcp__serena__find_declaration",
        "mcp__serena__find_implementations",
        "mcp__serena__find_referencing_symbols",
        "mcp__serena__find_symbol",
        "mcp__serena__get_symbols_overview",
        "mcp__serena__search_for_pattern",
    }
)

# Serena calls that carry out an edit. Together with the read tools above they
# are the compliance signal that re-arms the gate.
SERENA_EDIT_TOOLS = frozenset(
    {
        "mcp__serena__insert_after_symbol",
        "mcp__serena__insert_before_symbol",
        "mcp__serena__rename_symbol",
        "mcp__serena__replace_content",
        "mcp__serena__replace_in_files",
        "mcp__serena__replace_symbol_body",
        "mcp__serena__safe_delete_symbol",
    }
)

READ_GATED_SUFFIXES = (".php",)
EDIT_GATED_SUFFIXES = (".php", ".ts", ".tsx", ".vue")

EDIT_TOOLS = frozenset({"Edit", "MultiEdit", "Write"})

# Fail open after this many denials in a row. Any Serena call resets the count,
# so the gate stays armed for a whole session yet can never trap one.
MAX_CONSECUTIVE_DENIALS = 3

# Drop marker files older than this so the state dir cannot grow forever.
STATE_TTL_SECONDS = 24 * 60 * 60

READ_REASON = (
    "Discovery reads of PHP go through Serena in this project. Call "
    "`mcp__serena__get_symbols_overview` on this file, then "
    "`mcp__serena__find_symbol` with `include_body=true` for the symbols you "
    "actually need. Retry the built-in Read afterwards if a symbolic read is "
    "still the wrong shape, or pass `offset`/`limit` when a handful of lines "
    "is all you want. See the tool routing table in "
    "`.ai/guidelines/agent-workflow.md`."
)

EDIT_REASON = (
    "Edits to PHP, TypeScript and Vue go through Serena in this project. Use "
    "`mcp__serena__replace_symbol_body` to replace a whole symbol, "
    "`mcp__serena__insert_before_symbol`/`insert_after_symbol` to add one, "
    "`mcp__serena__replace_content` for lines inside a symbol, or "
    "`mcp__serena__replace_in_files` to repeat one edit across files. See the "
    "tool routing table in `.ai/guidelines/agent-workflow.md`."
)


def state_dir() -> str:
    path = os.path.join(tempfile.gettempdir(), "claude-serena-routing")
    os.makedirs(path, exist_ok=True)
    return path


def prune(directory: str) -> None:
    cutoff = time.time() - STATE_TTL_SECONDS
    try:
        entries = os.listdir(directory)
    except OSError:
        return
    for entry in entries:
        candidate = os.path.join(directory, entry)
        try:
            if os.path.getmtime(candidate) < cutoff:
                os.remove(candidate)
        except OSError:
            pass


def allow() -> None:
    sys.exit(0)


def deny(reason: str) -> None:
    print(
        json.dumps(
            {
                "hookSpecificOutput": {
                    "hookEventName": "PreToolUse",
                    "permissionDecision": "deny",
                    "permissionDecisionReason": reason,
                }
            }
        )
    )
    sys.exit(0)


def project_dir() -> str:
    return os.environ.get("CLAUDE_PROJECT_DIR") or os.getcwd()


def canonical(path: str) -> str:
    """Resolve a path so Serena's relative form and Claude's absolute form match."""
    if not os.path.isabs(path):
        path = os.path.join(project_dir(), path)
    return os.path.normpath(path)


def load_state(marker: str) -> dict:
    state = {"denials": 0, "inspected": []}
    try:
        with open(marker, encoding="utf-8") as handle:
            loaded = json.load(handle)
    except (OSError, json.JSONDecodeError, ValueError):
        return state
    if isinstance(loaded, dict):
        state.update(loaded)
    return state


def save_state(marker: str, state: dict) -> None:
    try:
        with open(marker, "w", encoding="utf-8") as handle:
            json.dump(state, handle)
    except OSError:
        pass


def main() -> None:
    try:
        payload = json.load(sys.stdin)
    except (json.JSONDecodeError, ValueError):
        allow()

    tool_name = payload.get("tool_name") or ""
    tool_input = payload.get("tool_input")
    if not isinstance(tool_input, dict):
        tool_input = {}
    session_id = payload.get("session_id") or ""
    if not session_id:
        allow()

    directory = state_dir()
    prune(directory)
    marker = os.path.join(directory, f"{session_id}.json")

    if tool_name in SERENA_READ_TOOLS or tool_name in SERENA_EDIT_TOOLS:
        state = load_state(marker)
        state["denials"] = 0
        relative_path = tool_input.get("relative_path")
        if tool_name in SERENA_READ_TOOLS and isinstance(relative_path, str) and relative_path:
            inspected = set(state.get("inspected") or [])
            inspected.add(canonical(relative_path))
            state["inspected"] = sorted(inspected)
        save_state(marker, state)
        allow()

    if tool_name != "Read" and tool_name not in EDIT_TOOLS:
        allow()

    file_path = tool_input.get("file_path")
    if not isinstance(file_path, str) or not file_path:
        allow()

    state = load_state(marker)
    lowered = file_path.lower()
    if tool_name == "Read":
        if not lowered.endswith(READ_GATED_SUFFIXES):
            allow()
        # A bounded read is the sanctioned way to look at a few lines.
        if tool_input.get("offset") is not None or tool_input.get("limit") is not None:
            allow()
        # Once the file has been inspected symbolically, a read is no longer
        # discovery, so the remaining reasons to read it in full are genuine.
        if canonical(file_path) in set(state.get("inspected") or []):
            allow()
        reason = READ_REASON
    else:
        if not lowered.endswith(EDIT_GATED_SUFFIXES):
            allow()
        # Creating a file is not an edit Serena's symbolic tools can express.
        if tool_name == "Write" and not os.path.exists(canonical(file_path)):
            allow()
        # No symbolic-read exemption here: having read the file is the argument
        # for `replace_symbol_body`, not against it. Built-in edits would also
        # be rejected downstream for a file only Serena has read.
        reason = EDIT_REASON

    denials = int(state.get("denials", 0)) + 1
    if denials > MAX_CONSECUTIVE_DENIALS:
        # Give up rather than trap the session in a denial loop. The next
        # Serena call re-arms the gate.
        state["denials"] = 0
        save_state(marker, state)
        allow()

    state["denials"] = denials
    save_state(marker, state)

    deny(reason)


if __name__ == "__main__":
    main()
