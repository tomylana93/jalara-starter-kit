#!/usr/bin/env python3
"""PreToolUse gate: block work tools until Serena's manual has been read.

The SessionStart hook shipped by serena-agent only prints advisory text, so
compliance with "read the Instructions Manual first" is left to the agent's
discretion. This hook turns it into an actual precondition: the first gated
tool call of a session is denied with an instruction to call
``mcp__serena__initial_instructions`` first.

State lives in one marker file per session id. The gate fails open after
``MAX_DENIALS`` refusals so a misbehaving agent can never deadlock a session.
"""

from __future__ import annotations

import json
import os
import sys
import tempfile
import time

UNLOCK_TOOL = "mcp__serena__initial_instructions"

# Tools that represent real work on the repository. Everything else (including
# all other Serena calls) passes through untouched.
GATED_TOOLS = frozenset(
    {
        "Bash",
        "Edit",
        "Glob",
        "Grep",
        "MultiEdit",
        "NotebookEdit",
        "Read",
        "Write",
    }
)

# Fail open after this many denials in one session.
MAX_DENIALS = 3

# Drop marker files older than this so the state dir cannot grow forever.
STATE_TTL_SECONDS = 24 * 60 * 60

DENY_REASON = (
    "Serena Instructions Manual has not been read in this session. "
    f"Call `{UNLOCK_TOOL}` first, then retry this tool call. "
    "The manual defines the symbolic navigation and editing tools this project "
    "expects you to prefer over full-file reads and hand-written edits."
)


def state_dir() -> str:
    path = os.path.join(tempfile.gettempdir(), "claude-serena-gate")
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


def main() -> None:
    try:
        payload = json.load(sys.stdin)
    except (json.JSONDecodeError, ValueError):
        allow()

    tool_name = payload.get("tool_name") or ""
    session_id = payload.get("session_id") or ""
    if not session_id:
        allow()

    directory = state_dir()
    prune(directory)
    marker = os.path.join(directory, f"{session_id}.json")

    if tool_name == UNLOCK_TOOL:
        with open(marker, "w", encoding="utf-8") as handle:
            json.dump({"unlocked": True, "denials": 0}, handle)
        allow()

    state = {"unlocked": False, "denials": 0}
    try:
        with open(marker, encoding="utf-8") as handle:
            state.update(json.load(handle))
    except (OSError, json.JSONDecodeError, ValueError):
        pass

    if state.get("unlocked"):
        allow()
    if tool_name not in GATED_TOOLS:
        allow()

    denials = int(state.get("denials", 0)) + 1
    if denials > MAX_DENIALS:
        # Give up rather than trap the session in a denial loop.
        allow()

    state["denials"] = denials
    with open(marker, "w", encoding="utf-8") as handle:
        json.dump(state, handle)

    deny(DENY_REASON)


if __name__ == "__main__":
    main()
