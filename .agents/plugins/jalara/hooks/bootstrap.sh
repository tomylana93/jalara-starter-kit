#!/usr/bin/env bash
#
# agy PreInvocation hook: Serena bootstrap and periodic tool-routing reminder.
#
# `serena-hooks` only supports the claude-code, codebuddy, vscode, and codex
# clients, so agy cannot reuse it; this script reproduces the same bootstrap
# through agy's own hook contract instead.
#
# Contract (see agy-customizations/docs/hooks.md):
#   stdin  - PreInvocation event JSON (camelCase; invocationNum, ...)
#   stdout - JSON object, optionally carrying `injectSteps`
#   cwd    - the directory containing hooks.json (this plugin's root)
#
# PreInvocation is used deliberately rather than PreToolUse: a PreToolUse hook
# must return a `decision`, which would override the developer's own permission
# prompts. This hook only injects context and never changes what agy may run.

set -uo pipefail

# Emit an empty result and exit cleanly. Never fail the invocation: a broken
# reminder must not block the agent loop.
emit_nothing() {
    echo '{}'
    exit 0
}

payload="$(cat)"

if ! command -v jq >/dev/null 2>&1; then
    emit_nothing
fi

invocation="$(printf '%s' "$payload" | jq -er '.invocationNum // empty' 2>/dev/null)" || emit_nothing

if [ -z "$invocation" ]; then
    emit_nothing
fi

case "$invocation" in
    ''|*[!0-9]*) emit_nothing ;;
esac

bootstrap_message=$(
    cat <<'MSG'
Repository bootstrap for this session:

1. Activate the `jalara-starter-kit` project with Serena and read Serena's
   initial instructions before touching code.
2. Read `mem:core`, then only the focused memories that the task actually
   touches.
3. Inspect the working tree with `git status --short` and preserve unrelated
   user changes.
4. You are the light implementor here. Read the workspace rule
   `.agents/plugins/jalara/rules/implementor-scope.md` before accepting the
   task, and hand oversized work back to the developer instead of growing it.
MSG
)

reminder_message=$(
    cat <<'MSG'
Routing reminder: prefer Serena's symbolic search, reference analysis, and
diagnostics over repeated full-file reads and grep. Use Laravel Boost for
version-specific Laravel documentation and schema, and Context7 for non-Laravel
libraries. Re-check that the change still fits the light-implementor scope.
MSG
)

if [ "$invocation" -eq 1 ]; then
    message="$bootstrap_message"
elif [ $((invocation % 12)) -eq 0 ]; then
    message="$reminder_message"
else
    emit_nothing
fi

jq -n --arg message "$message" '{injectSteps: [{ephemeralMessage: $message}]}'
