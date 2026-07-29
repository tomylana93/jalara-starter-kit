#!/usr/bin/env bash

set -euo pipefail

pid_file="storage/framework/testing/playwright-server.pid"

cleanup() {
    if [[ -n "${test_pid:-}" ]]; then
        kill -- -"${test_pid}" 2>/dev/null || true
    fi

    if [[ -f "${pid_file}" ]]; then
        server_pid="$(cat "${pid_file}")"
        pkill -TERM -P "${server_pid}" 2>/dev/null || true
        kill "${server_pid}" 2>/dev/null || true
        rm -f "${pid_file}"
    fi
}

trap cleanup EXIT INT TERM
setsid pnpm exec playwright test "$@" &
test_pid=$!
wait "${test_pid}"
