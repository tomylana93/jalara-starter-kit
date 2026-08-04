<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="public/assets/images/branding/logo-dark.png">
    <source media="(prefers-color-scheme: light)" srcset="public/assets/images/branding/logo.png">
    <img alt="Jalara Starter Kit Logo" src="public/assets/images/branding/logo.png" width="320">
  </picture>
</p>

# Jalara Starter Kit

A professional and production-ready Laravel application starter kit. Jalara provides a highly-polished, feature-rich foundation with integrated authentication, role-based authorization, comprehensive application settings, dynamic branding, and a complete testing suite.

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.5-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.5">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/Inertia-3-9553E9?style=flat-square&logo=inertia&logoColor=white" alt="Inertia.js 3">
  <img src="https://img.shields.io/badge/Vue-3-4FC08D?style=flat-square&logo=vue.js&logoColor=white" alt="Vue 3">
  <img src="https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white" alt="Tailwind CSS 4">
  <a href="https://github.com/tomylana93/jalara-starter-kit/actions/workflows/tests.yml"><img src="https://github.com/tomylana93/jalara-starter-kit/actions/workflows/tests.yml/badge.svg?branch=main" alt="tests"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="License"></a>
</p>

## Key Features

- **Authentication & Account Security**: Secure authentication, password reset, email verification, and secure session handling powered by Laravel Fortify, with individual account management.
- **RBAC & User Management**: Granular roles and permissions system built with Spatie Laravel Permission, alongside an administrative panel for user provisioning, export, and management.
- **Runtime Settings & Branding**: Dynamic system configuration panels including general parameters, authorization rules, mail server setup, and brand assets customization.
- **Notifications**: Dedicated notification center supporting multiple delivery channels to keep users informed.
- **Direct Messaging**: In-app private chat system supporting instant messaging and rich reactions.
- **Internal Documentation**: Built-in documentation center allowing all authenticated users to read and search, with authoring and management restricted to super administrators.
- **Media Processing**: Integrated media storage and processing lifecycle supporting queued image processing, optimization, lifecycle tracking, and orphan cleanup.
- **Localization**: Multi-language support for English and Indonesian with a runtime-configurable default locale.
- **Typed Inertia/Vue Frontend**: Seamless SPA architecture combining Vue 3 and Inertia.js 3, powered by Laravel Wayfinder for end-to-end type-safe routing.

## Installation

