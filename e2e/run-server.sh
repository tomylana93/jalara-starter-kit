#!/usr/bin/env bash

set -euo pipefail

mkdir -p storage/framework/testing
touch storage/framework/testing/playwright.sqlite

php artisan migrate:fresh --force --no-interaction
php artisan auth:init-superadmin --reset-password --no-interaction

pid_file="storage/framework/testing/playwright-server.pid"
echo "$$" > "${pid_file}"

# Realtime notifications need the WebSocket server plus a worker to drain the
# queued broadcast. Both start as children of this PID, which `exec` preserves,
# so the run-tests.sh cleanup reaps them along with the server.
php artisan reverb:start --host=127.0.0.1 --port="${REVERB_SERVER_PORT:-8081}" &
php artisan queue:work --tries=1 --sleep=1 &

exec php artisan serve --host=127.0.0.1 --port=8010 --no-reload
