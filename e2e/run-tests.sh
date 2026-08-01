#!/usr/bin/env bash

set -euo pipefail

pid_file="storage/framework/testing/playwright-server.pid"

# Assets live outside `public/build` and `public/hot` for the whole run, so a
# development session started before, during, or after the tests keeps sole
# ownership of those paths. `vite.config.ts` and `config/app.php` derive the
# same isolated paths from this variable, and playwright.config.ts forwards the
# process environment to the application under test.
export E2E_ASSET_ISOLATION="true"
build_dir="public/build-e2e"
hot_file="public/hot-e2e"

# Reverb values baked into the browser bundle at build time. They must match
# playwright.config.ts, and they must never outlive the test run inside the
# bundle the developer's own session serves.
export VITE_REVERB_APP_KEY="playwright-reverb-key"
export VITE_REVERB_HOST="127.0.0.1"
export VITE_REVERB_PORT="8081"
export VITE_REVERB_SCHEME="http"

# Clear Laravel's cached configuration to ensure E2E_ASSET_ISOLATION="true" is
# honored by both Vite and the Laravel application processes.
php artisan config:clear

cleanup_done=0

cleanup() {
    local status=$?

    if (( cleanup_done )); then
        return
    fi

    cleanup_done=1

    if [[ -n "${test_pid:-}" ]]; then
        kill -- -"${test_pid}" 2>/dev/null || true
    fi

    if [[ -f "${pid_file}" ]]; then
        server_pid="$(cat "${pid_file}")"
        pkill -TERM -P "${server_pid}" 2>/dev/null || true
        kill "${server_pid}" 2>/dev/null || true
        rm -f "${pid_file}"
    fi

    # Only the isolated assets are removed; the development bundle and hot
    # marker were never touched.
    rm -rf "${build_dir}"
    rm -f "${hot_file}"

    exit "${status}"
}

trap cleanup EXIT INT TERM

pnpm run build

setsid pnpm exec playwright test "$@" &
test_pid=$!
wait "${test_pid}"