Jalara installs as a community starter kit through the [Laravel installer](https://laravel.com/docs/installation) (version 5.31 or newer). This is the supported command:

```bash
laravel new my-app \
  --using=https://github.com/tomylana93/jalara-starter-kit \
  --database=sqlite \
  --pnpm \
  --no-boost \
  --no-interaction
```

The installer clones the starter kit, installs the Composer dependencies, writes `.env`, generates the application key, runs the Jalara installer hooks, configures and migrates the database, and finally installs and builds the frontend with pnpm.

**Required — dropping either of these produces a different application:**

- `--pnpm` — Jalara is locked with `pnpm-lock.yaml`, and its Composer scripts call `pnpm`. The installer deletes the lock files of every package manager it was not told to use, so any other choice discards Jalara's locked frontend dependency graph. Without an explicit package manager the installer also skips the dependency install and asset build entirely.
- `--no-interaction` — an interactive install treats the starter kit as a bare Laravel skeleton and reinstalls the test suite over it (`pest --init` and `pest --drift` rewrite `tests/Pest.php` and the existing tests, and the accompanying unpinned `composer update` ignores the committed lock). Jalara already ships Pest 5 with a configured test suite, so this step must not run.

**Defensive — redundant today, kept for explicitness:**

- `--no-boost` — Jalara already depends on Laravel Boost and keeps its own curated configuration in `boost.json` and `.ai/`, with the generated agent guidelines refreshed through `composer run agents:update`. Laravel installer 5.31 only selects Boost while prompting, so `--no-interaction` already skips the step; `--no-boost` states the intent outright and keeps it skipped if that default ever changes. Boost remains installed in the resulting application either way.

**Choice:**

- `--database=` — SQLite needs no further configuration. MySQL, MariaDB, and PostgreSQL are also supported; note that production refuses to run on SQLite.

To skip the frontend install and build, replace `--pnpm` with `--no-node` and run `pnpm install && pnpm run build` yourself afterwards.

If you clone the repository instead of installing it, run the equivalent setup yourself:

```bash
composer run setup
```

### Creating the Super Admin

Jalara ships without any user. The super admin is created from environment-backed credentials so nothing secret is committed or seeded. After installation, set the credentials in `.env`:

```dotenv
SUPER_ADMIN_NAME="Super Admin"
SUPER_ADMIN_EMAIL=admin@example.com
SUPER_ADMIN_PASSWORD=
```

Then create the user and synchronize the authorization catalog:

```bash
php artisan auth:init-superadmin
```

The command is idempotent: it restores the protected super admin and re-applies its role on every run, and `--reset-password` re-applies the configured password. Public registration stays disabled; further users are provisioned from the application's user management.

### Starting Development

```bash
composer run dev
```

### Operational Requirements

To support asynchronous tasks, real-time features, and file storage, Jalara requires the following services and processes:

1. **Task Scheduler**: Periodic tasks and cleanup operations must be scheduled.
   - **Production**: Configure a cron entry on your server to run every minute:
     ```cron
     * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
     ```
   - **Local Development**: You may optionally run the scheduler in the foreground:
     ```bash
     php artisan schedule:work
     ```

2. **Queue Worker**: Asynchronous background tasks (such as notifications and image processing) run via queue workers.
   - **Production**: Run `php artisan queue:work` as a long-running process managed by a process monitor (e.g., Supervisor or systemd) to ensure it restarts automatically if it fails.
   - **Local Development**: Run the worker in the foreground:
     ```bash
     php artisan queue:work
     ```

3. **Real-time Broadcasting (Laravel Reverb)**: Chat features and live updates are driven by WebSockets.
   - **Production**: Run `php artisan reverb:start` as a long-running process managed by a process monitor (e.g., Supervisor or systemd) to keep the WebSockets server active.
   - **Local Development**: Start the server in the foreground:
     ```bash
     php artisan reverb:start
     ```

4. **Storage Link**: Uploaded files and brand assets are served publicly.
   - Supported installation paths (the Laravel installer and `composer run setup`) automatically link the public directory.
   - Manual execution is only required for custom deployments or if the link is missing:
     ```bash
     php artisan storage:link
     ```

## Technology Stack

### Backend
- **Core Framework**: Laravel 13
- **PHP Version**: PHP 8.5
- **Authentication Engine**: Laravel Fortify
- **Authorization Engine**: Spatie Laravel Permission
- **Settings Store**: Spatie Laravel Settings

### Frontend
- **SPA Bridge**: Inertia.js 3
- **UI Library**: Vue 3
- **Styling**: Tailwind CSS 4

### Testing, Quality & Linting
- **Backend Testing**: Pest 5
- **Frontend Testing**: Vitest
- **End-to-End Testing**: Playwright
- **Static Analysis & Refactoring**: Larastan & Rector
- **Formatting & Linting**: Pint (PHP), ESLint & Prettier (Frontend)

## Quality Assurance

Jalara is built with stability and software quality as first-class citizens. The codebase is backed by a comprehensive, multi-tiered validation suite spanning unit, feature, and end-to-end verification powered by Pest, Vitest, and Playwright. The project enforces a minimum application coverage threshold of 80%.

To maintain code standards, we utilize:
- **Larastan & Rector** for static analysis and automated refactoring of backend PHP code.
- **Pint** for PHP code formatting.
- **ESLint & Prettier** for frontend code linting and formatting.

## License

This project is open-source software licensed under the [MIT License](LICENSE).
