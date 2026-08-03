#!/usr/bin/env bash

set -euo pipefail

# playwright.config.ts always supplies this, so the server, the workers, and the
# outer runner agree on which storage root owns (and later removes) every file
# this run creates.
: "${LARAVEL_STORAGE_PATH:?LARAVEL_STORAGE_PATH must be set by playwright.config.ts}"

testing_dir="${LARAVEL_STORAGE_PATH}/framework/testing"
mkdir -p "${testing_dir}"
touch "${testing_dir}/playwright.sqlite"

php artisan migrate:fresh --force --no-interaction
php artisan auth:init-superadmin --reset-password --no-interaction

pid_file="${testing_dir}/playwright-server.pid"
echo "$$" > "${pid_file}"

# Realtime notifications need the WebSocket server plus a worker to drain the
# queued broadcast. Both start as children of this PID, which `exec` preserves,
# so the run-tests.sh cleanup reaps them along with the server.
php artisan reverb:start --host=127.0.0.1 --port="${REVERB_SERVER_PORT:-8081}" &
php artisan queue:work --tries=1 --sleep=1 &

exec php artisan serve --host=127.0.0.1 --port=8010 --no-reload
