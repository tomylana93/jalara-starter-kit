#!/usr/bin/env bash

set -euo pipefail

pid_file="storage/framework/testing/playwright-server.pid"
build_dir="public/build"
hot_file="public/hot"

# Reverb values baked into the browser bundle at build time. They must match
# playwright.config.ts, and they must never outlive the test run inside the
# bundle the developer's own session serves.
export VITE_REVERB_APP_KEY="playwright-reverb-key"
export VITE_REVERB_HOST="127.0.0.1"
export VITE_REVERB_PORT="8081"
export VITE_REVERB_SCHEME="http"

snapshot_dir="$(mktemp -d)"
snapshot_taken=0
cleanup_done=0

# Content fingerprint of every asset the test build is allowed to touch. Taken
# before the build and again after restoration, it proves the developer's bundle
# and Vite hot marker came back exactly as they were.
asset_fingerprint() {
    {
        if [[ -d "${build_dir}" ]]; then
            find "${build_dir}" -type f -exec sha256sum {} + | sort
        fi

        if [[ -f "${hot_file}" ]]; then
            sha256sum "${hot_file}"
        fi
    } | sha256sum
}

restore_assets() {
    rm -rf "${build_dir}"

    if [[ -d "${snapshot_dir}/build" ]]; then
        cp -a "${snapshot_dir}/build" "${build_dir}"
    fi

    # Put the marker back exactly as found, or leave none behind when there was
    # none to begin with.
    rm -f "${hot_file}"

    if [[ -f "${snapshot_dir}/hot" ]]; then
        cp -a "${snapshot_dir}/hot" "${hot_file}"
    fi
}

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

    if (( snapshot_taken )); then
        restore_assets

        if [[ "$(asset_fingerprint)" != "${fingerprint_before}" ]]; then
            echo "e2e: could not restore ${build_dir} and ${hot_file} to their pre-test state" >&2
            status=1
        fi
    fi

    rm -rf "${snapshot_dir}"

    exit "${status}"
}

trap cleanup EXIT INT TERM

fingerprint_before="$(asset_fingerprint)"

if [[ -d "${build_dir}" ]]; then
    cp -a "${build_dir}" "${snapshot_dir}/build"
fi

if [[ -f "${hot_file}" ]]; then
    cp -a "${hot_file}" "${snapshot_dir}/hot"
fi

snapshot_taken=1

# A hot marker left by a development session makes Laravel serve the Vite dev
# client instead of the bundle built below, so the test run would never exercise
# the test Reverb port. Drop it for the duration of the run; cleanup restores it.
rm -f "${hot_file}"

pnpm run build

setsid pnpm exec playwright test "$@" &
test_pid=$!
wait "${test_pid}"
