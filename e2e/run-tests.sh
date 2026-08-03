#!/usr/bin/env bash

set -euo pipefail

# Every filesystem write the application performs during a run is owned by this
# invocation: the SQLite database, sessions, logs, private uploads, and
# processed media all live under a private temporary Laravel storage root that
# cleanup removes. `bootstrap/app.php` bridges LARAVEL_STORAGE_PATH from
# `getenv()`, without which the `php -S` child behind `artisan serve` would keep
# using the default storage path while the CLI processes honored this one.
storage_root="$(mktemp -d "${TMPDIR:-/tmp}/jalara-e2e-storage.XXXXXXXX")"
storage_id="${storage_root##*.}"
export LARAVEL_STORAGE_PATH="${storage_root}"

# Public branding files must stay browser-accessible, so the isolated public
# root gets its own uniquely named symlink. The developer's `public/storage`
# link is never read, written, or replaced. playwright.config.ts turns this name
# into the public disk URL.
public_storage_link="public/storage-e2e-${storage_id}"
export E2E_PUBLIC_STORAGE_LINK="storage-e2e-${storage_id}"

pid_file="${storage_root}/framework/testing/playwright-server.pid"

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

    if [[ -f "${pid_file:-}" ]]; then
        server_pid="$(cat "${pid_file}")"
        pkill -TERM -P "${server_pid}" 2>/dev/null || true
        kill "${server_pid}" 2>/dev/null || true
        rm -f "${pid_file}"
    fi

    # Only the isolated assets are removed; the development bundle and hot
    # marker were never touched.
    if [[ -n "${build_dir:-}" ]]; then
        rm -rf "${build_dir}"
    fi

    if [[ -n "${hot_file:-}" ]]; then
        rm -f "${hot_file}"
    fi

    # Only this run's own symlink is removed, and only while it still points at
    # this run's own public storage root.
    if [[ -L "${public_storage_link:-}" ]] \
        && [[ "$(readlink "${public_storage_link}")" == "${storage_root}/app/public" ]]; then
        rm -f "${public_storage_link}"
    fi

    # The prefix check keeps an unexpected value from ever widening this into a
    # delete of real application storage.
    if [[ "${storage_root:-}" == "${TMPDIR:-/tmp}/jalara-e2e-storage."* ]] && [[ -d "${storage_root}" ]]; then
        rm -rf "${storage_root}"
    fi

    exit "${status}"
}

# Installed before the first fallible step, so a failure while scaffolding the
# storage root or creating the public symlink still removes everything this run
# owns. Every guard above tolerates the partial state that implies.
trap cleanup EXIT INT TERM

mkdir -p \
    "${storage_root}/app/private" \
    "${storage_root}/app/public" \
    "${storage_root}/framework/cache/data" \
    "${storage_root}/framework/sessions" \
    "${storage_root}/framework/testing" \
    "${storage_root}/framework/views" \
    "${storage_root}/logs"

ln -s "${storage_root}/app/public" "${public_storage_link}"

# Clear Laravel's cached configuration to ensure E2E_ASSET_ISOLATION="true" is
# honored by both Vite and the Laravel application processes.
php artisan config:clear

pnpm run build

setsid pnpm exec playwright test "$@" &
test_pid=$!
wait "${test_pid}"
