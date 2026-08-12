# Setup & Technical Documentation

This document provides detailed technical information for installing, configuring, and operating the **Jalara Starter Kit**.

## Detailed Installation

The standard installation command clones the starter kit, installs Composer dependencies, configures `.env`, generates the application key, runs installer hooks, configures and migrates the database, and builds the frontend:

```bash
laravel new my-app \
  --using=https://github.com/tomylana93/jalara-starter-kit \
  --database=sqlite \
  --pnpm \
  --no-boost \
  --no-interaction
```

### Parameter Breakdown

*   **`--using=https://github.com/tomylana93/jalara-starter-kit`**: Specifies the custom repository to use as the starter kit.
*   **`--database=sqlite`**: SQLite is recommended for local development as it requires zero setup. MySQL, MariaDB, and PostgreSQL are also supported (note that production refuses to run on SQLite).
*   **`--pnpm`** (*Required*): Jalara is locked with `pnpm-lock.yaml`, and its Composer scripts call `pnpm`. The installer deletes the lock files of every package manager it was not told to use, so any other choice discards Jalara's locked frontend dependency graph. Without an explicit package manager, the installer also skips the dependency install and asset build entirely.
*   **`--no-interaction`** (*Required*): An interactive install treats the starter kit as a bare Laravel skeleton and reinstalls the test suite over it (`pest --init` and `pest --drift` rewrite `tests/Pest.php` and the existing tests, and the accompanying unpinned `composer update` ignores the committed lock). Jalara already ships Pest 5 with a configured test suite, so this step must not run.
*   **`--no-boost`** (*Defensive*): Jalara already depends on Laravel Boost and keeps its own curated configuration in `boost.json` and `.ai/`, with the generated agent guidelines refreshed through `composer run agents:update`. Laravel installer 5.31 only selects Boost while prompting, so `--no-interaction` already skips the step; `--no-boost` states the intent outright and keeps it skipped if that default ever changes. Boost remains installed in the resulting application either way.

### Alternative: Skipping Frontend Install/Build
To skip the frontend install and build during installation, replace `--pnpm` with `--no-node` and run the following manually afterwards:
```bash
pnpm install
pnpm run build
```

### Alternative: Direct Repository Clone
If you clone the repository instead of using the Laravel installer, run the setup command to initialize the project:
```bash
composer run setup
```

---

## Creating the Super Admin

Jalara ships without any pre-configured user. The super admin is created from environment-backed credentials so nothing secret is committed or seeded. 

1. Set the credentials in your `.env` file:
   ```dotenv
   SUPER_ADMIN_NAME="Super Admin"
   SUPER_ADMIN_EMAIL=admin@example.com
   SUPER_ADMIN_PASSWORD=your_secure_password
   ```

2. Create the user and synchronize the authorization catalog:
   ```bash
   php artisan auth:init-superadmin
   ```

> [!NOTE]  
> The command is idempotent: it restores the protected super admin and re-applies its role on every run. Adding the `--reset-password` flag re-applies the configured password. Public registration is disabled; further users are provisioned from the application's user management panel.

---

## Operational Requirements

To support asynchronous tasks, real-time features, and file storage, Jalara requires the following services and processes:

### 1. Task Scheduler
Periodic tasks and cleanup operations must be scheduled.
*   **Production**: Configure a cron entry on your server to run every minute:
    ```cron
    * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
    ```
*   **Local Development**: You may optionally run the scheduler in the foreground:
    ```bash
    php artisan schedule:work
    ```

### 2. Queue Worker
Asynchronous background tasks (such as notifications and image processing) run via queue workers.
*   **Production**: Run `php artisan queue:work` as a long-running process managed by a process monitor (e.g., Supervisor or systemd) to ensure it restarts automatically if it fails.
*   **Local Development**: Run the worker in the foreground:
    ```bash
    php artisan queue:work
    ```

### 3. Real-time Broadcasting (Laravel Reverb)
Chat features and live updates are driven by WebSockets.
*   **Production**: Run `php artisan reverb:start` as a long-running process managed by a process monitor (e.g., Supervisor or systemd) to keep the WebSockets server active.
*   **Local Development**: Start the server in the foreground:
    ```bash
    php artisan reverb:start
    ```

### 4. Storage Link
Uploaded files and brand assets are served publicly.
*   Supported installation paths (the Laravel installer and `composer run setup`) automatically link the public directory.
*   Manual execution is only required for custom deployments or if the link is missing:
    ```bash
    php artisan storage:link
    ```

---

## Repository Setup

A fresh copy of this starter kit needs **no repository variable and no secret** to get working CI. Every check runs out of the box, including the installer contract. The optional capabilities are switched on individually:

| Variable | Effect when set to `true` |
| --- | --- |
| `RELEASE_ENABLED` | Turns on release automation, once a credential below is also configured |
| `STARTER_KIT_MODE` | Turns on the weekly check that installs this repository with the real Laravel installer |

The repository itself should be configured for the trunk-based flow the workflows assume: `main` as the default branch, squash merge enabled, merge commits and rebase merges disabled, and *Automatically delete head branches* enabled. Under **Settings → Actions → General**, set the default workflow permission to read-only.

On a **public** repository, or on any paid plan, you can additionally require the `CI / required` check through a ruleset. The workflows do not depend on that: a private repository on the Free plan gets the same behaviour without it.

---

## Version Bootstrap for a Descendant

A project generated from this starter kit inherits Jalara's `CHANGELOG.md`, `.release-please-manifest.json`, and `version.json`. Those describe *Jalara's* releases, not yours. Before enabling release automation, reset them to your own starting point:

1. Empty or replace `CHANGELOG.md`.
2. Set `.release-please-manifest.json` to `{ ".": "0.1.0" }` (or your chosen starting version).
3. Set `version.json` to the same version.
4. Set `bootstrap-sha` in `release-please-config.json` to the first commit of your own history, and `baseline` in `.github/release-provenance.json` to the commit you are starting the policy from.

The three version files must agree; the release metadata gate fails the release pull request if they do not.

---

## Release Automation Setup

Releases are automated with [Release Please](https://github.com/googleapis/release-please) targeting the `main` branch. The process is opt-in, so a fork or private descendant stays silent until it is enabled — CI stays green and the release job explains in its run summary that it is inactive.

### 1. Choose a credential

**A GitHub App (preferred).** Its installation token expires within the hour and is issued per job with only the permissions that job needs.

1. Create a GitHub App under your account or organisation (**Settings → Developer settings → GitHub Apps → New GitHub App**). It needs no webhook and no user-facing URL.
2. Give it these repository permissions: **Contents: Read and write**, **Pull requests: Read and write**, **Issues: Read and write**, **Metadata: Read-only**.
3. Generate a private key and install the App on this repository only.
4. Add the App client ID (the value beginning with `Iv`) as the repository variable `RELEASE_APP_CLIENT_ID`, and the private key as the repository secret `RELEASE_APP_PRIVATE_KEY`.

The workflows narrow the token further per job: the publisher receives Contents and Metadata alone.

**A fine-grained personal access token (fallback).** Scope it to this repository with **Contents**, **Pull requests**, and **Issues** read/write, and add it as the repository secret `RELEASE_TOKEN`.

### 2. Switch automation on

Add the repository variable `RELEASE_ENABLED` with the value `true`.

### 3. Release

Merge to `main` as usual. After the gate passes, a release pull request appears; merging it publishes the tag and the GitHub Release. If a tag was written but its GitHub Release was not, re-run `release-publish.yml` — it completes that same version rather than raising a new one.
