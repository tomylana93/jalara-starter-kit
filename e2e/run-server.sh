#!/usr/bin/env bash

set -euo pipefail

mkdir -p storage/framework/testing
touch storage/framework/testing/playwright.sqlite

php artisan migrate:fresh --force --no-interaction
php artisan auth:init-superadmin --reset-password --no-interaction

pid_file="storage/framework/testing/playwright-server.pid"
echo "$$" > "${pid_file}"

exec php artisan serve --host=127.0.0.1 --port=8010 --no-reload
