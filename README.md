<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="public/assets/images/branding/logo-dark.png">
    <source media="(prefers-color-scheme: light)" srcset="public/assets/images/branding/logo.png">
    <img alt="Jalara Starter Kit Logo" src="public/assets/images/branding/logo.png" width="320">
  </picture>
</p>

# Jalara Starter Kit

A professional Laravel application starter kit with production-ready capabilities. Jalara provides a highly-polished, feature-rich foundation with integrated authentication, role-based authorization, comprehensive application settings, dynamic branding, and a complete testing suite.

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.5-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.5">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/Inertia-3-9553E9?style=flat-square&logo=inertia&logoColor=white" alt="Inertia.js 3">
  <img src="https://img.shields.io/badge/Vue-3-4FC08D?style=flat-square&logo=vue.js&logoColor=white" alt="Vue 3">
  <img src="https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white" alt="Tailwind CSS 4">
  <img src="https://img.shields.io/badge/status-development%20%2F%20pre--adoption-orange?style=flat-square" alt="Status: Development / Pre-adoption">
  <a href="https://github.com/tomylana93/jalara-starter-kit/actions/workflows/tests.yml"><img src="https://github.com/tomylana93/jalara-starter-kit/actions/workflows/tests.yml/badge.svg?branch=main" alt="tests"></a>
  <a href="https://github.com/tomylana93/jalara-starter-kit/releases/latest"><img src="https://img.shields.io/github/v/release/tomylana93/jalara-starter-kit?style=flat-square&label=release&color=blue" alt="Latest release"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="License"></a>
</p>

## Project Status

**Development / Pre-adoption.** The starter kit has no external adopters or
persistent production databases whose migration history must be preserved yet.
While this status remains active, existing application migrations may be
consolidated and development databases rebuilt from scratch.

Before the first real deployment, external adoption, or supported in-place
upgrade, change this status to **Stable / Adopted**. From that point onward,
existing migrations are immutable and every schema change must use a new,
forward-only migration. Creating a Git tag alone does not change this lifecycle
status.

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

## Prerequisites

Before setting up Jalara Starter Kit, ensure your environment meets the following requirements:

### System Prerequisites
- **PHP**: `^8.5` (Composer dependencies require this version)
- **Composer**: `^2.0`
- **Node.js**: Version `^24.x` recommended
- **pnpm**: `^11.0` (pinned via `pnpm-lock.yaml`)
- **Database**: SQLite (recommended for local development), MySQL, MariaDB, or PostgreSQL
- **Laravel Installer**: `^5.31`
- **ext-intl**: Required. Month names in documents are read from ICU so they match what the browser renders.
- **A Chrome or Chromium binary**: Required only for PDF export, at runtime.

#### PDF export at runtime

PDF export renders through Browsershot, which drives a real Chrome from Node. Two things follow, and neither is optional wherever documents are generated:

- `node_modules` must be present in production, because Browsershot shells out to Node and requires the `puppeteer` package. It is a runtime dependency, not a build-time one.
- A Chrome or Chromium binary must exist, and `LARAVEL_PDF_CHROME_PATH` must point at it. Puppeteer's own browser download is declined in `pnpm-workspace.yaml`, so no browser is fetched during `pnpm install`.

Nothing in CI catches a missing browser: the Pest suite fakes rendering, and only the end-to-end job runs a real one, pointed at the Chromium that Playwright installs. A misconfigured server therefore fails on the first document a user asks for, not in the pipeline.

Chromium's sandbox needs unprivileged user namespaces, which Ubuntu 23.10+ and most containers deny. Prefer granting the namespace permission over setting `LARAVEL_PDF_NO_SANDBOX=true`; the test suite sets that flag for itself, and production should not inherit it.

### Agent & MCP Prerequisites (for AI-assisted development)
- **Laravel Boost MCP**: Version `^2.0` for framework context, database query execution, and schemas
- **Serena MCP**: For semantic search, precise code refactoring, and code memories
- **Context7 MCP**: For non-Laravel library documentation queries
- **shadcn MCP**: For searching and managing shadcn-vue components

## Installation & Quick Start

Jalara installs as a community starter kit through the [Laravel installer](https://laravel.com/docs/installation) (version 5.31 or newer). Run the following command:

```bash
laravel new my-app \
  --using=https://github.com/tomylana93/jalara-starter-kit \
  --database=sqlite \
  --pnpm \
  --no-boost \
  --no-interaction
```

After installation, set up the development server:

```bash
composer run dev
```

For detailed explanations of the installation flags, how to configure the environment, setup the Super Admin, or run background processes (scheduler, queue, broadcasting), see the [Setup & Technical Documentation](docs/setup.md).

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

## Contributing & Releases

Guidelines for branching, tiered CI checks, commit conventions, and release automation are documented in the [Contributing & Releases Guidelines](CONTRIBUTING.md).

## License

This project is open-source software licensed under the [MIT License](LICENSE).
